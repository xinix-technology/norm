<?php
namespace Norm\Type;

use DateTimeZone;
use DateTime as PhpDateTime;
use JsonKit\JsonSerializer;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2016 PT Sagara Xinix Solusitama
 * @link      http://sagara.id/p/product Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class DateTime extends PhpDateTime implements JsonSerializer, Marshallable
{
    protected $serverTz;

    public function __construct($dt = null, $tz = null)
    {
        $this->serverTz = new DateTimeZone(date_default_timezone_get());

        if (is_string($tz)) {
            $tz = new DateTimeZone($tz);
        }

        switch (func_num_args()) {
            case 0:
                return parent::__construct();
            case 1:
                return parent::__construct($dt);
        }

        return parent::__construct($dt, $tz);
    }

    public function serverFormat($format)
    {
        $original = $this->getTimezone();
        $this->setTimezone($this->serverTz);
        $result = $this->format($format);
        $this->setTimezone($original);
        return $result;
    }

    /**
     * Perform serialization of this implementation.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /**
     * Overloading method to convert this implementation to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->serverFormat('c');
    }

    public function marshall()
    {
        return $this->format('c');
    }

    public function __debugInfo()
    {
        return [
            'client' => $this->format('c'),
            'server' => $this->serverFormat('c'),
        ];
    }
}
