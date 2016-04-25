<?php
namespace Norm;

use Traversable;
use Exception;
use Norm\Exception\NormException;
use Norm\Exception\SkipException;
use Norm\Exception\FilterException;
use Norm\Exception\FatalException;
use Norm\Normable;
use Norm\Type\Object as TypeObject;

/**
 * Filter (validation) for database field
 */
class Filter extends Normable
{
    /**
     * Registries of available filters
     *
     * @var array
     */
    protected static $registries = [];

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
    public function __construct(Collection $collection = null, array $rules = [])
    {
        parent::__construct($collection);

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
        // if (!is_array($filter) || count($filter) < 3) {
        //     throw new FatalException('Invalid filter ' . print_r($filter, 1));
        // }

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
            } else {
                $message = 'Ineligible filter '.$filter[0].' for ';
                // if ($data instanceof Model) {
                //     $message .= $data->getCollectionName().'::';
                // }
                $message .= $k;
                throw new FatalException($message);
            }
        } else {
            return $filter[0]($val, $opts);
        }
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
                // if (empty($rule['filters'])) {
                //     continue;
                // }

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
        if (!empty($value)) {
            $config = $this->getAttribute('salt');
            if (isset($config)) {
                $method = 'md5';
                if (is_string($config)) {
                    $key = $config;
                } elseif (2 === count($config)) {
                    list($method, $key) = $config;
                }

                if (!empty($key)) {
                    return $method($value.$key);
                }
            }

            throw new FatalException('You should define salt key in order to use salt.');
        }
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
                $model = $this->parent->findOne([$opts['key'] => $value]);
                break;
            case 1:
                $model = $this->parent->findOne([$opts['arguments'][0] => $value]);
                break;
            default:
                $model = $this->parent->factory($opts['arguments'][0])->findOne([$opts['arguments'][1] => $value]);
        }

        if (
            isset($model) &&
            (
                (isset($model['$id']) && $model['$id'] != $opts['data']['$id']) ||
                !isset($model['$id'])
            )
        ) {
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
        if (empty($opts['data'][$opts['arguments'][0]])) {
            return $value;
        }

        if (empty($value)) {
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
        if (!empty($opts['data'][$opts['arguments'][0]])) {
            return $value;
        }

        if (empty($value)) {
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
        if (!is_null($value) && '' !== $value) {
            if ($value < $opts['arguments'][0]) {
                throw (new FilterException('Field %s less than '.$opts['arguments'][0]))
                    ->setContext($opts['key'])
                    ->setArgs($this->getLabel($opts['key']));
            }
        }

        return $value;
    }

    protected function filterMax($value, $opts)
    {
        if (!is_null($value) && '' !== $value) {
            if ($value > $opts['arguments'][0]) {
                throw (new FilterException('Field %s more than '.$opts['arguments'][0]))
                    ->setContext($opts['key'])
                    ->setArgs($this->getLabel($opts['key']));
            }
        }

        return $value;
    }

    protected function filterBetween($value, $opts)
    {
        if (!is_null($value) && '' !== $value) {
            if ($value < $opts['arguments'][0] || $value > $opts['arguments'][1]) {
                throw (new FilterException('Field %s out of %s to %s'))
                    ->setContext($opts['key'])
                    ->setArgs($this->getLabel($opts['key'], $opts['arguments'][0], $opts['arguments'][1]));
            }
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
        if (!is_null($value) && '' !== $value) {
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_IP)) {
                throw (new FilterException('Field %s is not valid IP Address'))
                    ->setContext($opts['key'])
                    ->setArgs($this->getLabel($opts['key']));
            }
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
        if (!is_null($value) && '' !== $value) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw (new FilterException('Field %s is not valid email'))
                    ->setContext($opts['key'])
                    ->setArgs($this->getLabel($opts['key']));
            }
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

        $filtered = array_filter(is_array($value) ? $value : $value->toArray());

        return new TypeObject($filtered);
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
            $value = $opts['arguments'][0];
        }

        return $value;
    }
}
