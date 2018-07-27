<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NUnsafeString;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

abstract class AbstractTest extends TestCase
{
    public function setUp()
    {
        $repository = $this->createMock(Repository::class);

        $repository->method('render')
            ->will($this->returnCallback(function ($template) {
                return $template;
            }));

        $this->injector = new Injector();
        $this->injector->singleton(Repository::class, $repository);

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collection->method('getRepository')
            ->willReturn($repository);

        $this->injector->singleton(Collection::class, $collection);
    }
}
