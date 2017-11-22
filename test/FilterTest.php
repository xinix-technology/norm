<?php
namespace Norm\Test;

use PHPUnit\Framework\TestCase;
use Norm\Repository;
use Norm\Filter;
use Norm\Exception\FilterException;
use Norm\Collection;
use Norm\Connection;
use Norm\Exception\NormException;
use Norm\Exception\FatalException;
use Norm\Exception\SkipException;
use ROH\Util\Collection as UtilCollection;
use ROH\Util\Injector;

class FilterTest extends TestCase
{
    public function setUp()
    {
        $this->injector = new Injector();
        $this->injector->singleton(Repository::class, new Repository());
        $this->injector->singleton(Connection::class, $this->getMockForAbstractClass(Connection::class, [$this->injector->resolve(Repository::class)]));
        $this->injector->singleton(Collection::class, $this->getMock(Collection::class, null, [ $this->injector->resolve(Connection::class), 'Foo' ]));
    }

    public function testDebugInfo()
    {
        $filter = new Filter();

        $this->assertEquals(array_keys($filter->__debugInfo()), []);
    }

    public function testRegister()
    {
        $hit = false;
        $foo = function() use (&$hit) {
            $hit = true;
        };
        Filter::register('foo', $foo);
        $this->assertEquals(Filter::get('foo'), $foo);

        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'foo'
                ]
            ]
        ]);

        $data = [
            'foo' => 'foo',
        ];

        $filter->run($data);
        $this->assertEquals($hit, true);
    }

    public function testFilterChain()
    {
        $filter = function() {};
        $rules = Filter::parseFilterRules([
            'foo' => [
                'filter' => [
                    'a|b:c|d:e,f',
                    $filter,
                ]
            ]
        ]);

        $this->assertEquals(4, count($rules['foo']['filter']));

        $this->assertEquals('a', $rules['foo']['filter'][0][0]);
        $this->assertEquals([], $rules['foo']['filter'][0][1]);

        $this->assertEquals('b', $rules['foo']['filter'][1][0]);
        $this->assertEquals(['c'], $rules['foo']['filter'][1][1]);

        $this->assertEquals('d', $rules['foo']['filter'][2][0]);
        $this->assertEquals(['e', 'f'], $rules['foo']['filter'][2][1]);
        $this->assertEquals($filter, $rules['foo']['filter'][3][0]);
    }

    public function testConstruct()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'a|b:c|d:e,f',
                ]
            ]
        ]);

        $this->assertEquals(count($filter->__debugInfo()['foo']['filter']), 3);

        try {

            $filter = new Filter('test');
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Rules must be array or instance of Schema') {
                throw $e;
            }
        }
    }

    public function testGetLabel()
    {
        $filter = new Filter([
            'foo' => [],
            'bar' => [
                'label' => 'Bar',
            ],
        ]);

        $this->assertEquals($filter->getLabel('foo'), 'Unknown');
        $this->assertEquals($filter->getLabel('bar'), 'Bar');
    }

    public function testRunAllContext()
    {
        $filter = new Filter([
            'paddedstr' => [
                'filter' => [
                    'trim'
                ]
            ]
        ], true);

        $data = [
            'paddedstr' => '   this is str   ',
        ];

        $result = $filter->run($data);

        $this->assertEquals('this is str', $result['paddedstr']);
    }

    public function testRunSingleField()
    {
        $filter = new Filter([
            'paddedstr' => [
                'filter' => [
                    'trim'
                ]
            ],
            'foo' => [
                'filter' => [
                    function() {
                        throw new \Exception('Must not throw this');
                    }
                ]
            ]
        ]);

        $data = [
            'paddedstr' => '   this is str   ',
            'foo' => 'bar',
        ];

        $filter->run($data, 'paddedstr');
    }

    public function testRunSelectiveContext()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'trim'
                ]
            ],
            'bar' => [
                'filter' => [
                    'trim'
                ]
            ]
        ], true);

        $data = [
            'foo' => '   this is foo   ',
            'bar' => '   this is bar   ',
        ];

        $result = $filter->run($data);

        $this->assertNotEquals('   this is foo   ', $result['foo']);
        $this->assertEquals('this is bar', $result['bar']);
    }

    public function testRunFatal()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    function() {
                        throw new FatalException('fatal');
                    }
                ]
            ],
            'bar' => [
                'filter' => [
                    'trim'
                ]
            ]
        ], true);

        $data = [
            'foo' => '   this is foo   ',
            'bar' => '   this is bar   ',
        ];

        try {
            $result = $filter->run($data);
        } catch(FatalException $fe) {
            $this->assertEquals($filter->getErrors(), []);
        }
    }

    public function testRunSkip()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    function() {
                        throw new SkipException('fatal');
                    },
                    'trim',
                ]
            ],
        ]);

        $data = [
            'foo' => '   this is foo   ',
            'bar' => '   this is bar   ',
        ];

        $result = $filter->run($data);
        $this->assertEquals($result['foo'], $data['foo']);
    }

    public function testRunCommonError()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'trim',
                ]
            ],
            'bar' => [
                'filter' => [
                    function() {
                        throw new \Exception('common error');
                    }
                ]
            ]
        ]);

        $data = [
            'foo' => '   this is foo   ',
            'bar' => '   this is bar   ',
        ];

        try {
            $result = $filter->run($data);
            $this->fail('Must not here');
        } catch(FilterException $e) {
        }
    }

    public function testRunIneligibleError()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'oops',
                ]
            ],
        ]);

        $data = [
            'foo' => '   this is foo   ',
            'bar' => '   this is bar   ',
        ];

        try {
            $result = $filter->run($data);
            $this->fail('Must not here');
        } catch(FatalException $e) {
            if ($e->getMessage() !== 'Ineligible filter oops for foo') {
                throw $e;
            }
        }
    }

    public function testFilterRequired()
    {
        $filter = new Filter([
            'foo' => [
                'label' => 'Foo',
                'filter' => [
                    'required'
                ]
            ],
        ], true);

        $filter->run(['foo' => 'not raised']);

        try {
            $filter->run(null);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
            $this->assertEquals('Field Foo is required', $e->getMessage());
        }

        try {
            $filter->run([]);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
        }

        try {
            $filter->run(['foo' => '']);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
        }

        try {
            $filter->run(['foo' => null]);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
        }

        // required with
        $filter = new Filter([
            'foo' => [
                'label' => 'Foo',
                'filter' => [
                    'requiredWith:bar'
                ]
            ],
        ]);
        $filter->setImmediate(true);

        $result = $filter->run([]);
        $result = $filter->run([
            'foo' => 'foo',
            'bar' => 'bar',
        ]);
        try {
            $result = $filter->run([
                'bar' => 'bar',
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {}

        // required without
        $filter = new Filter([
            'foo' => [
                'label' => 'Foo',
                'filter' => [
                    'requiredWithout:bar'
                ]
            ],
        ]);

        $result = $filter->run([
            'foo' => 'foo',
            'bar' => 'bar',
        ]);

        $result = $filter->run([
            'foo' => 'foo',
        ]);

        try {
            $result = $filter->run([]);
            $this->fail('Must not here');
        } catch(FilterException $e) {}
    }

    public function testFilterConfirmed()
    {
        $filter = new Filter([
            'foo' => [
                'label' => 'Foo',
                'filter' => [
                    'confirmed'
                ]
            ],
        ]);

        try {
            $filter->run([
                'foo' => 'foo',
                'foo_confirmation' => 'foox',
            ]);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
            $this->assertEquals('Field Foo must be confirmed', $e->getChildren()[0]->getMessage());
        }

        $result = $filter->run([
            'foo' => 'foo',
            'foo_confirmation' => 'foo',
        ]);

        $this->assertEquals('foo', $result['foo']);

        $result = $filter->run([
            'bar' => 'foo',
        ]);

        $this->assertEquals('', $result['foo']);
    }

    public function testFilterUnique()
    {
        $collection = $this->getMock(Collection::class, ['findOne'], [ $this->injector->resolve(Connection::class), 'Foo']);
        $collection->method('findOne')->will($this->returnCallback(function($criteria) {
            return null;
        }));
        $collection->addField($this->getMockForAbstractClass(\Norm\Schema\NField::class, [$collection, 'foo', 'unique']));
        $filter = new Filter($collection, true);
        $result = $filter->run([
            'foo' => 'foo',
        ]);
        $this->assertEquals($result['foo'], 'foo');

        $collection = $this->getMock(Collection::class, ['findOne'], [ $this->injector->resolve(Connection::class), 'Foo']);
        $collection->method('findOne')->will($this->returnCallback(function($criteria) {
            return ['foo' => 'foo'];
        }));
        $collection->addField($this->getMockForAbstractClass(\Norm\Schema\NField::class, [$collection, 'foo', 'unique']));
        $filter = new Filter($collection, true);
        try {
            $result = $filter->run([
                'foo' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {
        }

        $collection = $this->getMock(Collection::class, ['findOne'], [ $this->injector->resolve(Connection::class), 'Foo']);
        $collection->method('findOne')->will($this->returnCallback(function($criteria) {
            return ['foo' => 'foo'];
        }));
        $collection->addField($this->getMockForAbstractClass(\Norm\Schema\NField::class, [$collection, 'foo', 'unique:foo']));
        $filter = new Filter($collection);
        try {
            $result = $filter->run([
                'foo' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {
        }

        $result = $filter->run([]);
        $this->assertEquals($result['foo'], '');

        $filter = new Filter([
            'foo' => [
                'filter' => ['unique']
            ]
        ], true);
        try {
            $result = $filter->run([
                'foo' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {
        }
    }

    public function testFilterUniqueCrossCollection()
    {

        $repository = $this->getMock(Repository::class);
        $connection = $this->getMockForAbstractClass(Connection::class, [$repository]);

        $repository->method('factory')->will($this->returnCallback(function($name) use ($connection) {
            $collection = $this->getMock(Collection::class, [], [$connection, $name]);
            $collection->method('findOne')->will($this->returnValue(['bar' => 'foo']));
            return $collection;
        }));

        $collection = new Collection($connection, 'Foo');
        $collection->addField($this->getMockForAbstractClass(\Norm\Schema\NField::class, [$collection, 'foo', 'unique:Bar,bar']));

        $filter = new Filter($collection, true);
        try {
            $filter->run([
                'foo' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch (FilterException $e) {}
    }

    public function testFilterSalt()
    {
        $collection = new Collection($this->injector->resolve(Connection::class), 'Foo');
        $collection->addField($this->getMockForAbstractClass(\Norm\Schema\NField::class, [$collection, 'salty', 'salt']));
        $filter = new Filter($collection);

        try {
            $result = $filter->run([
                'salty' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch (FatalException $e) {
            if ($e->getMessage() !== 'You should define salt key in order to use salt.') {
                throw $e;
            }
        }

        $this->injector->resolve(Repository::class)->setAttribute('salt', 'random');
        $filter = new Filter($collection);
        $result = $filter->run([
            'salty' => 'foo',
        ]);
        $this->assertNotEquals($result['salty'], 'foo');

        $this->injector->resolve(Repository::class)->setAttribute('salt', ['sha1', 'random']);
        $filter = new Filter($collection);
        $result = $filter->run([
            'salty' => 'foo',
        ]);
        $this->assertNotEquals($result['salty'], 'foo');

        $result = $filter->run([
            'salty' => '',
        ]);
        $this->assertEquals($result['salty'], '');

        $this->injector->resolve(Repository::class)->setAttribute('salt', ['sha1']);
        $filter = new Filter($collection);
        try {
            $result = $filter->run([
                'salty' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch (FatalException $e) {
            if ($e->getMessage() !== 'You should define salt key in order to use salt.') {
                throw $e;
            }
        }
    }

    public function testMinMaxBetween()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'min:10'
                ]
            ],
        ]);
        $result = $filter->run([
            'foo' => '10',
        ]);
        try {
            $result = $filter->run([
                'foo' => '1',
            ]);
            $this->fail('Must not here');
        } catch (FilterException $e) {
        }

        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'max:10'
                ]
            ],
        ]);
        $result = $filter->run([
            'foo' => '10',
        ]);
        try {
            $result = $filter->run([
                'foo' => '11',
            ]);
            $this->fail('Must not here');
        } catch (FilterException $e) {
        }

        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'between:-1,10'
                ]
            ],
        ]);
        $result = $filter->run([
            'foo' => '5',
        ]);
        try {
            $result = $filter->run([
                'foo' => '-3',
            ]);
            $this->fail('Must not here');
        } catch (FilterException $e) {
        }
        try {
            $result = $filter->run([
                'foo' => '11',
            ]);
            $this->fail('Must not here');
        } catch (FilterException $e) {
        }
    }

    public function testIpAndEmail()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'email'
                ]
            ],
        ]);
        $result = $filter->run([
            'foo' => 'foo@bar.com',
        ]);

        $result = $filter->run([]);
        $this->assertEquals($result['foo'], '');

        try {
            $result = $filter->run([
                'foo' => '11',
            ]);
            $this->fail('Must not here');
        } catch (FilterException $e) {
        }

        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'ip'
                ]
            ],
        ]);
        $result = $filter->run([
            'foo' => '127.0.0.1',
        ]);

        $result = $filter->run([]);
        $this->assertEquals($result['foo'], '');

        try {
            $result = $filter->run([
                'foo' => '11',
            ]);
            $this->fail('Must not here');
        } catch (FilterException $e) {
        }
    }

    public function testDefault()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'default:bar'
                ]
            ],
        ]);
        $result = $filter->run([
        ]);
        $this->assertEquals($result['foo'], 'bar');
    }

    public function testRemoveEmpty()
    {
        $filter = new Filter([
            'foo' => [
                'filter' => [
                    'removeEmpty'
                ]
            ],
        ]);
        $result = $filter->run([
            'foo' => [
                'bar' => null,
                'baz' => 'baz',
            ]
        ]);
        $this->assertTrue(!isset($result['foo']['bar']));

        $result = $filter->run([
            'foo' => []
        ]);
        $this->assertTrue(empty($result['foo']));
    }
}
