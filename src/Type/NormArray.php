<?php namespace Norm\Type;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class NormArray extends Collection
{
    /**
     * {@inheritDoc}
     */
    public function __construct($attributes = null)
    {
        parent::__construct($attributes);

        $this->attributes = array_values($this->attributes);
    }

    /**
     * Add an items to a collection
     *
     * @param mixed $object
     */
    public function add($object)
    {
        $this->attributes[] = $object;
    }

    /**
     * {@inheritDoc}
     */
    public function has($offset)
    {
        return in_array($offset, $this->attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($key, $value)
    {
        if (! is_int($key)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$key] = $value;
        }
    }
}
