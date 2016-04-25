<?php
namespace Norm\Test;

use Norm\Normable;
use Norm\Repository;
use PHPUnit_Framework_TestCase;

class NormableTest extends PHPUnit_Framework_TestCase
{
    public function testGetAttribute()
    {
        $parent = $this->getMock(Normable::class);
        $parent->expects($this->once())->method('getAttribute')->will($this->returnValue('bar'));
        $normable = $this->getMockForAbstractClass(Normable::class, [$parent]);

        $this->assertEquals($normable->getAttribute('foo'), 'bar');
    }

    public function testTranslate()
    {
        $parent = $this->getMock(Normable::class);
        $parent->expects($this->once())->method('translate');
        $normable = $this->getMockForAbstractClass(Normable::class, [$parent]);
        $normable->translate('foo');
    }

    public function testRender()
    {
        $parent = $this->getMock(Normable::class);
        $parent->expects($this->once())->method('render');
        $normable = $this->getMockForAbstractClass(Normable::class, [$parent]);
        $normable->render('foo');
    }

    public function testFactory()
    {
        $parent = $this->getMock(Normable::class);
        $parent->expects($this->once())->method('factory');
        $normable = $this->getMockForAbstractClass(Normable::class, [$parent]);
        $normable->factory('Foo');
    }
}