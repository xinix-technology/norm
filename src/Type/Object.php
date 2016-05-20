<?php
namespace Norm\Type;

use ROH\Util\Collection;
use JsonKit\JsonKit;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2016 PT Sagara Xinix Solusitama
 * @link      http://sagara.id/p/product Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class Object extends Collection implements Marshallable
{
    /**
     * {@inheritDoc}
     */
    public function has($o)
    {
        $attrs = array_values($this->attributes);

        return in_array($o, $attrs);
    }

    public function marshall()
    {
        return JsonKit::encode($this->toArray());
    }
}
