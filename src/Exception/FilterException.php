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
 * @copyright   2015 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm
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
 * The FilterException contains information of field context where the exception
 * raise and children exceptions as array of exceptions raise on the same field context.
 *
 */
class FilterException extends RuntimeException
// extends BonoException implements INotifiedException
{
    /**
     * Database field context where exception raise.
     *
     * @var string
     */
    protected $context;

    /**
     * Arguments of the implementation.
     *
     * @var array
     */
    protected $args;

    /**
     * Format of message thrown by exception.
     *
     * @var string
     */
    protected $formatMessage;

    /**
     * FilterException is nested exception
     * @var array
     */
    protected $children = array();

    /**
     * Factory method to create new exception
     *
     * @param  string $message Message of new exception
     *
     * @return Norm\Exception\FilterException
     */
    public static function create($context, $message)
    {
        $o = new static($message);

        return $o->context($context);
    }

    /**
     * Class constructor
     *
     * @param string     $message
     * @param integer    $code
     * @param Exception $previousException
     */
    public function __construct($message = 'Caught filter error, you should not see this if you dont know what it is. Please report this to webmaster.', $code = 0, $previousException = null)
    {
        $this->formatMessage = $message;

        // $this->setStatus(400);

        parent::__construct($message, $code, $previousException);
    }

    /**
     * Get context
     * @return [type] [description]
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set context
     * @param [type] $context [description]
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Set field context of exception
     *
     * @param string $context The field context
     *
     * @return FilterException return self object to be chained
     */
    public function context($context = null)
    {
        if (func_num_args() === 0) {
            return $this->getContext();
        }

        $this->setContext($context);

        return $this;
    }

    /**
     * Get the arrguments passed and build message by them.
     *
     * @return Norm\Exception\FilterException
     */
    public function withArgs()
    {
        $this->args = func_get_args();

        $params = array_merge(array($this->formatMessage), $this->args);
        $this->message = call_user_func_array('sprintf', $params);

        return $this;
    }

    public function setChildren($children)
    {
        $this->children = $children;
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
