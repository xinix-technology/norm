<?php
namespace Norm\Test;

use PHPUnit\Framework\TestCase;
use Norm\Manager;
use Norm\Session;
use Norm\Connection;
use ROH\Util\Injector;
use ROH\Util\Collection;
use Norm\Schema\NString;
use Norm\Adapter\Memory;

class GettingStartedTest extends TestCase
{
    // public function setUp()
    // {
    //     $this->injector = new Injector();
    //     // $this->repository = new Repository([], $this->injector);
    //     // $this->injector->singleton(
    //     //     Connection::class,
    //     //     $this->getMockForAbstractClass(Connection::class, [ $this->repository ])
    //     // );
    // }

    public function testGettingStarted()
    {
        $injector = new Injector();
        $data = new Collection();
        $manager = new Manager($injector, [
            [
                'handler' => [ Memory::class, [
                    'data' => $data,
                ]],
                'schemas' => [
                    [
                        'name' => 'user',
                        'fields' => [
                            [ NString::class, [
                                'name' => 'username',
                            ]],
                            [ NString::class, [
                                'name' => 'password',
                            ]],
                        ],
                    ],
                ],
            ],
        ]);

        $manager->runSession(function (Session $session) use (&$data) {
            $this->assertEquals($session->factory('user')->count(), 0);

            $query = $session->factory('user')
                ->insert([
                    'username' => 'foo',
                    'password' => 'bar',
                ])
                ->insert([
                    'username' => 'bar',
                    'password' => 'baz',
                ])
                ->save();

            $this->assertEquals($query->getAffected(), 2);
            $this->assertEquals(count($query->getRows()), 2);

            $this->assertEquals($session->factory('user')->count(), 2);

            $session->factory('user', [ 'username' => 'bar' ])
                ->set([ 'password' => 'password' ])
                ->save();

            $user = $session->factory('user', [ 'username' => 'bar' ])
                ->single();

            $this->assertEquals($user['password'], 'password');

            $session->factory('user', [ 'username' => 'bar' ])
                ->delete();

            $this->assertEquals($session->factory('user')->count(), 1);
        });
    }
}
