<?php
namespace Norm\Test;

use Norm\Model;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testJsonSerialize()
    {
        $model = new Model([
            'id' => 1,
            'foo' => 'bar'
        ]);

        $this->assertEquals($model->jsonSerialize()['id'], 1);
        $this->assertEquals($model->jsonSerialize()['foo'], 'bar');
    }
}
