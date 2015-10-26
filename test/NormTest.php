<?php

namespace Norm\Test;

use stdClass;
use Norm\Norm;
use Norm\Connection;
use Norm\Collection;
use PHPUnit_Framework_TestCase;

class NormTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function testCanGetConnection()
    {
        $norm = new Norm();

        $stub = $this->getMock(Connection::class);
        $norm->add('first', $stub);
        $this->assertEquals($stub, $norm->getConnection('first'));
    }

    public function testCanAddConnection()
    {
        $norm = new Norm();

        $stub = $this->getMock(Connection::class);
        $retval = $norm->add('first', $stub);

        $this->assertEquals($norm, $retval, '#add should be chainable');
    }

    public function testCanAddAndGetResolvers()
    {
        $norm = new Norm();

        $resolver = $this->getMock('stdClass');
        $retval = $norm->addResolver($resolver);

        $this->assertEquals($norm, $retval, '#addResolver should be chainable');
    }

    public function testCanSetAndGetDefault()
    {
        $norm = new Norm();

        $default = $this->getMock('stdClass');
        $retval = $norm->setDefault($default);

        $this->assertEquals($norm, $retval, '#setDefault should be chainable');
    }

    public function testConstructWithOptions()
    {
        $connections = [
            'first' => $this->getMock(Connection::class),
        ];

        $default = $this->getMock('stdClass');
        $resolver = $this->getMock('stdClass');

        $norm = new Norm([
            'connections' => $connections,
            'collections' => [
                'default' => $default,
                'resolvers' => [
                    $resolver
                ]
            ]
        ]);

        $this->assertEquals($connections['first'], $norm->getConnection('first'));
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
                'null' => $this->getMock(Connection::class),
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
