<?php namespace Norm\Type;

use Norm\Norm;
use DateTimeZone;
use DateTime as DT;
use JsonKit\JsonSerializer;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class NDate extends DT implements JsonSerializer
{
    /**
     * Formatting date time implementation.
     *
     * @param string $format format
     *
     * @return mixed
     */
    public function localFormat($format)
    {
        return $this->tzFormat($format);
    }

    /**
     * Formatting by timezone
     *
     * @param string $format
     * @param string $tz
     *
     * @return mixed
     */
    public function tzFormat($format, $tz = null)
    {
        if (isset($tz)) {
            $this->setTimezone(new DateTimeZone($tz));
        }

        return $this->format($format);
    }

    /**
     * Perform serialization of this implementation.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        if ($tz = Norm::options('tz')) {
            $this->setTimezone(new DateTimeZone($tz));
        }

        return $this->format('c');
    }

    /**
     * Overloading method to convert this implementation to a string.
     *
     * @return string
     */
    public function __toString()
    {
        if ($tz = Norm::options('tz')) {
            $this->setTimezone(new DateTimeZone($tz));
        }

        return $this->format('c');
    }
}
