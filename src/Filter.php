<?php
namespace Norm;

use Traversable;
use Exception;
use Norm\Exception\NormException;
use Norm\Exception\SkipException;
use Norm\Exception\FilterException;
use Norm\Exception\FatalException;
use Norm\Normable;
use Norm\Collection;
use Norm\Schema\NField;
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
     * [$collection description]
     * @var Norm\Collection
     */
    protected $collection;

    /**
     * Available fields
     *
     * @var array
     */
    protected $fields;

    /**
     * Errors
     *
     * @var array
     */
    protected $errors;

    /**
     * [$immediate description]
     * @var boolean
     */
    protected $immediate = false;

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
        // FIXME coba liat ini lagi, kayaknya ada yg ga bagus
        if (is_callable($filterChain)) {
            $ruleFilters[] = [$filterChain, [], 'f'];
        } elseif (is_string($filterChain)) {
            $filterArr = explode('|', $filterChain);
            foreach ($filterArr as $singleFilter) {
                $parsed = explode(':', $singleFilter);
                if (!isset($parsed[1]) && is_callable($parsed[0])) {
                    $ruleFilters[] = [$parsed[0], [], 'f'];
                } else {
                    $parsed[1] = isset($parsed[1]) ? explode(',', $parsed[1]) : [];
                    $parsed[] = 's';
                    $ruleFilters[] = $parsed;
                }
            }
        } elseif (is_array($filterChain)) {
            foreach ($filterChain as $f) {
                static::parseFilterChain($f, $ruleFilters);
            }
        }
    }

    /**
     * [parseFilterRules description]
     * @param  array $fields [description]
     * @return array        [description]
     */
    public static function parseFilterRules($fields)
    {
        // FIXME coba liat ini lagi, kayaknya ada yg ga bagus
        $newRules = [];

        foreach ($fields as $k => $rule) {
            $ruleFilters = [];
            $filters = $rule instanceof NField ? $rule->getFilter() : (isset($rule['filter']) ? $rule['filter'] : []);
            static::parseFilterChain($filters, $ruleFilters);
            $rule['filter'] = $ruleFilters;
            $newRules[$k] = $rule;
        }

        return $newRules;
    }

    /**
     * [__construct description]
     * @param Collection $collection [description]
     * @param array      $fields      [description]
     */
    public function __construct($fields = [], $immediate = false)
    {
        if ($fields instanceof Collection) {
            $this->collection = $fields;
            $this->repository = $fields->getRepository();
        } elseif (!is_array($fields)) {
            throw new NormException('Rules must be array or instance of Schema');
        }
        $this->fields = static::parseFilterRules($fields);
        $this->immediate = $immediate;
    }

    public function setImmediate($immediate)
    {
        $this->immediate = $immediate;
        return $this;
    }

    /**
     * [getLabel description]
     * @param  string $key [description]
     * @return string      [description]
     */
    public function getLabel($key)
    {
        return isset($this->fields[$key]['label']) ? $this->fields[$key]['label'] : 'Unknown';
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
            if (null !== $filterFn) {
                return $filterFn($val, $opts);
            } elseif (is_callable([$this, 'filter'.$filter[0]])) {
                $fn = 'filter'.$filter[0];
                return $this->$fn($val, $opts);
            } else {
                throw new FatalException('Ineligible filter '.$filter[0].' for '.$k);
            }
        } else {
            return $filter[0]($val);
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

        $fields = null;

        if (null === $key) {
            $fields = $this->fields;
        } elseif (isset($this->fields[$key])) {
            $fields = [
                $key => $this->fields[$key]
            ];
        }

        if (is_array($fields)) {
            foreach ($fields as $k => $rule) {
                foreach ($rule['filter'] as $filter) {
                    try {
                        $data[$k] = $this->execFilter($filter, $data, $k, $rule);
                    } catch (SkipException $e) {
                        break;
                    } catch (FatalException $e) {
                        throw $e;
                    } catch (Exception $e) {
                        if ($this->immediate) {
                            throw $e;
                        } else {
                            $this->errors[] = $e;
                            break;
                        }
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
        return $this->fields;
    }

    /**
     * [filterRequired description]
     * @param  [type] $value [description]
     * @param  [type] $opts  [description]
     * @return [type]        [description]
     */
    protected function filterRequired($value, $opts)
    {
        if (null === $value || '' === $value) {
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
        if (null === $value || $value === '') {
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
            $config = null === $this->repository ? null : $this->repository->getAttribute('salt');
            if (null !== $config) {
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
        if (null === $this->collection) {
            throw (new FilterException('Filter unique needs collection to be set'));
        }

        if (null === $value || '' === $value) {
            return '';
        }

        $collection = $this->collection;
        $argCount = count($opts['arguments']);
        switch ($argCount) {
            case 0:
                $key = $opts['key'];
                break;
            case 1:
                $key = $opts['arguments'][0];
                break;
            default:
                $collection = $this->factory($opts['arguments'][0]);
                $key = $opts['arguments'][1];
        }

        $model = $collection->findOne([$key => $value]);

        if (null !== $model &&
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
        if (null !== $value && '' !== $value) {
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
        if (null !== $value && '' !== $value) {
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
        if (null !== $value && '' !== $value) {
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
        if (null !== $value && '' !== $value) {
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
        if (null !== $value && '' !== $value) {
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
