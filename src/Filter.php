<?php
namespace Norm;

use Norm\Exception\FilterException;

/**
 * Filter (validation) for database field
 */
class Filter
{
    /**
     * Registries of available filters
     *
     * @var array
     */
    protected static $registries = [];

    public static function register(string $key, callable $factory)
    {
        static::$registries[$key] = $factory;
    }

    public static function get($signature)
    {
        $fn = '';
        $args = [];
        if (is_string($signature)) {
            $signature = explode(':', $signature, 2);
        } elseif (is_callable($signature)) {
            return $signature;
        }

        if (!is_array($signature)) {
            throw new FilterException('Unimplemented filter');
        }

        $fn = $signature[0];
        if (isset($signature[1]) && is_string($signature[1])) {
            $args = explode(',', $signature[1]);
        }

        if (isset(static::$registries[$fn])) {
            $filter = static::$registries[$fn](...$args);
        } else {
            $filterConstructor = '\\Norm\\Filter\\' . $fn;
            $filter = new $filterConstructor($args);
        }

        return $filter;
    }
}
