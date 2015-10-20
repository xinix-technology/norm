<?php

namespace Norm\Test;

use stdClass;
use Norm\Norm;
use Norm\Collection;

class NormTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function testCanGetConnection()
    {
        $norm = new Norm();

        $stub = new stdClass();
        $norm->add('first', $stub);

        $this->assertEquals($stub, $norm->getConnection('first'));
    }

    public function testCanAddConnection()
    {
        $norm = new Norm();

        $stub = $this->getMockBuilder('stdClass')->getMock();
        $retval = $norm->add('first', $stub);

        $this->assertEquals($norm, $retval, '#add should be chainable');
    }

    public function testCanAddAndGetResolvers()
    {
        $norm = new Norm();

        $stub = $this->getMockBuilder('stdClass')->getMock();
        $retval = $norm->addResolver($stub);

        $this->assertEquals($norm, $retval, '#addResolver should be chainable');
        $this->assertEquals(
            $stub,
            $norm->getResolvers()[0],
            '#getResolver should return the same value set with #addResolver'
        );
    }

    public function testCanSetAndGetDefault()
    {
        $norm = new Norm();

        $stub = $this->getMockBuilder('stdClass')->getMock();
        $retval = $norm->setDefault($stub);

        $this->assertEquals($norm, $retval, '#setDefault should be chainable');
        $this->assertEquals(
            $stub,
            $norm->getDefault(),
            '#getDefault should return the same value set with #setDefault'
        );
    }

    public function testConstructWithOptions()
    {
        $connectionsStub = [
            'first' => new stdClass(),
        ];

        $defaultStub = $this->getMockBuilder('stdClass')->getMock();
        $resolverStub = $this->getMockBuilder('stdClass')->getMock();

        $norm = new Norm([
            'connections' => $connectionsStub,
            'collections' => [
                'default' => $defaultStub,
                'resolvers' => [
                    $resolverStub
                ]
            ]
        ]);

        $this->assertEquals($connectionsStub['first'], $norm->getConnection('first'));
        $this->assertEquals($defaultStub, $norm->getDefault());
        $this->assertEquals($resolverStub, $norm->getResolvers()[0]);
    }

    public function testFactoryUseResolvers()
    {
        $resolverHandlerMock = $this->getMock('stdClass', ['resolve']);
        $resolverHandlerMock->expects($this->at(0))
             ->method('resolve');
        $resolverHandlerMock->expects($this->at(1))
             ->method('resolve')->will($this->returnValue([]));
        $resolverHandlerMock->expects($this->at(2))
             ->method('resolve');

        $resolverWrapper = function ($id) use ($resolverHandlerMock) {
            return call_user_func_array([$resolverHandlerMock, 'resolve'], func_get_args());
        };

        $norm = new Norm([
            'connections' => [
                'null' => new \stdClass(),
            ],
            'collections' => [
                'resolvers' => [
                    $resolverWrapper
                ]
            ],
        ]);
        $collection = $norm->factory('Once');
        $collection = $norm->factory('Resolved');
        $collection = $norm->factory('Twice');

        $this->assertInstanceOf(Collection::class, $collection);
    }
}
