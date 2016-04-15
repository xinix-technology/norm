<?php
namespace Norm\Type;

use DateTimeZone;
use DateTime as NDateTime;
use JsonKit\JsonSerializer;
use Norm\Repository;

/**
 * Collection abstract class.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2016 PT Sagara Xinix Solusitama
 * @link      http://sagara.id/p/product Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class DateTime extends NDateTime implements JsonSerializer
{
    protected $repository;

    function __construct(Repository $repository, $t = null, $tz = null)
    {
        $this->repository = $repository;
        switch(func_num_args()) {
            case 1:
                return parent::__construct();
            case 2:
                return parent::__construct($t);
            default:
                return parent::__construct($t, $tz);
        }
    }

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
        return $this->__toString();
        // if (null !== $this->repository->getAttribute('timezone')) {
        //     $this->setTimezone(new DateTimeZone($this->repository->getAttribute('timezone')));
        // }

        // return $this->format('c');
    }

    /**
     * Overloading method to convert this implementation to a string.
     *
     * @return string
     */
    public function __toString()
    {

        if (null !== $this->repository->getAttribute('timezone')) {
            $this->setTimezone(new DateTimeZone($this->repository->getAttribute('timezone')));
        }

        return $this->format('c');
    }
}
