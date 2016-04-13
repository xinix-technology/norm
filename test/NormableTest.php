<?php
namespace Norm\Test;

use Norm\Normable;
use Norm\Repository;
use PHPUnit_Framework_TestCase;

class NormableTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    protected $normable;

    public function setUp()
    {
        $this->repository = new Repository();
        $this->normable = $this->getMock(Normable::class, [ 'factory' ], [
            $this->repository,
        ]);
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(Normable::class, $this->normable);
    }

    public function testGetAttribute()
    {
        $this->repository->setAttribute('foo', 'bar');

        $this->assertEquals($this->normable->getAttribute('foo'), 'bar');
    }

    public function testTranslate()
    {
        $this->assertEquals($this->normable->translate('Foo'), 'Foo');
    }

    public function testRender()
    {
        $this->repository->setRenderer(function() {
            return 'Foo';
        });
        $this->assertEquals($this->normable->render('foo'), 'Foo');
    }
}