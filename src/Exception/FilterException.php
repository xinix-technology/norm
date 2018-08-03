<?php

/**
 * Norm - (not) ORM Framework
 *
 * MIT LICENSE
 *
 * Copyright (c) 2015 PT Sagara Xinix Solusitama
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
 * @copyright   2016 PT Sagara Xinix Solusitama
 * @link        http://sagara.id/p/product
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm\Filter
 *
 */
namespace Norm\Exception;

use RuntimeException;

/**
 *
 * FilterException is official exception that raise on the failing of database
 * field filter (validation).
 *
 * Filter (validation) will raise FilterException instead of return succeed or
 * failing condition.
 *
 * The FilterException contains information of field where the exception
 * raise and children exceptions as array of exceptions raise on the same field.
 *
 */
class FilterException extends RuntimeException
{
    /**
     * Database field where exception raise.
     *
     * @var string
     */
    protected $field;

    /**
     * FilterException is nested exception
     * @var array
     */
    protected $children = [];

    /**
     * FilterException constructor
     *
     * @param string     $message
     * @param integer    $code
     * @param Exception $previousException
     */
    public function __construct(
        $message = 'Caught filter error, you should not see this if you dont ' .
        'know what it is. Please report this to webmaster.',
        $code = 0,
        $previousException = null
    ) {
        parent::__construct($message, $code, $previousException);
    }

    /**
     * Get field
     * @return [type] [description]
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set field
     * @param [type] $field [description]
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    public function addChild($child)
    {
        $this->children[] = $child;

        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function hasChildren()
    {
        return !empty($this->children);
    }
}
