<?php
namespace Norm\Test;

use PHPUnit\Framework\TestCase;
use Norm\Manager;
use Norm\Filter;
use Norm\Session;
use Norm\Schema\NString;
use Norm\Filter\RequiredException;
use ROH\Util\Collection;
use ROH\Util\Injector;
use Norm\Adapter\Memory;
use Norm\Exception\FilterException;

class FilterTest extends TestCase
{
    public function testRun()
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
                            (new NString('username'))->filter('required'),
                            [ NString::class, [
                                'name' => 'password',
                            ]],
                        ],
                    ],
                ],
            ],
        ]);

        try {
            $manager->runSession(function (Session $session) {
                $session->factory('user')
                    ->insert([
                        'name' => 'foo',
                        'password' => 'pass',
                    ])
                    ->save();
            });
        } catch (FilterException $e) {
            $err = $e->getChildren()[0];
            $this->assertEquals($err->getField(), 'username');
            $this->assertTrue($err instanceof RequiredException);
        }
    }

    public function testRegisterAsFunction()
    {
        $hit = false;
        $foo = function () use (&$hit) {
            return function ($value) use (&$hit) {
                $hit = true;
                return $value;
            };
        };
        Filter::register('foo', $foo);

        $filter = Filter::get('foo');

        $this->assertEquals($filter, $foo);

        $result = $filter('foo');

        $this->assertEquals($hit, true);
        $this->assertEquals($result, 'foo');
    }
}
