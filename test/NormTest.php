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
        $repository = new Norm();

        $stub = $this->getMock(Connection::class);
        $repository->add('first', $stub);
        $this->assertEquals($stub, $repository->getConnection('first'));
    }

    public function testCanAddConnection()
    {
        $repository = new Norm();

        $stub = $this->getMock(Connection::class);
        $retval = $repository->add('first', $stub);

        $this->assertEquals($repository, $retval, '#add should be chainable');
    }

    public function testCanAddAndGetResolvers()
    {
        $repository = new Norm();

        $resolver = function () {

        };
        $retval = $repository->addResolver($resolver);

        $this->assertEquals($repository, $retval, '#addResolver should be chainable');
    }

    public function testCanSetAndGetDefault()
    {
        $repository = new Norm();

        $default = [];
        $retval = $repository->setDefault($default);

        $this->assertEquals($repository, $retval, '#setDefault should be chainable');
    }

    public function testConstructWithOptions()
    {
        $connections = [
            'first' => $this->getMock(Connection::class),
        ];

        $default = [];
        $resolver = function () {
        };

        $repository = new Norm([
            'connections' => $connections,
            'collections' => [
                'default' => $default,
                'resolvers' => [
                    $resolver
                ]
            ]
        ]);

        $this->assertEquals($connections['first'], $repository->getConnection('first'));
    }

    public function testFactoryUseResolvers()
    {
        $resolverHandler = $this->getMock('stdClass', ['resolve']);
        $resolverHandler->expects($this->at(0))
             ->method('resolve');
        $resolverHandler->expects($this->at(1))
             ->method('resolve')->will($this->returnValue([]));
        $resolverHandler->expects($this->at(2))
             ->method('resolve');

        $resolverWrapper = function ($id) use ($resolverHandler) {
            return call_user_func_array([$resolverHandler, 'resolve'], func_get_args());
        };

        $repository = new Norm([
            'connections' => [
                'null' => $this->getMock(Connection::class),
            ],
            'collections' => [
                'resolvers' => [
                    $resolverWrapper
                ]
            ],
        ]);
        $collection = $repository->factory('Once');
        $collection = $repository->factory('Resolved');
        $collection = $repository->factory('Twice');

        $this->assertInstanceOf(Collection::class, $collection);
    }
}
