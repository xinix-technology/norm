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
use ROH\Util\Collection as UtilCollection;

class FilterTest extends PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $hit = false;
        $foo = function() use (&$hit) {
            $hit = true;
        };
        Filter::register('foo', $foo);
        $this->assertEquals(Filter::get('foo'), $foo);

        $filter = new Filter(null, [
            'foo' => [
                'filters' => [
                    'foo',
                ]
            ]
        ]);
        $filter->run([]);
        $this->assertTrue($hit);
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
        $filter = new Filter(null, [
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
        $filter = new Filter(null, [
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
        $filter = new Filter(null, [
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
        $filter = new Filter(null, [
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
        $filter = new Filter(null, [
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
        $filter = new Filter(null, [
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
        $filter = new Filter(null, [
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
        $filter = new Filter(null, [
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

    public function testRunIneligibleError()
    {
        $filter = new Filter(null, [
            'foo' => [
                'filters' => [
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
        $filter = new Filter(null, [
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

        // required with
        $filter = new Filter(null, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
                    'requiredWith:bar'
                ]
            ],
        ]);

        $result = $filter->run([
        ]);
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
        $filter = new Filter(null, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
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
            $result = $filter->run([
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {}
    }

    public function testFilterConfirmed()
    {
        $filter = new Filter(null, [
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

        $result = $filter->run([
            'bar' => 'foo',
        ]);

        $this->assertEquals('', $result['foo']);
    }

    public function testFilterUnique()
    {
        $findOneHit = 0;
        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $collection->method('factory')->will($this->returnValue($collection));
        $collection->method('findOne')->will($this->returnCallback(function($criteria) use (&$findOneHit) {
            $findOneHit++;
            if (isset($criteria['notfound'])) {
                return null;
            } else {
                return [
                    'foo' => 'foo',
                ];
            }
        }));

        $filter = new Filter($collection, [
            'notfound' => [
                'label' => 'Foo',
                'filters' => [
                    'unique'
                ]
            ],
        ]);

        $result = $filter->run([
            'notfound' => 'foo',
        ]);
        $this->assertEquals($findOneHit, 1);
        $this->assertEquals($result['notfound'], 'foo');

        $filter = new Filter($collection, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
                    'unique'
                ]
            ],
        ]);
        try {
            $result = $filter->run([
                'foo' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {
        }
        $this->assertEquals($findOneHit, 2);

        $filter = new Filter($collection, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
                    'unique:foo',
                ]
            ],
        ]);
        try {
            $result = $filter->run([
                'foo' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {
        }
        $this->assertEquals($findOneHit, 3);

        $filter = new Filter($collection, [
            'foo' => [
                'label' => 'Foo',
                'filters' => [
                    'unique:Bar,foo',
                ]
            ],
        ]);
        try {
            $result = $filter->run([
                'foo' => 'foo',
            ]);
            $this->fail('Must not here');
        } catch(FilterException $e) {
        }
        $this->assertEquals($findOneHit, 4);

        $result = $filter->run([
        ]);
        $this->assertEquals($result['foo'], '');
    }

    public function testFilterSalt()
    {
        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $filter = new Filter($collection, [
            'salty' => [
                'filters' => [
                    'salt'
                ]
            ],
        ]);

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

        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $collection->method('getAttribute')->will($this->returnValue('random'));
        $filter = new Filter($collection, [
            'salty' => [
                'filters' => [
                    'salt'
                ]
            ],
        ]);
        $result = $filter->run([
            'salty' => 'foo',
        ]);
        $this->assertNotEquals($result['salty'], 'foo');

        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $collection->method('getAttribute')->will($this->returnValue(['sha1', 'random']));
        $filter = new Filter($collection, [
            'salty' => [
                'filters' => [
                    'salt'
                ]
            ],
        ]);
        $result = $filter->run([
            'salty' => 'foo',
        ]);
        $this->assertNotEquals($result['salty'], 'foo');

        $result = $filter->run([
            'salty' => '',
        ]);
        $this->assertEquals($result['salty'], '');

        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $collection->method('getAttribute')->will($this->returnValue(['sha1']));
        $filter = new Filter($collection, [
            'salty' => [
                'filters' => [
                    'salt'
                ]
            ],
        ]);
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
        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $filter = new Filter($collection, [
            'foo' => [
                'filters' => [
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

        $filter = new Filter($collection, [
            'foo' => [
                'filters' => [
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

        $filter = new Filter($collection, [
            'foo' => [
                'filters' => [
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
        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $filter = new Filter($collection, [
            'foo' => [
                'filters' => [
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

        $filter = new Filter($collection, [
            'foo' => [
                'filters' => [
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
        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $filter = new Filter($collection, [
            'foo' => [
                'filters' => [
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
        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $filter = new Filter($collection, [
            'foo' => [
                'filters' => [
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
