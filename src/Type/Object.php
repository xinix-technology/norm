<?php
namespace Norm\Type;

use ROH\Util\Collection;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2015 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class Object extends Collection
{
    /**
     * {@inheritDoc}
     */
    public function has($o)
    {
        $attrs = array_values($this->attributes);

        return in_array($o, $attrs);
    }
}
