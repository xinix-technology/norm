<?php

/**
 * Norm - (not) ORM Framework
 *
 * MIT LICENSE
 *
 * Copyright (c) 2013 PT Sagara Xinix Solusitama
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm\Filter
 *
 */
namespace Norm\Filter;

/**
 * Filter (validation) for database field
 */
class Filter {

    /**
     * Registries of available filters
     * @var array
     */
    protected static $registries = array();

    /**
     * Available rules
     * @var array
     */
    protected $rules = array();

    /**
     * Errors
     * @var array
     */
    protected $errors;

    /**
     * Register custom filter to use later
     * @param  string $key   Key name of filter
     * @param  string $clazz PHP class to use
     */
    public static function register($key, $clazz) {
        static::$registries[$key] = $clazz;
    }

    /**
     * Static method to create instance of filter from database schema configuration.
     * @param  array $schema      Database schema configuration
     * @param  array  $preFilter  Filters that will be run before the filter
     * @param  array  $postFilter Filters that will be run after the filter
     * @return \Norm\Filter\Filter
     */
    public static function fromSchema($schema, $preFilter = array(), $postFilter = array()) {
        $rules = array();

        foreach ($preFilter as $key => $filter) {
            $filter = explode('|', $filter);
            foreach ($filter as $f) {
                $rules[$key][] = trim($f);
            }
        }

        foreach ($schema as $k => $field) {
            $rules[$k] = $field->filter();
        }

        foreach ($postFilter as $key => $filter) {
            $filter = explode('|', $filter);
            foreach ($filter as $f) {
                $rules[$key][] = trim($f);
            }
        }

        return new static($rules);
    }

    public static function create($filters = array()) {
        $rules = array();

        foreach ($filters as $key => $filter) {
            $filter = explode('|', $filter);
            foreach ($filter as $f) {
                $rules[$key][] = trim($f);
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
                } catch(SkipException $e) {
                    break;
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
        if ($value == '') {
            unset($data[$key]);
            unset($data[$key.'_confirmation']);
            throw new SkipException();
        }
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

    public function filter_min($key, $value, $data, $args = array()) {
        if ($value < $args[0]) {
            throw FilterException::factory('Field %s less than '.$args[0])->name($key);
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
