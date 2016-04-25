<?php
namespace Norm\Test\Resolver;

use PHPUnit_Framework_TestCase;
use Norm\Resolver\DefaultResolver;

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

class DefaultResolverTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        @mkdir('./tmp', 0755, true);
    }

    public function tearDown()
    {
        rrmdir('./tmp');
    }

    public function testResolve()
    {
        file_put_contents('./tmp/Foo.php', "<?php return [ 'foo' => 'bar' ];");

        $resolver = new DefaultResolver([
            'resolvePaths' => [
                './tmp'
            ]
        ]);
        $this->assertEquals($resolver('Foo'), ['foo' => 'bar']);
        $this->assertEquals($resolver('Bar'), null);
    }
}