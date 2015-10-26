<?php
namespace Norm;

use ROH\Util\Composition;

/**
 * Base class for hookable implementation
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2015 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
abstract class Base
{
    /**
     * Options associative array
     *
     * @var array
     */
    protected $options;

    /**
     * Hooks list
     *
     * @var array
     */
    // protected $hooks = array();
    protected $compositions;

    /**
     * Constructor
     *
     * @param assoc $options
     */
    public function __construct($options = array())
    {
        $this->options = $options;
        $this->compositions = [];
    }

    public function compose($key, $value)
    {
        $this->getComposition($key)
            ->compose($value);

        return $this;
    }

    public function getComposition($key)
    {
        if (!isset($this->compositions[$key])) {
            $this->compositions[$key] = new Composition();
        }

        return $this->compositions[$key];
    }

    public function apply($key, $context = null, $callback = null)
    {
        $composition = $this->getComposition($key);

        if (func_num_args() > 2) {
            $composition->withCore($callback);
        }

        return $composition->apply($context);
    }
}
