<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Connection;
use Norm\Collection;
use Norm\Schema\NBool;
use Norm\Schema;

abstract class AbstractSchemaTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->repository = new Repository();
        $this->repository->singleton(Connection::class, $this->getMock(Connection::class));
        $collection = $this->repository->resolve(Collection::class, [
            'options' => ['name' => 'Foo']
        ]);
        $this->repository->singleton(Collection::class, $collection);
        $schema = $this->repository->resolve(Schema::class);
        $this->repository->singleton(Schema::class, $schema);
    }
}