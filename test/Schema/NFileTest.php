<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NFile;
use Norm\Schema;
use ROH\Util\File as UtilFile;
use Norm\Type\File;
use NOrm\Exception\NormException;

class NFileTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        UtilFile::rm('tmp-data');

        $this->schema = $this->getMock(Schema::class);
    }

    public function tearDown()
    {
        UtilFile::rm('tmp-data');
    }

    public function testPrepare()
    {
        @mkdir('tmp-data/foo', 0755, true);
        file_put_contents('tmp-data/foo/baz', 'baz');
        @mkdir('tmp-data/bar', 0755, true);
        file_put_contents('tmp-data/bar/baz', 'baz');

        $field = new NFile($this->schema, 'foo');
        $field['dataDir'] = 'tmp-data';
        $this->assertEquals($field->prepare('missing')->isExists(), false);

        $this->assertEquals($field->prepare('bar/missing')->isExists(), false);

        $this->assertEquals($field->prepare('bar/baz')->isExists(), true);

        $file = $field->prepare('foo/baz');
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($file->getPath(), 'foo/baz');
        $this->assertEquals($file->getName(), 'baz');
        $this->assertEquals($file->getSize(), 3);

        $field->prepare(new File('tmp-data', 'foo'));
        $this->assertInstanceOf(File::class, $file);

        $field = new NFile($this->schema, 'foo');
        try {
            $field->prepare(new File('bar', 'foo'));
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Incompatible file') {
                throw $e;
            }
        }
    }

    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($template) {
            return $template;
        }));
        $field = new NFile($schema, 'foo');

        $result = $field->format('input');
        $this->assertEquals($result, '__norm__/nfile/input');

        $result = $field->format('readonly');
        $this->assertEquals($result, '__norm__/nfile/readonly');
    }

}