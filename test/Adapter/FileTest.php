<?php
namespace Norm\Test\Adapter;

use Norm\Cursor;
use Norm\Norm as TheNorm;
use Norm\Adapter\File;
use FilesystemIterator;
use PHPUnit_Framework_TestCase;

if (!function_exists('rrmdir')) {
    function rrmdir($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}

class FileTest extends PHPUnit_Framework_TestCase
{
    protected $norm;

    public function setUp()
    {
        @rrmdir('db-files');

        $this->norm = new TheNorm([
            'connections' => [
                'file' => [
                    'class' => File::class,
                    'config' => [
                        'dataDir' => 'db-files'
                    ]
                ]
            ]
        ]);

        $model = $this->norm->factory('Foo')->newInstance();
        $model->set(['fname' => 'Jane', 'lname' => 'Doe']);
        $model->save();
        $model = $this->norm->factory('Foo')->newInstance();
        $model->set(['fname' => 'Ganesha', 'lname' => 'M']);
        $model->save();
    }

    public function testSearch()
    {
        $cursor = $this->norm->factory('Foo')->find();

        $this->assertInstanceOf(Cursor::class, $cursor);
    }

    public function testCreate()
    {
        $model = $this->norm->factory('Foo')->newInstance();
        $model->set([
            'fname' => 'John',
            'lname' => 'Doe',
        ]);
        $model->save();

        $row = json_decode(file_get_contents('db-files/foo/'.$model['$id'].'.json'), 1);

        $this->assertEquals(
            $row['fname'],
            $model['fname']
        );
    }

    public function testRead()
    {
        $this->testCreate();

        $model = $this->norm->factory('Foo')->findOne(['fname' => 'John']);
        $this->assertEquals('Doe', $model['lname']);

        $fi = new FilesystemIterator('db-files/foo', FilesystemIterator::SKIP_DOTS);
        $this->assertEquals(3, iterator_count($fi));
    }

    public function testUpdate()
    {
        $model = $this->norm->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model['fname'] = 'Rob';
        $model->save();

        $row = json_decode(file_get_contents('db-files/foo/'.$model['$id'].'.json'), 1);

        $this->assertEquals('Rob', $row['fname']);
    }

    public function testDelete()
    {
        $model = $this->norm->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model->remove();

        $fi = new FilesystemIterator('db-files/foo', FilesystemIterator::SKIP_DOTS);

        $this->assertEquals(1, iterator_count($fi));
    }
}
