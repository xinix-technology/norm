<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Collection;
use Norm\Adapter\Memory;

abstract class ObserverTestCase extends PHPUnit_Framework_TestCase
{
    protected $repository;

    public function __construct()
    {
        date_default_timezone_set('UTC');
    }

    public function setUp()
    {
        $this->repository = new Repository();
        $this->repository->add(new Memory('default'));
    }

    public function getCollection($observer)
    {
        $collection = $this->repository->resolve(Collection::class, [
            'connection' => $this->repository->getConnection(),
            'options' => [
                'name' => 'Foo',
            ],
        ]);

        $collection->observe($observer);

        return $collection;
    }
}
