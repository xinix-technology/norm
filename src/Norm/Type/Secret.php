<?php namespace Norm\Type;

use JsonKit\JsonSerializer;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class Secret implements JsonSerializer
{
    /**
     * Raw value
     *
     * @var mixed
     */
    protected $value;

    /**
     * Class constructor.
     *
     * @param [type] $val [description]
     */
    public function __construct($val)
    {
        $this->value = $val;
    }

    /**
     * Overloading method to convert this implementation to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Perform serialization of this implementation.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return '';
    }

    /**
     * Marshal single object from norm to the proper data accepted by data source. Sometimes data source expects object to be persisted to it in specific form, this method will transform associative array from Norm into this specific form.
     *
     * @see \Norm\Connection::unmarshall()
     *
     * @return mixed Friendly data source object
     */
    public function marshall()
    {
        return $this->value;
    }
}
