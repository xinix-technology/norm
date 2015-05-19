<?php namespace Norm;

/**
 * Base class for hookable implementation
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
abstract class Hookable
{
    /**
     * Hooks list
     *
     * @var array
     */
    protected $hooks = array();

    /**
     * Assign hook
     *
     * @param string $name     The hook name
     * @param mixed  $callable A callable object
     * @param int    $priority The hook priority; 0 = high, 10 = low
     */
    public function hook($name, $callable, $priority = 10)
    {
        if (!isset($this->hooks[$name])) {
            $this->hooks[$name] = array(array());
        }

        if (is_callable($callable)) {
            $this->hooks[$name][(int) $priority][] = $callable;
        }
    }

    /**
     * Invoke hook
     *
     * @param string $name    The hook name
     * @param mixed  $hookArg (Optional) Argument for hooked functions
     */
    public function applyHook($name, $hookArg = null)
    {
        if (!isset($this->hooks[$name])) {
            $this->hooks[$name] = array(array());
        }

        if (!empty($this->hooks[$name])) {
            // Sort by priority, low to high, if there's more than one priority
            if (count($this->hooks[$name]) > 1) {
                ksort($this->hooks[$name]);
            }

            foreach ($this->hooks[$name] as $priority) {
                if (!empty($priority)) {
                    foreach ($priority as $callable) {
                        call_user_func($callable, $hookArg);
                    }
                }
            }
        }
    }

    /**
     * Invoke filter
     *
     * @param string $name      The hook name
     * @param mixed  $filterArg (Optional) Argument for hooked functions
     *
     * @return mixed
     */
    public function applyFilter($name, $filterArg = null)
    {
        if (!isset($this->hooks[$name])) {
            $this->hooks[$name] = array(array());
        }

        if (!empty($this->hooks[$name])) {
            // Sort by priority, low to high, if there's more than one priority
            if (count($this->hooks[$name]) > 1) {
                ksort($this->hooks[$name]);
            }
            foreach ($this->hooks[$name] as $priority) {
                if (!empty($priority)) {
                    foreach ($priority as $callable) {
                        $filterArg = call_user_func($callable, $filterArg);
                    }
                }
            }
        }

        return $filterArg;
    }

    /**
     * Get hook listeners. Return an array of registered hooks. If `$name` is a valid hook name, only the listeners attached to that hook are returned. Else, all listeners are returned as an associative array whose keys are hook names and whose values are arrays of listeners.
     *
     * @param string $name A hook name (Optional)
     *
     * @return array|null
     */
    public function getHooks($name = null)
    {
        if (!is_null($name)) {
            return isset($this->hooks[(string) $name]) ? $this->hooks[(string) $name] : null;
        } else {
            return $this->hooks;
        }
    }

    /**
     * Clear hook listeners. Clear all listeners for all hooks. If `$name` is a valid hook name, only the listeners attached to that hook will be cleared.
     *
     * @param string $name A hook name (Optional)
     */
    public function clearHooks($name = null)
    {
        if (!is_null($name) && isset($this->hooks[(string) $name])) {
            $this->hooks[(string) $name] = array(array());
        } else {
            foreach ($this->hooks as $key => $value) {
                $this->hooks[$key] = array(array());
            }
        }
    }
}
