<?php
namespace Norm;

use Exception;
use ArrayAccess;
use InvalidArgumentException;
use ROH\Util\Collection as UtilCollection;
use Norm\Exception\SkipException;
use Norm\Exception\FilterException;
use Norm\Exception\FatalException;

/**
 * Filter (validation) for database field
 */
class Filter
{
    /**
     * Registries of available filters
     *
     * @var array
     */
    protected static $registries = [

    ];

    protected $collection;

    /**
     * Available rules
     *
     * @var array
     */
    protected $rules;

    /**
     * Errors
     *
     * @var array
     */
    protected $errors;

    /**
     * Register custom filter to use later
     *
     * @param  string $key   Key name of filter
     * @param  string $callable PHP class to use
     */
    public static function register($key, $callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Invalid callable to register');
        }
        static::$registries[$key] = $callable;
    }

    public static function get($key)
    {
        if (isset(static::$registries[$key])) {
            return static::$registries[$key];
        }
    }

    public static function debugRegistration()
    {
        return static::$registries;
    }

    protected static function parseFilterChain($filterChain, &$ruleFilters)
    {
        if (is_string($filterChain)) {
            $filterArr = explode('|', $filterChain);
            foreach ($filterArr as $singleFilter) {
                $parsed = explode(':', $singleFilter);
                $parsed[1] = isset($parsed[1]) ? explode(',', $parsed[1]) : [];
                $parsed[] = 's';
                $ruleFilters[] = $parsed;
            }
        } elseif (is_callable($filterChain)) {
            $ruleFilters[] = [$filterChain, [], 'f'];
        }
    }

    public static function parseFilterRules($rules)
    {
        $newRules = [];

        foreach ($rules as $k => $rule) {
            $ruleFilters = [];
            if (isset($rule['filters'])) {
                foreach ($rule['filters'] as $filterChain) {
                    static::parseFilterChain($filterChain, $ruleFilters);
                }
            }
            $rule['filters'] = $ruleFilters;

            $newRules[$k] = $rule;
        }

        return $newRules;
    }

    public function __construct(Collection $collection, $rules)
    {
        if (!is_array($rules) && !($rules instanceof ArrayAccess)) {
            throw new InvalidArgumentException('Filter rules must be instance of array');
        }

        $this->collection = $collection;

        $this->rules = static::parseFilterRules($rules);
    }

    public function getLabel($key)
    {
        return isset($this->rules[$key]['label']) ? $this->rules[$key]['label'] : 'Unknown';
    }

    public function execFilter($filter, $data, $k, $rule)
    {
        if (!is_array($filter) || count($filter) < 3) {
            throw new FatalException('Invalid filter'.print_r($filter, 1));
        }

        $val = isset($data[$k]) ? $data[$k] : null;
        $opts = [
            'key' => $k,
            'data' => $data,
            'arguments' => $filter[1],
            'meta' => $filter,
            'rule' => $rule,
            'filter' => $this,
        ];
        if ($filter[2] === 's') {
            $filterFn = static::get($filter[0]);
            if (isset($filterFn)) {
                return $filterFn($val, $opts);
            } elseif (is_callable([$this, 'filter'.$filter[0]])) {
                $fn = 'filter'.$filter[0];
                return $this->$fn($val, $opts);
            } elseif (is_callable($filter[0])) {
                return $filter[0]($val);
            }
        } else {
            return $filter[0]($val, $opts);
        }

        $message = 'Ineligible filter ';
        if ($filter[2] === 's') {
            $message .= $filter[0];
        } else {
            $message .= '{callable}';
        }
        $message .= ' for ';
        if ($data instanceof Model) {
            $message .= $data->getCollectionName().'::';
        }
        $message .= $k;
        throw new FatalException($message);
    }

    public function run($data, $key = null)
    {
        $this->errors = [];

        $rules = null;

        if (is_null($key)) {
            $rules = $this->rules;
        } elseif (isset($this->rules[$key])) {
            $rules = [
                $key => $this->rules[$key]
            ];
        }

        if (is_array($rules)) {
            foreach ($rules as $k => $rule) {
                if (empty($rule['filters'])) {
                    continue;
                }

                foreach ($rule['filters'] as $filter) {
                    try {
                        $data[$k] = $this->execFilter($filter, $data, $k, $rule);
                    } catch (SkipException $e) {
                        break;
                    } catch (FatalException $e) {
                        throw $e;
                    } catch (Exception $e) {
                        $this->errors[] = $e;

                        break;
                    }
                }
            }
        }

        if ($this->errors) {
            $e = new FilterException();
            $e->setChildren($this->errors);
            throw $e;
        }

        return $data;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function __debugInfo()
    {
        return $this->rules;
    }

    public function filterRequired($value, $opts)
    {
        if (is_null($value) || $value === '') {
            throw FilterException::create($opts['key'], 'Field %s is required')
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    public function filterConfirmed($value, $opts)
    {
        if (is_null($value) || $value === '') {
            unset($opts['data'][$opts['key']]);
            unset($opts['data'][$opts['key'].'_confirmation']);
            return '';
        }

        if ($value.'' !== $opts['data'][$opts['key'].'_confirmation']) {
            throw FilterException::create($opts['key'], 'Field %s must be confirmed')
                ->setArgs($this->getLabel($opts['key']));
        }

        unset($opts['data'][$opts['key'].'_confirmation']);

        return $value;
    }

    public function filterSalt($value, $opts)
    {
        if ($value) {
            $config = $this->getAttribute('salt');
            if (isset($config)) {
                $method = 'md5';
                if (is_string($config)) {
                    $key = $config;
                } else {
                    list($method, $key) = $config;
                }

                if (empty($key)) {
                    throw new \Exception('You should define salt key in order to use salt.');
                }

                $value = $method($value.$key);
            }
        }

        return $value;
    }


    public function filterUnique($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        $argCount = count($opts['arguments']);
        switch ($argCount) {
            case 0:
                $model = $this->collection->findOne([$opts['key'] => $value]);
                break;
            case 1:
                $model = $this->collection->findOne([$opts['arguments'][0] => $value]);
                break;
            default:
                $model = $this->collection->factory($opts['arguments'][0])->findOne([$opts['arguments'][1] => $value]);
        }

        if (isset($model) && $model['$id'] != $opts['data']['$id']) {
            throw FilterException::create($opts['key'], 'Field %s must be unique')
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    public function filterRequiredWith($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!empty($opts['data'][$opts['arguments'][0]]) && (is_null($value) || $value === '')) {
            throw FilterException::create($opts['key'], 'Field %s is required')
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;

    }

    public function filterRequiredWithout($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (empty($opts['data'][$opts['arguments'][0]]) && (is_null($value) || $value === '')) {
            throw FilterException::create($opts['key'], 'Field %s is required')
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;

    }

    public function filterMin($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if ($value < $opts['arguments'][0]) {
            throw FilterException::create($opts['key'], 'Field %s less than '.$opts['arguments'][0])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }


    public function filterIp($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_IP)) {
            throw FilterException::create($opts['key'], 'Field %s is not valid IP Address')
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    public function filterEmail($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw FilterException::create($opts['key'], 'Field %s is not valid email')
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    public function filterRemoveEmpty($value, $opts)
    {
        if (empty($value)) {
            return $value;
        }
        $filtered = array_filter($value->toArray());
        $value->set($filtered);
        return $value;
    }

    public function filterDefault($value, $opts)
    {
        if (empty($value)) {
            return $opts['arguments'][0];
        }

        return $value;
    }

    public function getAttribute($key)
    {
        return $this->collection->getAttribute($key);
    }
}
