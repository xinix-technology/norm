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
 * @package     Norm
 *
 */
namespace Norm;

/**
 * Base class for connection instance
 */
abstract class Connection extends Hookable {
    protected $options;

    protected $raw;

    protected $collections = array();

    public function __construct($options) {
        $this->options = $options;

        $this->initialize($options);
    }

    public function getOptions() {
        return $this->options;
    }

    public function getName() {
        return $this->options['name'];
    }

    public function getRaw() {
        return $this->raw;
    }

    public function factory($collectionName) {
        if (!isset($this->collections[$collectionName])) {
            $collection = Norm::createCollection(array(
                'name' => $collectionName,
                'connection' => $this,
            ));

            $this->applyHook('norm.after.factory', $collection);

            $this->collections[$collectionName] = $collection;
        }

        return $this->collections[$collectionName];
    }

    public function hasCollection($name) {
        $collections = $this->listCollections();
        foreach ($collections as $key => $collection) {
            if ($collection === $name) {
                return true;
            }
        }
        return false;
    }

    abstract public function initialize($options);
    // abstract public function migrate(Collection $collection);
    abstract public function listCollections();
    abstract public function prepare(Collection $collection, $object);
    abstract public function query(Collection $collection);
    abstract public function save(Collection $collection, Model $model);
    abstract public function remove(Collection $collection, $model);
}
