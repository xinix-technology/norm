<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Filter;
use Norm\Exception\FilterException;
use Norm\Collection;
use Norm\Connection;
use Norm\Exception\FatalException;
use Norm\Exception\SkipException;

class FilterTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    protected $collection;

    public function setUp()
    {
        $this->repository = new Repository();
        $connection = $this->getMock(Connection::class);
        $this->collection = $this->getMock(Collection::class, [], [
            $this->repository,
            $connection,
            [ 'name' => 'Foo' ],
        ]);
    }

    public function testRegister()
    {
        $foo = function() {};
        Filter::register('foo', $foo);
        $this->assertEquals(Filter::get('foo'), $foo);
    }

    public function testFilterChain()
    {
        $filter = function() {};
        $rules = Filter::parseFilterRules([
            'foo' => [
                'filters' => [
                    'a|b:c|d:e,f',
                    $filter,
                ]
            ]
        ]);

        $this->assertEquals(4, count($rules['foo']['filters']));

        $this->assertEquals('a', $rules['foo']['filters'][0][0]);
        $this->assertEquals([], $rules['foo']['filters'][0][1]);

        $this->assertEquals('b', $rules['foo']['filters'][1][0]);
        $this->assertEquals(['c'], $rules['foo']['filters'][1][1]);

        $this->assertEquals('d', $rules['foo']['filters'][2][0]);
        $this->assertEquals(['e', 'f'], $rules['foo']['filters'][2][1]);
        $this->assertEquals($filter, $rules['foo']['filters'][3][0]);
    }

    public function testConstruct()
    {
        $filter = new Filter($this->collection, [
            'foo' => [
                'filters' => [
                    'a|b:c|d:e,f',
                ]
            ]
        ]);

        $this->assertEquals(count($filter->__debugInfo()['foo']['filters']), 3);
    }

    public function testGetLabel()
    {
        $filter = new Filter($this->collection, [
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
        $filter = new Filter($this->collection, [
            'paddedstr' => [
                'filters' => [
                    'trim'
                ]
            ]
        ]);

        $data = [
            'paddedstr' => '   this is str   ',
        ];

        $result = $filter->run($data);

        $this->assertEquals('this is str', $result['paddedstr']);
    }

    public function testRunSingleField()
    {
        $filter = new Filter($this->collection, [
            'paddedstr' => [
                'filters' => [
                    'trim'
                ]
            ],
            'foo' => [
                'filters' => [
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
        $filter = new Filter($this->collection, [
            'foo' => [
                'filters' => [
                    'trim'
                ]
            ],
            'bar' => [
                'filters' => [
                    'trim'
                ]
            ]
        ]);

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
        $filter = new Filter($this->collection, [
            'foo' => [
                'filters' => [
                    function() {
                        throw new FatalException('fatal');
                    }
                ]
            ],
            'bar' => [
                'filters' => [
                    'trim'
                ]
            ]
        ]);

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
        $filter = new Filter($this->collection, [
            'foo' => [
                'filters' => [
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
        $filter = new Filter($this->collection, [
            'foo' => [
                'filters' => [
                    'trim',
                ]
            ],
            'bar' => [
                'filters' => [
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

    public function testFilterRequired()
    {
        $filter = new Filter($this->collection, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
                    'required'
                ]
            ],
        ]);

        try {
            $x = $filter->run(['foo' => 'not raised']);
        } catch (\Excception $e) {
            $this->fail('Unexpected exception raised here. '. $e->getMessage());
        }

        try {
            $filter->run(null);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
            $this->assertEquals('Field Foo is required', $filter->getErrors()[0]->getMessage());
        } catch(\Exception $e) {
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
    }

    public function testFilterConfirmed()
    {
        $filter = new Filter($this->collection, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
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
    }

    public function testFilterUnique()
    {
        $filter = new Filter($this->collection, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
                    'unique'
                ]
            ],
        ]);

        $result = $filter->run([
            'foo' => 'foo',
        ]);
    }
}
