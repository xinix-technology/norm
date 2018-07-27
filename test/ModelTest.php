<?php
// namespace Norm\Test;

// use Norm\Exception\NormException;
// use Norm\Model;
// use Norm\Collection;
// use Norm\Connection;
// use Norm\Repository;
// use Norm\Schema\NField;
// use PHPUnit\Framework\TestCase;
// use ROH\Util\Injector;

// class ModelTest extends TestCase
// {
//     public function setUp()
//     {
//         $this->injector = new Injector();
//         $this->injector->singleton(Repository::class, $this->createMock(Repository::class));
//         $this->injector->singleton(Connection::class, $this->getMockForAbstractClass(Connection::class, [$this->injector->resolve(Repository::class)]));
//         $this->injector->singleton(Collection::class, $this->getMock(Collection::class, null, [ $this->injector->resolve(Connection::class), 'Foo' ]));
//     }

//     public function testSetUnsetHas()
//     {
//         $model = $this->injector->resolve(Model::class);
//         try {
//             $model->set('$id', 10);
//             $this->fail('Must not here');
//         } catch (NormException $e) {
//             if ($e->getMessage() !== 'Restricting model to set for $id.') {
//                 throw $e;
//             }
//         }

//         $model = $this->injector->resolve(Model::class, [ 'attributes' => [ '$id' => 1 ] ]);
//         $this->assertEquals($model['$id'], 1);

//         $model->set([
//             'foo' => 'bar',
//             'bar' => 'baz',
//         ]);
//         $this->assertEquals($model['foo'], 'bar');
//         $this->assertEquals($model['bar'], 'baz');
//         $model->set('foo', 'baz');
//         $this->assertEquals($model['foo'], 'baz');
//         $model['bar'] = 'foo';
//         $this->assertEquals($model['bar'], 'foo');
//         unset($model['bar']);
//         $this->assertEquals($model['bar'], null);
//         $this->assertFalse(isset($model['bar']));
//         $this->assertTrue($model->has('foo'));

//         $this->assertEquals($model->dump(), ['$id' => 1, 'foo' => 'baz']);
//     }

//     public function testReader()
//     {
//         $fooField = $this->getMockForAbstractClass(NField::class, [ $this->injector->resolve(Collection::class), 'foo' ]);
//         $fooField->setReader(function () {
//             return 'hijacked!';
//         });
//         $this->injector->resolve(Collection::class)->addField($fooField);

//         $model = $this->injector->resolve(Model::class, [ 'attributes' => [
//             '$id' => 1,
//             'foo' => 'bar',
//             'bar' => 'baz',
//         ]]);
//         $this->assertEquals($model['foo'], 'hijacked!');
//     }

//     public function testClear()
//     {
//         $model = $this->injector->resolve(Model::class, [ 'attributes' => [
//             'foo' => 'foo',
//             'bar' => 'bar',
//         ]]);
//         $this->assertNotNull($model->clear('bar')['foo']);
//         $this->assertNull($model->clear()['foo']);
//         try {
//             $model->clear('$id');
//         } catch (NormException $e) {
//             if ($e->getMessage() !== 'Restricting model to clear for $id.') {
//                 throw $e;
//             }
//         }

//         unset($model->set('foo', 'foo')['foo']);
//         $this->assertNull($model['foo']);
//     }

//     public function testFilter()
//     {
//         $collection = $this->getMock(Collection::class, ['filter'], [ $this->injector->resolve(Connection::class), 'Foo' ]);
//         $collection->expects($this->once())->method('filter');

//         $model = $this->injector->resolve(Model::class, [
//             'collection' => $collection,
//             'attributes' => ['foo' => 'bar']
//         ]);
//         $model->filter();
//     }

//     public function testPrevious()
//     {
//         $model = $this->injector->resolve(Model::class, [ 'attributes' => ['foo' => 'bar']]);
//         $this->assertEquals($model->previous(), ['foo' => 'bar']);
//         $this->assertEquals($model->previous('foo'), 'bar');
//     }

//     public function testStatus()
//     {
//         $model = $this->injector->resolve(Model::class, [ 'attributes' => ['foo' => 'bar']]);
//         $this->assertEquals($model->isRemoved(), false);
//     }

//     public function testToArrayAndDebugInfo()
//     {
//         $model = $this->injector->resolve(Model::class, [ 'attributes' => ['foo' => 'bar']]);
//         $this->assertEquals($model->toArray(), $model->__debugInfo());
//     }

//     public function testToArray()
//     {
//         $model = $this->injector->resolve(Model::class, [ 'attributes' => [
//             '$id' => 1,
//             '$hidden' => 'yes',
//             'foo' => 'bar'
//         ]]);

//         $this->assertEquals($model->toArray(), ['$id' => 1, '$hidden' => 'yes', 'foo' => 'bar', '$type' => 'Foo']);
//         $this->assertEquals($model->toArray(Model::FETCH_ALL), ['$id' => 1, '$hidden' => 'yes', 'foo' => 'bar', '$type' => 'Foo']);
//         $this->assertEquals($model->toArray(Model::FETCH_HIDDEN), ['$id' => 1, '$hidden' => 'yes', '$type' => 'Foo']);
//         $this->assertEquals($model->toArray(Model::FETCH_PUBLISHED), ['foo' => 'bar']);
//         $this->assertEquals($model->toArray(Model::FETCH_RAW), ['foo' => 'bar', '$hidden' => 'yes']);
//     }

//     public function testJsonSerialize()
//     {
//         $model = $this->injector->resolve(Model::class, [ 'attributes' => [
//             '$id' => 1,
//             'foo' => 'bar'
//         ]]);

//         $this->assertEquals($model->jsonSerialize()['foo'], 'bar');
//     }

//     public function testSaveAndRemove()
//     {
//         $collection = $this->getMock(Collection::class, ['save', 'remove'], [ $this->injector->resolve(Connection::class), 'Foo' ]);
//         $collection
//             ->expects($this->once())
//             ->method('save')
//             ->will($this->returnCallback(function ($model) {
//                 $model->sync($model->dump());
//             }));
//         $collection
//             ->expects($this->once())
//             ->method('remove')
//             ->will($this->returnCallback(function ($model) {
//                 $model->reset(true);
//             }));

//         $model = $this->injector->resolve(Model::class, [
//             'collection' => $collection,
//             'attributes' => [
//                 '$id' => 1,
//                 'foo' => 'bar'
//             ]
//         ]);
//         $model->save();
//         $this->assertFalse($model->isNew());

//         $model->remove();
//         $this->assertTrue($model->isRemoved());
//     }

//     public function testFormat()
//     {
//         $model = $this->injector->resolve(Model::class, [ 'attributes' => [
//             '$id' => 1,
//             'foo' => 'bar',
//             'bar' => 'baz',
//         ]]);
//         try {
//             $model->format();
//             $this->fail('Must not here');
//         } catch (NormException $e) {
//             if ($e->getMessage() !== 'Cannot format undefined fields') {
//                 throw $e;
//             }
//         }

//         $this->injector->resolve(Collection::class)
//             ->addField($this->getMock(NField::class, null, [
//                 $this->injector->resolve(Collection::class),
//                 'foo'
//             ]));
//         $this->assertEquals($model->format(), 'bar');
//         $this->assertEquals($model->format('plain'), 'bar');
//         $this->assertEquals($model->format('plain', 'bar'), 'baz');
//     }
// }
