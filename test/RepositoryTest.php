<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use Norm\Schema\NString;
use Norm\Exception\NormException;
use ROH\Util\Injector;

class NormTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->injector = new Injector();
    }

    public function testConnectionAddAndGet()
    {
        $repository = new Repository();
        $this->assertNull($repository->getConnection());

        $connection = $this->getMockForAbstractClass(Connection::class, [ $repository ]);

        $this->assertEquals($repository->getConnection(), $connection);

        try {
            $repository->getConnection(33);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Connection id must be string') {
                throw $e;
            }
        }
    }

    public function testAttributesSetUnsetAndGet()
    {
        $repository = new Repository([ 'foo' => 'bar' ]);

        $this->assertEquals($repository->getAttribute('foo'), 'bar');

        $repository->setAttribute('foo', null);
        $repository->setAttribute('baz', 'baz');

        $this->assertEquals($repository->getAttribute('foo'), null);
        $this->assertEquals($repository->getAttribute('baz'), 'baz');
    }

    public function testFactory()
    {
        $repository = new Repository();

        try {
            $collection = $repository->factory('Foo');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Undefined connection to create collection') {
                throw $e;
            }
        }

        $this->getMockForAbstractClass(Connection::class, [ $repository ]);

        $collection = $repository->factory('Foo');
        $this->assertInstanceOf(Collection::class, $collection);

        $collection = $repository('Foo');
        $this->assertInstanceOf(Collection::class, $collection);

        try {
            $collection = $repository->factory(88);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Collection and Connection Id must be string') {
                throw $e;
            }
        }

        $repository->setDefault([
            'fields' => [
                [ NString::class, [ 'name' => 'foo' ]],
            ]
        ]);

        $this->assertInstanceOf(NString::class, $repository->factory('Bar')->getField('foo'));
    }

    public function testUseConnection()
    {
        $repository = new Repository();
        $connection1 = $this->getMockForAbstractClass(Connection::class, [$repository, 'con1']);
        $connection2 = $this->getMockForAbstractClass(Connection::class, [$repository, 'con2']);

        $this->assertEquals($repository->getConnection(), $connection1);
        $repository->useConnection('con2');
        $this->assertEquals($repository->getConnection(), $connection2);

        try {
            $repository->useConnection('con3');
            $this->fail('Must throw error');
        } catch (NormException $e) {}
    }

    public function testTranslate()
    {
        $repository = new Repository();
        $this->assertEquals($repository->translate('Foo'), 'Foo');

        try {
            $repository->translate(99);
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Message to translate must be string') {
                throw $e;
            }
        }

        $repository = (new Repository())->setTranslator(function() { return 'Bar'; });
        $this->assertEquals($repository->translate('Foo'), 'Bar');

        $repository->setTranslator(function () { return 'Baz'; });
        $this->assertEquals($repository->translate('Foo'), 'Baz');
    }

    public function testAddAndGetResolvers()
    {
        $repository = new Repository();
        $this->getMockForAbstractClass(Connection::class, [$repository]);

        $hit1 = false;
        $hit2 = false;
        $repository->addResolver(function() use (&$hit1) { $hit1 = true; return []; });
        $repository->addResolver(function() use (&$hit2) { $hit2 = true; });
        $this->assertEquals(count($repository->getResolvers()), 2);

        $repository->factory('Foo');

        $this->assertEquals($hit1, true);
        $this->assertEquals($hit2, false);
    }

    public function testRender()
    {
        $repository = new Repository();

        $result = $repository->render('__norm__/nbool/input', [
            'self' => [
                'name' => 'foo',
            ],
            'value' => false,
        ]);

        if (strpos($result, 'name="foo"') < 0 || strpos($result, 'selected>False<') < 0) {
            throw new \Exception('Mismatch result');
        }

        try {
            $repository->render(99);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Template to render must be string') {
                throw $e;
            }
        }

        try {
            $repository->render('not-found');
            $this->fail('Must not here');
        } catch (NormException $e) {
            if (strpos($e->getMessage(), 'Template not found') < 0) {
                throw $e;
            }
        }

        $repository->setRenderer(function() { return 'foo'; });
        $this->assertEquals($repository->render('unmatter'), 'foo');
    }

    public function testDebugInfo()
    {
        $repository = new Repository();
        $this->assertEquals(array_keys($repository->__debugInfo()), ['connections', 'use']);
    }
}
