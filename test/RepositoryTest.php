<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use Norm\Exception\NormException;
use ROH\Util\Injector;

class NormTest extends PHPUnit_Framework_TestCase
{
    public function testConnectionAddAndGet()
    {
        $repository = new Repository();
        $this->assertNull($repository->getConnection());

        $connection = $this->getMockForAbstractClass(Connection::class);
        $repository->addConnection($connection);
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
        $repository = new Repository([], [ 'foo' => 'bar' ]);

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

        $repository->addConnection($this->getMockForAbstractClass(Connection::class));

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

        $hit = false;
        $inject = false;

        $injector = new Injector();
        $injector->delegate(Collection::class, function ($args) use (&$inject) {
            if (isset($args['foo']) && $args['foo'] === 'bar' &&
                isset($args['baz']) && $args['baz'] === 'bar') {
                $inject = true;
            }
        });

        $repository = (new Repository([
                $this->getMockForAbstractClass(Connection::class),
            ]))
            ->setDefault([
                'foo' => 'bar',
            ])
            ->addResolver(function() use (&$hit) {
                $hit = true;
                return [
                    'baz' => 'bar',
                ];
            })
            ->setInjector($injector);
        $collection = $repository->factory('Foo');

        $this->assertTrue($hit);
        $this->assertTrue($inject);
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
        $repository = new Repository([
            $this->getMockForAbstractClass(Connection::class),
        ]);

        $this->assertEquals(count($repository->__debugInfo()['connections']), 1);
    }
}
