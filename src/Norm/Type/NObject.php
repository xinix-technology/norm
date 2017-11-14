<?php namespace Norm\Type;

use stdClass;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class NObject extends Collection
{
    /**
     * Convert this class to a standard object.
     *
     * @return \stdClass
     */
    public function toObject()
    {
        $obj = new stdClass();

        if (! empty($this->attributes)) {
            foreach ($this->attributes as $key => $value) {
                $obj->$key = $value;
            }
        }

        return $obj;
    }

    /**
     * {@inheritDoc}
     */
    public function has($o)
    {
        $attrs = array_values($this->attributes);

        return in_array($o, $attrs);
    }
}
