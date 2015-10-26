<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Filter;
use Norm\Exception\FilterException;
use Norm\Collection;

class FilterTest extends PHPUnit_Framework_TestCase
{
    public function mockCollection()
    {
        return $this->getMock(Collection::class, [], [null, [
            'name' => 'Foo',
        ]]);
    }

    public function testFilterChain()
    {
        $rules = Filter::parseFilterRules([
            'foo' => [
                'filters' => [
                    'a|b:c|d:e,f'
                ]
            ]
        ]);

        $this->assertEquals(3, count($rules['foo']['filters']));

        $this->assertEquals('a', $rules['foo']['filters'][0][0]);
        $this->assertEquals([], $rules['foo']['filters'][0][1]);

        $this->assertEquals('b', $rules['foo']['filters'][1][0]);
        $this->assertEquals(['c'], $rules['foo']['filters'][1][1]);

        $this->assertEquals('d', $rules['foo']['filters'][2][0]);
        $this->assertEquals(['e', 'f'], $rules['foo']['filters'][2][1]);
    }

    public function testRegister()
    {
        $filter1 = function () {

        };
        $filter2 = [$this, 'testRegister'];

        Filter::register('filter-1', $filter1);
        Filter::register('filter-2', $filter2);

        $registries = Filter::debugRegistration();

        $this->assertEquals($filter1, $registries['filter-1']);
        $this->assertEquals($filter2, $registries['filter-2']);
    }

    public function testConstruct()
    {
        $filter = new Filter($this->mockCollection(), []);
    }

    public function testRunAllContext()
    {
        $filter = new Filter($this->mockCollection(), [
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

    public function testRunSelectiveContext()
    {
        $filter = new Filter($this->mockCollection(), [
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

    public function testGetErrors()
    {
        $filter = new Filter($this->mockCollection(), [
            'foo' => [
                'filters' => [
                    function () {
                        throw new \Exception('Foo error');
                    }
                ]
            ],
            'bar' => [
                'filters' => [
                    function () {
                        throw new \Exception('Bar error');
                    }
                ]
            ]
        ]);

        try {
            $filter->run(null);
        } catch (FilterException $e) {
            $this->assertEquals('Foo error', $e->getChildren()[0]->getMessage());
            $this->assertEquals('Foo error', $filter->getErrors()[0]->getMessage());
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testFilterRequired()
    {
        $filter = new Filter($this->mockCollection(), [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
                    'required'
                ]
            ],
        ]);

        try {
            $x = $filter->run(['foo' => 'not raised']);
        } catch (\Exception $e) {
            $this->fail('Unexpected exception raised here. '. $e->getMessage());
        }

        try {
            $filter->run(null);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
            $this->assertEquals('Field Foo is required', $filter->getErrors()[0]->getMessage());
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
        $filter = new Filter($this->mockCollection(), [
            'foo' => [
                'label' => 'Foo',
            ],
        ]);

        try {
            $filter->filterConfirmed('foo', [
                'key' => 'foo',
                'data' => [
                    'foo' => 'foo',
                    'foo_confirmation' => 'foox',
                ]
            ]);
            $this->fail('Expected exception raised here.');
        } catch (FilterException $e) {
            $this->assertEquals('Field Foo must be confirmed', $e->getMessage());
        }

        $result = $filter->filterConfirmed('foo', [
            'key' => 'foo',
            'data' => [
                'foo' => 'foo',
                'foo_confirmation' => 'foo',
            ]
        ]);

        $this->assertEquals('foo', $result);
    }

    public function testFilterUnique()
    {
        $filter = new Filter($this->mockCollection(), [
            'foo' => [
                'label' => 'Foo',
            ],
        ]);

        $result = $filter->filterUnique('foo', [
            'key' => 'foo',
            'data' => [
                'foo' => 'foo',
            ],
            'arguments' => []
        ]);

        $this->assertEquals('foo', $result);

        // try {
        //     $filter->filterUnique('foo', [
        //         'key' => 'foo',
        //         'data' => [
        //             'foo' => 'foo',
        //         ],
        //         'arguments' => []
        //     ]);
        //     $this->fail('Expected exception raised here.');
        // } catch (FilterException $e) {
        //     $this->assertEquals('Field Foo must be confirmed', $e->getMessage());
        // }

    }
}
