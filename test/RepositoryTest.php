<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use Norm\Exception\NormException;

class NormTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $repository = new Repository([
            'attributes' => [
                'foo' => 'bar',
            ],
            'collections' => [
                'default' => [],
                'resolvers' => [
                    function () {

                    }
                ],
            ],
            'renderer' => function () {},
            'translator' => function () {},
        ]);

        $this->assertEquals($repository->getAttribute('foo'), 'bar');
    }

    public function testGetConnection()
    {
        $repository = new Repository();
        try {
            $repository->getConnection(33);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Connection id must be string') {
                throw $e;
            }
        }
    }

    public function testFactory()
    {
        $connection = $this->getMock(Connection::class);
        $repository = new Repository([
            'connections' => [
                $connection,
            ],
        ]);

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

        $repository = new Repository();
        try {
            $collection = $repository->factory('Foo');
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'No connection available to create collection') {
                throw $e;
            }
        }
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

        $repository = new Repository([
            'translator' => function() { return 'Bar'; }
        ]);
        $this->assertEquals($repository->translate('Foo'), 'Bar');

        $repository->setTranslator(function () { return 'Baz'; });
        $this->assertEquals($repository->translate('Foo'), 'Baz');
    }

    public function testRender()
    {
        $repository = new Repository();

        $result = $repository->render('__norm__/boolean/input', [
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
            $repository->render('not-found.php');
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
        $connection = $this->getMock(Connection::class);
        $repository = new Repository([
            'connections' => [
                $connection,
            ],
        ]);

        $this->assertEquals(count($repository->__debugInfo()['connections']), 1);
    }
}
