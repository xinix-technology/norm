<?php
namespace Norm\Observer;

use DateTime;
use Norm\Schema\NDateTime;
use ROH\Util\Options;

class Timestampable
{
    protected $options;

    public function __construct($options = [])
    {
        $this->options = (new Options([
            'createdKey' => '$created_time',
            'updatedKey' => '$updated_time',
        ]))->merge($options);
    }

    public function initialize($context)
    {
        $context['collection']->addField([ NDateTime::class, [
            'name' => $this->options['createdKey']
        ]]);
        $context['collection']->addField([ NDateTime::class, [
            'name' => $this->options['updatedKey']
        ]]);
    }

    public function save($context, $next)
    {
        $now = new DateTime();

        if ($context['model']->isNew()) {
            $context['model'][$this->options['updatedKey']] = $now;
            $context['model'][$this->options['createdKey']] = $now;
        } else {
            $context['model'][$this->options['updatedKey']] = $now;
        }

        $next($context);
    }
}
