<?php
namespace Norm\Test\Type;

use PHPUnit\Framework\TestCase;
use Norm\Type\ArrayList;

class ArrayListTest extends TestCase
{
    protected $al;

    public function setUp()
    {
        $this->al = new ArrayList();
        $this->al->add('paw');
    }

    public function testConstruct()
    {
        $al = new ArrayList();

        $this->assertEquals(0, count($al));
    }

    public function testAdd()
    {
        $al = new ArrayList();

        $al->add('foo');

        $this->assertEquals(1, count($al));

        $al->add('bar');

        $this->assertEquals(2, count($al));
    }

    public function testHas()
    {
        $this->assertTrue($this->al->has('paw'));
    }

    public function testSet()
    {
        $this->al[] = 'xxx';

        $this->assertEquals('xxx', $this->al[1]);

        $this->al['zzz'] = 'zzz';

        $this->assertEquals('zzz', $this->al[2]);

        $this->al[10] = 'yyy';

        $this->assertEquals('yyy', $this->al[10]);
    }
}
