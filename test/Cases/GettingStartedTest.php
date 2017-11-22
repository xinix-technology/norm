<?php
namespace Norm\Test;

use PHPUnit\Framework\TestCase;
use Norm\Repository;
use Norm\Connection;
use Norm\Collection;
use Norm\Cursor;
use Norm\Model;
use Norm\Exception\NormException;
use ROH\Util\Injector;
use Norm\Schema\NField;
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
        $repository = new Repository();
        $repository->addConnection(new Memory($repository));

        $userCollection = $repository->factory('user');

        $this->assertEquals($userCollection->find()->count(), 0);

        $user = $userCollection->newInstance();
        $user->set([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $user->save();

        $user = $userCollection->newInstance();
        $user->set([
            'username' => 'bar',
            'password' => 'baz',
        ]);
        $user->save();

        $this->assertEquals($userCollection->find()->count(), 2);

        $user = $userCollection->findOne([ 'username' => 'bar' ]);

        $user->set('password', 'password');
        $user->save();

        $user = $userCollection->findOne([ 'username' => 'bar' ]);
        $this->assertEquals($user['password'], 'password');

        $user->remove();

        $this->assertEquals($userCollection->find()->count(), 1);
    }
}
