<?php
// namespace Norm\Test;

// use PHPUnit\Framework\TestCase;
// use Norm\Repository;
// use Norm\Connection;
// use Norm\Collection;
// use Norm\Cursor;
// use Norm\Model;
// use Norm\Exception\NormException;
// use ROH\Util\Injector;
// use Norm\Schema\NField;

// class CollectionTest extends TestCase
// {
//     public function setUp()
//     {
//         $this->injector = new Injector();
//         $this->repository = new Repository([], $this->injector);
//         $this->injector->singleton(
//             Connection::class,
//             $this->getMockForAbstractClass(Connection::class, [ $this->repository ])
//         );
//     }

//     public function testConstructAndObserve()
//     {
//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $this->assertEquals($collection->getId(), 'foo');
//         $this->assertEquals($collection->getName(), 'Foo');

//         $collection = $this->injector->resolve(Collection::class, ['name' => ['Foo', 'bar']]);
//         $this->assertEquals($collection->getId(), 'bar');
//         $this->assertEquals($collection->getName(), 'Foo');

//         try {
//             $collection = $this->injector->resolve(Collection::class, ['name' => 11]);
//             $this->fail('Must not here');
//         } catch (NormException $e) {
//             if ($e->getMessage() !== 'Collection name must be string') {
//                 throw $e;
//             }
//         }
//     }

//     public function testObserve()
//     {
//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $collection->observe([
//             'initialize' => function ($context) use (&$hit) {
//                 $hit = true;
//             },
//             'save' => function ($context, $next) {
//             },
//         ]);
//         $this->assertTrue($hit);

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $observer = $this->getMock(stdClass::class, ['initialize', 'save']);
//         $observer->expects($this->once())->method('initialize');
//         $collection->observe($observer);

//         try {
//             $collection->observe(0);
//             $this->fail('Must not here');
//         } catch (NormException $e) {
//             if ($e->getMessage() !== 'Observer must be array or object') {
//                 throw $e;
//             }
//         }
//     }

//     public function testDebugInfoAndGetters()
//     {
//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $info = $collection->__debugInfo();
//         $this->assertEquals($info['id'], 'foo');
//         $this->assertEquals($info['name'], 'Foo');

//         $this->assertEquals($collection->getId(), 'foo');
//         $this->assertEquals($collection->getName(), 'Foo');
//     }

//     public function testAttach()
//     {
//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $result = $collection->attach([
//             '$id' => 1,
//             'foo' => 'bar',
//             'bar' => 'baz',
//         ]);

//         $this->assertInstanceOf(Model::class, $result);
//         $this->assertEquals($result['foo'], 'bar');
//     }

//     public function testFindAndFindOne()
//     {
//         $this->injector->resolve(Connection::class)
//             ->method('read')->will($this->returnValue(['foo' => 'bar']));

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//         $result = $collection->find();
//         $this->assertInstanceOf(Cursor::class, $result);

//         $result = $collection->findOne(10);
//         $this->assertInstanceOf(Model::class, $result);
//     }

//     public function testNewInstanceSaveAndRemove()
//     {
//         $connection = $this->injector->resolve(Connection::class);
//         $connection->method('persist')->will($this->returnValue(['$id' => 1, 'foo' => 'bar']));
//         $connection->expects($this->once())->method('remove');

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//         $model = $collection->newInstance();
//         $this->assertInstanceOf(Model::class, $model);

//         $model->set('foo', 'bar');
//         $collection->save($model);
//         $this->assertFalse($model->isNew());

//         $collection->save($model, ['observer' => false]);
//         $this->assertFalse($model->isNew());

//         $collection->remove($model);
//     }

//     public function testRemoveModelWithoutObserve()
//     {
//         $connection = $this->injector->resolve(Connection::class);
//         $connection->expects($this->once())->method('remove');

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $model = $collection->newInstance();
//         $collection->remove($model, ['observer' => false]);
//     }

//     public function testRemoveAll()
//     {
//         $connection = $this->injector->resolve(Connection::class);
//         $connection->expects($this->once())->method('remove');

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $collection->remove();
//     }

//     public function testRemoveCursor()
//     {
//         $connection = $this->injector->resolve(Connection::class);
//         $connection->expects($this->once())->method('remove');

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $cursor = $collection->find(['foo' => 'bar']);
//         $collection->remove($cursor);
//     }

//     public function testDelegateCursorMethods()
//     {
//         $connection = $this->injector->resolve(Connection::class);
//         $connection->expects($this->once())->method('distinct');
//         $connection->expects($this->once())->method('size');
//         $connection->expects($this->once())->method('read');

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//         $cursor = new Cursor($collection);
//         $collection->distinct($cursor, 'foo');
//         $collection->size($cursor);
//         $collection->read($cursor);
//     }

//     public function testAddAndGetFormatterAndFormat()
//     {
//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//         // $model = $this->getMock(Model::class, [], [ $collection ]);

//         // function
//         $formatter = function () {
//             return 'function foo bar';
//         };
//         $collection->addFormatter('plain', $formatter);
//         $this->assertEquals($collection->getFormatter('plain'), $formatter);
//         $this->assertEquals('function foo bar', $collection->format('plain', []));

//         // string variably format
//         $formatter = '{foo}-{bar}';
//         $collection->addFormatter('plain', $formatter);
//         $this->assertEquals('foox-barx', $collection->format('plain', ['foo' => 'foox', 'bar' => 'barx']));

//         // string static format
//         $formatter = '{bar}';
//         $collection->addFormatter('plain', $formatter);
//         $this->assertEquals('baz', $collection->format('plain', ['bar' => 'baz']));

//         // rejected format
//         try {
//             $collection->addFormatter('plain', 99);
//             $this->fail('Must not here');
//         } catch (NormException $e) {
//             if ($e->getMessage() !== 'Formatter should be callable or string format') {
//                 throw $e;
//             }
//         }

//         try {
//             $collection->getFormatter(88);
//         } catch (NormException $e) {
//             if ($e->getMessage() !== 'Format key must be string') {
//                 throw $e;
//             }
//         }

//         try {
//             $collection->format('not-found', []);
//         } catch (NormException $e) {
//             if (strpos($e->getMessage(), 'not found') < 0) {
//                 throw $e;
//             }
//         }
//     }

//     public function testFormatTableFieldsAndInputFields()
//     {
//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//         $collection->addField($this->getMockForAbstractClass(NField::class, [$collection, 'foo']));

//         $formatted = $collection->format('tableFields');
//         $this->assertInstanceOf(\Iterator::class, $formatted);

//         $formatted = $collection->format('inputFields');
//         $this->assertInstanceOf(\Iterator::class, $formatted);
//     }

//     public function testGetFields()
//     {
//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//         $this->assertEquals(count($collection->getFields()), 0);

//         $collection->addField($this->getMockForAbstractClass(NField::class, [$collection, 'foo']));
//         $this->assertEquals(count($collection->getFields()), 1);
//     }

//     // public function testAddFieldByMetadata()
//     // {
//     //     $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//     //     $field = $collection->addField([ \Norm\Schema\NString::class, [
//     //         'name' => 'foo',
//     //     ]]);

//     //     $this->assertInstanceOf(NField::class, $field);
//     //     $this->assertInstanceOf(NUnknown::class, $collection->getField('bar'));
//     //     $this->assertEquals($field, $collection->getField('foo'));
//     // }

//     // public function testAddFieldByInstance()
//     // {
//     //     $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//     //     $originalField = $this->getMockForAbstractClass(NField::class, ['foo']);
//     //     $field = $collection->addField($originalField);

//     //     $this->assertEquals($field, $originalField);
//     // }

//     // public function testFormatPlain()
//     // {
//     //     $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);

//     //     try {
//     //         $formatted = $collection->format('plain', []);
//     //         $this->fail('Must not here');
//     //     } catch (NormException $e) {
//     //         if ($e->getMessage() !== 'Cannot format undefined fields') {
//     //             throw $e;
//     //         }
//     //     }

//     //     $field = $this->getMockForAbstractClass(NField::class, [ 'foo' ]);
//     //     $collection->addField($field);

//     //     $model['foo'] = 'bar';
//     //     $formatted = $collection->format('plain', $model);
//     //     $this->assertEquals('bar', $formatted);
//     // }
// }
