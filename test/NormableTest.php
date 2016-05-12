<?php
namespace Norm\Test;

use Norm\Normable;
use Norm\Repository;
use Norm\Exception\NormException;
use PHPUnit_Framework_TestCase;

class NormableTest extends PHPUnit_Framework_TestCase
{
    public function testGetRepository()
    {
        $repository = $this->getMock(Repository::class);
        $normable = $this->getMockForAbstractClass(Normable::class, [$repository]);
        $this->assertEquals($normable->getRepository(), $repository);
    }
    // public function testGetAttribute()
    // {
    //     $repository = $this->getMock(Repository::class);
    //     $repository->expects($this->once())->method('getAttribute')->will($this->returnValue('bar'));

    //     $normable = $this->getMockForAbstractClass(Normable::class, [$repository]);
    //     $this->assertEquals($normable->getAttribute('foo'), 'bar');
    // }

    // public function testTranslate()
    // {
    //     $repository = $this->getMock(Repository::class);
    //     $repository->expects($this->once())->method('translate');

    //     $normable = $this->getMockForAbstractClass(Normable::class, [$repository]);
    //     $normable->translate('foo');
    // }

    // public function testRender()
    // {
    //     $repository = $this->getMock(Repository::class);
    //     $repository->expects($this->once())->method('render');

    //     $normable = $this->getMockForAbstractClass(Normable::class, [$repository]);
    //     $normable->render('foo');
    // }

    public function testFactory()
    {
        $repository = $this->getMock(Repository::class);
        $repository->expects($this->once())->method('factory');

        $normable = $this->getMockForAbstractClass(Normable::class, [$repository]);
        $normable->factory('Foo');
    }
}