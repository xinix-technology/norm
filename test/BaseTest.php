<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Base;
use ROH\Util\Collection as UtilCollection;

class BaseTest extends PHPUnit_Framework_TestCase
{
    public function testCompose()
    {
        $base = $this->getMockForAbstractClass(Base::class);
        $result = $base->compose('foo', function () {
            // do something;
        });

        $this->assertEquals($base, $result);
    }

    public function testApply()
    {
        $base = $this->getMockForAbstractClass(Base::class);
        $result = $base->compose('foo', function ($context, $next) {
            $context['before-1'] = true;
            $next($context);
            $context['after-1'] = true;
        })
        ->compose('foo', function ($context, $next) {
            $context['before-2'] = true;
            $next($context);
            $context['after-2'] = true;
        })
        ->compose('foo', function ($context, $next) {
            $context['before-3'] = true;
            $next($context);
            $context['after-3'] = true;
        });

        $context = new UtilCollection();
        $base->apply('foo', $context, function ($context) {
            $context['core'] = true;
        });

        $this->assertEquals($context['before-1'], true);
        $this->assertEquals($context['before-2'], true);
        $this->assertEquals($context['before-3'], true);
        $this->assertEquals($context['after-1'], true);
        $this->assertEquals($context['after-2'], true);
        $this->assertEquals($context['after-3'], true);
        $this->assertEquals($context['core'], true);
    }
}
