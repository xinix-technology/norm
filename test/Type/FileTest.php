<?php
namespace Norm\Test\Type;

use PHPUnit_Framework_TestCase;
use Norm\Type\File;
use ROH\Util\File as UtilFile;

class FileTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        UtilFile::rm('./tmp');
    }

    public function testConstruct()
    {
        $file = new File('./tmp', '/foo/bar/');
        $this->assertEquals($file->getPath(), 'foo/bar');
        $this->assertEquals($file->isExists(), false);
        $this->assertEquals($file->getBaseDirectory(), './tmp');

        mkdir('./tmp/foo', 0755, true);
        file_put_contents('./tmp/foo/bar', '123');
        $file = new File('./tmp/', 'foo/bar');
        $this->assertEquals($file->getPath(), 'foo/bar');
        $this->assertEquals($file->getBaseDirectory(), './tmp');
        $this->assertEquals($file->getName(), 'bar');
        $this->assertEquals($file->getSize(), 3);

    }
}