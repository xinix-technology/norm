<?php
namespace Norm;

use Traversable;
use Norm\Exception\NormException;
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
     * @param  string   $key      Key name of filter
     * @param  callable $callable PHP class to use
     */
    public static function register($key, callable $callable)
    {
        static::$registries[$key] = $callable;
    }

    /**
     * [get description]
     * @param  string   $key [description]
     * @return callable      [description]
     */
    public static function get($key)
    {
        return isset(static::$registries[$key]) ? static::$registries[$key] : null;
    }

    /**
     * [parseFilterChain description]
     * @param  mixed $filterChain  [description]
     * @param  array &$ruleFilters [description]
     */
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

    /**
     * [parseFilterRules description]
     * @param  array $rules [description]
     * @return array        [description]
     */
    public static function parseFilterRules(array $rules)
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

    /**
     * [__construct description]
     * @param Collection $collection [description]
     * @param array      $rules      [description]
     */
    public function __construct(Collection $collection, array $rules)
    {
        $this->collection = $collection;

        $this->rules = static::parseFilterRules($rules);
    }

    /**
     * [getLabel description]
     * @param  string $key [description]
     * @return string      [description]
     */
    public function getLabel($key)
    {
        return isset($this->rules[$key]['label']) ? $this->rules[$key]['label'] : 'Unknown';
    }

    /**
     * [execFilter description]
     * @param  mixed|null  $filter [description]
     * @param  mixed|null  $data   [description]
     * @param  string      $k      [description]
     * @param  mixed       $rule   [description]
     * @return mixed               [description]
     */
    protected function execFilter($filter, $data, $k, $rule)
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

    /**
     * [run description]
     * @param  array  $data [description]
     * @param  string $key  [description]
     * @return array        [description]
     */
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
                    } catch (\Exception $e) {
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

    /**
     * [getErrors description]
     * @return array [description]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * [__debugInfo description]
     * @return [type] [description]
     */
    public function __debugInfo()
    {
        return $this->rules;
    }

    /**
     * [filterRequired description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterRequired($value, $opts)
    {
        if (is_null($value) || $value === '') {
            throw (new FilterException('Field %s is required'))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    /**
     * [filterConfirmed description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterConfirmed($value, $opts)
    {
        if (is_null($value) || $value === '') {
            unset($opts['data'][$opts['key']]);
            unset($opts['data'][$opts['key'].'_confirmation']);
            return '';
        }

        if ($value.'' !== $opts['data'][$opts['key'].'_confirmation']) {
            throw (new FilterException('Field %s must be confirmed'))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        unset($opts['data'][$opts['key'].'_confirmation']);

        return $value;
    }

    /**
     * [filterSalt description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterSalt($value, $opts)
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
                    throw new NormException('You should define salt key in order to use salt.');
                }

                $value = $method($value.$key);
            }
        }

        return $value;
    }

    /**
     * [filterUnique description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterUnique($value, $opts)
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
            throw (new FilterException('Field %s must be unique'))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    /**
     * [filterRequiredWith description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterRequiredWith($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!empty($opts['data'][$opts['arguments'][0]]) && (is_null($value) || $value === '')) {
            throw (new FilterException('Field %s is required'))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;

    }

    /**
     * [filterRequiredWithout description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterRequiredWithout($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (empty($opts['data'][$opts['arguments'][0]]) && (is_null($value) || $value === '')) {
            throw (new FilterException('Field %s is required'))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;

    }

    /**
     * [filterMin description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterMin($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if ($value < $opts['arguments'][0]) {
            throw (new FilterException('Field %s less than '.$opts['arguments'][0]))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    /**
     * [filterIp description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterIp($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_IP)) {
            throw (new FilterException('Field %s is not valid IP Address'))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    /**
     * [filterEmail description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterEmail($value, $opts)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw (new FilterException('Field %s is not valid email'))
                ->setContext($opts['key'])
                ->setArgs($this->getLabel($opts['key']));
        }

        return $value;
    }

    /**
     * [filterRemoveEmpty description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterRemoveEmpty($value, $opts)
    {
        if (empty($value)) {
            return $value;
        }
        $filtered = array_filter($value->toArray());
        $value->set($filtered);
        return $value;
    }

    /**
     * [filterDefault description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterDefault($value, $opts)
    {
        if (empty($value)) {
            return $opts['arguments'][0];
        }

        return $value;
    }

    /**
     * [getAttribute description]
     * @param  string $key [description]
     * @return mixed       [description]
     */
    public function getAttribute($key)
    {
        return $this->collection->getAttribute($key);
    }
}
