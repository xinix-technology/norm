<?php
namespace Norm\Test\Observer;

use PHPUnit_Framework_TestCase;
use Norm\Collection;
use Norm\Connection;
use Norm\Repository;
use Norm\Adapter\Memory;

abstract class AbstractObserverTest extends PHPUnit_Framework_TestCase
{
    // public function __construct()
    // {
    //     date_default_timezone_set('UTC');
    // }

    public function getCollection($observer)
    {
        $repository = new Repository();
        $connection = new Memory($repository);
        $collection = new Collection($connection, 'Foo');
        $collection->observe($observer);
        return $collection;
    }
}
