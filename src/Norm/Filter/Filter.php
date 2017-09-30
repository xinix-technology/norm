<?php namespace Norm\Filter;

use Exception;

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
    protected static $registries = array();

    /**
     * Available rules
     *
     * @var array
     */
    protected $rules = array();

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
     * @param  string $clazz PHP class to use
     */
    public static function register($key, $clazz)
    {
        static::$registries[$key] = $clazz;
    }

    /**
     * Static method to create instance of filter from database schema configuration.
     *
     * @param array $schema      Database schema configuration
     * @param array $preFilter   Filters that will be run before the filter
     * @param array $postFilter  Filters that will be run after the filter
     *
     * @return \Norm\Filter\Filter
     */
    public static function fromSchema($schema, $preFilter = array(), $postFilter = array())
    {
        $rules = array();

        foreach ($preFilter as $key => $filter) {
            $filter = explode('|', $filter);

            foreach ($filter as $f) {
                $rules[$key][] = trim($f);
            }
        }

        foreach ($schema as $k => $field) {
            if (is_null($field)) {
                continue;
            }

            $rules[$k] = array(
                'label' => $field['label'],
                'filter' => $field->filter(),
            );
        }

        foreach ($postFilter as $key => $filter) {
            $filter = explode('|', $filter);

            foreach ($filter as $f) {
                $rules[$key]['filter'][] = trim($f);
            }
        }

        return new static($rules);
    }

    public static function create($filters = array())
    {
        $rules = array();

        foreach ($filters as $key => $filter) {
            $filter = explode('|', $filter);

            foreach ($filter as $f) {
                $rules[$key]['label'] = $key;
                $rules[$key]['filter'][] = trim($f);
            }
        }

        return new static($rules);
    }

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    public function run($data, $key = null)
    {
        $this->errors = array();

        $rules = null;

        if (is_null($key)) {
            $rules = $this->rules;
        } elseif (isset($this->rules[$key])) {
            $rules = array(
                'password' => $this->rules[$key]
            );
        }

        if (is_array($rules)) {
            foreach ($rules as $k => $rule) {
                if (empty($rule['filter'])) {
                    continue;
                }

                foreach ($rule['filter'] as $filterChain) {
                    try {
                        if (is_string($filterChain)) {
                            $method = explode(':', $filterChain);
                            $args = array();

                            if (isset($method[1])) {
                                $args = explode(',', $method[1]);
                            }

                            $method = $method[0];

                            $innerMethodName = 'filter'.strtoupper($method[0]).substr($method, 1);

                            if (method_exists($this, $innerMethodName)) {
                                $method = $innerMethodName;
                                $val = (isset($data[$k])) ? $data[$k] : null;
                                $data[$k] = $this->$method($k, $val, $data, $args);
                            } elseif (isset(static::$registries[$method]) &&
                                is_callable(static::$registries[$method])) {
                                $method = static::$registries[$method];
                                $data[$k] = call_user_func($method,$k , $data[$k], $data, $args);
                            } elseif (function_exists($method)) {
                                $data[$k] = $method($data[$k]);
                            } else {
                                throw new Exception('Filter "'.$filterChain.'" not found.');
                            }
                        } elseif (is_callable($filterChain)) {
                            $data[$k] = call_user_func($filterChain, $data[$k], $data, array());
                        }
                    } catch (SkipException $e) {
                        break;
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

    public function errors()
    {
        return $this->errors;
    }

    public function filterRequired($key, $value, $data, $args)
    {
        if (is_null($value) || $value === '') {
            throw FilterException::factory($key, 'Field %s is required')->args($this->rules[$key]['label']);
        }

        return $value;
    }

    public function filterConfirmed($key, $value, $data)
    {
        if (is_null($value) || $value === '') {
            unset($data[$key]);
            unset($data[$key.'_confirmation']);
            return '';
        }

        if ($value.'' !== $data[$key.'_confirmation']) {
            throw FilterException::factory($key, 'Field %s must be confirmed')->args($this->rules[$key]['label']);
        }

        unset($data[$key.'_confirmation']);

        return $value;
    }


    public function filterUnique($key, $value, $data, $args = array())
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        $clazz = $args[0];
        $field = isset($args[1]) ? $args[1] : $key;
        $model = \Norm\Norm::factory($clazz)->findOne(array($field => $value));

        if (isset($model) && $model['$id'] != $data['$id']) {
            throw FilterException::factory($key, 'Field %s must be unique')->args($this->rules[$key]['label']);
        }

        return $value;
    }

    public function filterRequiredWith($key, $value, $data, $args = array())
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!empty($data[$args[0]]) && (is_null($value) || $value === '')) {
            throw FilterException::factory($key, 'Field %s is required')->args($this->rules[$key]['label']);
        }

        return $value;

    }

    public function filterRequiredWithout($key, $value, $data, $args = array())
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (empty($data[$args[0]]) && (is_null($value) || $value === '')) {
            throw FilterException::factory($key, 'Field %s is required')->args($this->rules[$key]['label']);
        }

        return $value;

    }

    public function filterMin($key, $value, $data, $args = array())
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if ($value < $args[0]) {
            throw FilterException::factory($key, 'Field %s less than '.$args[0])->args($this->rules[$key]['label']);
        }

        return $value;

    }

    public function filterIp($key, $value)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_IP)) {
            throw FilterException::factory($key, 'Field %s is not valid IP Address')->args($this->rules[$key]['label']);
        }

        return $value;
    }

    public function filterEmail($key, $value)
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw FilterException::factory($key, 'Field %s is not valid email')->args($this->rules[$key]['label']);
        }

        return $value;
    }

    public function filterRemoveEmpty($key, $value)
    {
        if (empty($value)) {
            return $value;
        }
        $filtered = array_filter($value->toArray());
        $value->set($filtered);
        return $value;
    }

    public function filterDefault($key, $value, $data, $args)
    {
        if (empty($value)) {
            return $args[0];
        }

        return $value;
    }
}
