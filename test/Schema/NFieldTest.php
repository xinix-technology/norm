<?php
namespace Norm\Test\Schema;

use Norm\Repository;
use Norm\Connection;
use Norm\Collection;
use Norm\Schema\NField;
use Norm\Schema;
use Norm\Exception\NormException;

class NFieldTest extends AbstractSchemaTest
{
    public function setUp()
    {
        parent::setUp();

        $this->schema = $this->getMock(NField::class, ['factory'], [
            $this->repository,
            $this->repository->resolve(Schema::class),
            [ 'name' => 'Foo', ]
        ]);
    }
    public function testConstruct()
    {
        try {
            $schema = $this->getMock(NField::class, [], [
                $this->repository,
                $this->repository->resolve(Schema::class),
            ]);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Option name is mandatory!') {
                throw $e;
            }
        }
    }
}