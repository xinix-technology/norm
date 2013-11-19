<?php

namespace Norm\Filter;

class Filter {

    protected static $registries = array();

    protected $rules = array();
    protected $errors;

    public static function register($key, $clazz) {
        static::$registries[$key] = $clazz;
    }

    public static function fromSchema($schema, $preFilter = array(), $postFilter = array()) {
        $rules = array();

        foreach ($preFilter as $key => $filter) {
            $filter = explode('|', $filter);
            foreach ($filter as $f) {
                $rules[$key][] = $f;
            }
        }

        foreach ($schema as $k => $field) {
            $rules[$k] = $field->filter();
        }

        foreach ($postFilter as $key => $filter) {
            $filter = explode('|', $filter);
            foreach ($filter as $f) {
                $rules[$key][] = $f;
            }
        }

        return new static($rules);
    }

    public function __construct($rules) {
        $this->rules = $rules;
    }

    public function run($data, $key = NULL) {
        $this->errors = array();

        foreach($this->rules as $k => $ruleChain) {
            foreach ($ruleChain as $rule) {
                try {
                    if (is_string($rule)) {
                        $method = explode(':', $rule);
                        $args = array();
                        if (isset($method[1])) {
                            $args = explode(',', $method[1]);
                        }
                        $method = $method[0];

                            if (method_exists($this, 'filter_'.$method)) {
                                $method = 'filter_'.$method;
                                $data[$k] = $this->$method($k, $data[$k], $data, $args);
                            } elseif (isset(static::$registries[$method])) {
                                $method = static::$registries[$method];
                                $data[$k] = $method($k, $data[$k], $data, $args);
                            } elseif (function_exists($method)) {
                                $data[$k] = $method($data[$k]);
                            } else {
                                throw new \Exception('Filter "'.$rule.'" not found.');
                            }
                    } elseif (is_callable($rule)) {
                        $data[$k] = call_user_func($rule, $k, $data[$k], $data, $args);
                    }
                } catch(FilterException $e) {
                    $this->errors[] = ''.$e;
                    break;
                } catch(\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    break;
                }
            }
        }
        return $data;
    }

    public function errors() {
        return $this->errors;
    }

    public function filter_required($key, $value) {
        if (is_null($value) || $value === '') {
            throw FilterException::factory('Field %s is required')->name($key);
        }
        return $value;
    }

    public function filter_confirmed($key, $value, $data) {
        if ($value !== $data[$key.'_confirmation']) {
            throw FilterException::factory('Field %s must be confirmed')->name($key);
        }
        unset($data[$key.'_confirmation']);
        return $value;
    }

    public function filter_unique($key, $value, $data, $args = array()) {
        $clazz = $args[0];
        $field = isset($args[1]) ? $args[1] : $key;
        $model = \Norm\Norm::factory($clazz)->findOne(array($field => $value));
        if(isset($model) && $model['$id'] != $data['$id']) {
            throw FilterException::factory('Field %s must be unique')->name($key);
        }
        return $value;
    }

    public function filter_requiredWith($key, $value, $data, $args = array()) {
        if (!empty($data[$args[0]]) && (is_null($value) || $value === '')) {
            throw FilterException::factory('Field %s is required')->name($key);
        }
        return $value;

    }

    public function filter_requiredWithout($key, $value, $data, $args = array()) {
        if (empty($data[$args[0]]) && (is_null($value) || $value === '')) {
            throw FilterException::factory('Field %s is required')->name($key);
        }
        return $value;

    }

    public function filter_ip($key, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_IP)) {
            throw FilterException::factory('Field %s is not valid IP Address')->name($key);
        }
        return $value;
    }

}
