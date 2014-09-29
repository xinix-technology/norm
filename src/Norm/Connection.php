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
abstract class Connection extends Hookable
{
    protected $options;

    protected $raw;

    protected $collections = array();

    public function __construct($options)
    {
        $this->options = $options;

        $this->initialize($options);
    }

    public function option($name = null)
    {
        if (func_num_args() === 0) {
            return $this->options;
        } elseif (isset($this->options[$name])) {
            return $this->options[$name];
        }
    }

    public function getName()
    {
        return $this->options['name'];
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function setRaw($raw)
    {
        $this->raw = $raw;
    }

    public function factory($collectionName)
    {
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

    public function hasCollection($name)
    {
        $collections = $this->listCollections();
        foreach ($collections as $key => $collection) {
            if ($collection === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Unmarshall single object from data source to associative array. The
     * unmarshall process is necessary due to different data type provided by
     * data source. Proper unmarshall will make sure data from data source that
     * will be consumed by Norm in the accepted form of data.
     *
     * @see Norm\Connection::marshall()
     *
     * @param  mixed     $object     Object from data source
     * @return assoc                 Friendly norm data
     */
    public function unmarshall($object)
    {
        if (isset($object['id'])) {
            $newObject = array(
                '$id' => $this->unmarshall($object['id']),
            );
            foreach ($object as $key => $value) {
                if ($key === 'id') {
                    continue;
                }
                if ($key[0] === '_') {
                    $key[0] = '$';
                }
                $newObject[$key] = $this->unmarshall($value);
            }

            $object = $newObject;
        }

        return $object;
    }

    /**
     * Marshal single object from norm to the proper data accepted by data
     * source. Sometimes data source expects object to be persisted to it in
     * specific form, this method will transform associative array from Norm
     * into this specific form.
     *
     * @see Norm\Connection::unmarshall()
     *
     * @param  assoc    $object Norm data
     * @return mixed            Friendly data source object
     */
    public function marshall($object)
    {
        if (is_array($object)) {
            $result = array();
            foreach ($object as $key => $value) {
                if ($key[0] === '$') {
                    if ($key === '$id' || $key === '$type') {
                        continue;
                    }
                    $result['_'.substr($key, 1)] = $this->marshall($value);
                } else {
                    $result[$key] = $this->marshall($value);
                }
            }
            return $result;
        // FIXME \Norm\Type\XXX should have marshall method
        } elseif ($object instanceof \Norm\Type\DateTime) {
            return $object->format('c');
        } elseif ($object instanceof \Norm\Type\NormArray) {
            return json_encode($object->toArray());
        } elseif (method_exists($object, 'marshall')) {
            return $object->marshall();
        } else {
            return $object;
        }
    }

    abstract public function initialize($options);
    abstract public function listCollections();
    abstract public function query(Collection $collection);
    abstract public function save(Collection $collection, Model $model);
    abstract public function remove(Collection $collection, $model);
    // abstract public function migrate(Collection $collection);
}
