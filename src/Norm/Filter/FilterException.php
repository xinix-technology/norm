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
 *
 * FilterException is official exception that raise on the failing of database
 * field filter (validation).
 *
 * Filter (validation) will raise FilterException instead of return succeed or
 * failing condition.
 *
 * The FilterException contains information of field name where the exception
 * raise and sub exceptions as array of exceptions raise on the same field name.
 *
 */
class FilterException extends \RuntimeException {

    /**
     * Database field name where exception raise
     * @var string
     */
    protected $name;

    /**
     * Array of sub exceptions
     * @var array
     */
    protected $sub;

    /**
     * Factory method to create new exception
     * @param  string $message Message of new exception
     * @return \Norm\Filter\FilterException
     */
    public static function factory($message) {
        return new static($message);
    }

    function __construct($message = '', $code = 0, $exception = null) {
        parent::__construct($message, 400, $exception);
    }

    /**
     * Set field name of exception
     * @param  string $name The field name
     * @return FilterException return self object to be chained
     */
    public function name($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Set sub exceptions
     * @param  array $sub Sub exceptions
     * @return FilterException return self object to be chained
     */
    public function sub($sub) {
        $this->sub = $sub;
        return $this;
    }

    /**
     * Return string representation for object
     * @return string representation for object
     */
    public function __toString() {
        $str = '';
        if (is_array($this->sub)) {
            foreach ($this->sub as $c) {
                $str .= '<p>'.$c."</p>\n";
            }
        } else {
            $str .= sprintf($this->getMessage(), $this->name);
        }
        return $str;
    }

    // TODO i dont know why getMessage overriding dont work
    // public function __toString() {
    //     return $this->getMessage();
    // }
}
