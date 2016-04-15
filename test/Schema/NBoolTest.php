<?php
namespace Norm\Test\Schema;

use Norm\Repository;
use Norm\Connection;
use Norm\Collection;
use Norm\Schema\NBool;
use Norm\Schema;

class NBoolTest extends AbstractSchemaTest
{
    public function testPrepare()
    {
        $schema = $this->repository->resolve(NBool::class, [
            'options'=> [
                'name' => 'foo',
            ]
        ]);
        $this->assertEquals($schema->prepare('1'), true);
        $this->assertEquals($schema->prepare(1), true);
        $this->assertEquals($schema->prepare(true), true);

        $this->assertEquals($schema->prepare(null), false);
        $this->assertEquals($schema->prepare(''), false);
        $this->assertEquals($schema->prepare('0'), false);
        $this->assertEquals($schema->prepare(0), false);
        $this->assertEquals($schema->prepare(false), false);
    }
}