<?php
namespace Norm\Test\Observer;

use PHPUnit_Framework_TestCase;

use Norm\Collection;
use Norm\Norm;
use Norm\Adapter\Memory;

abstract class AbstractObserverTest extends PHPUnit_Framework_TestCase
{
    public function getCollection($observer)
    {
        $connection = new Memory();
        $repository = new Norm([
            'connections' => [
                "memory" => $connection,
            ],
        ]);

        return (new Collection($repository, [
            'name' => 'Foo',
            'observers' => [
                $observer
            ],
        ]))->withConnection($connection);
    }
}
