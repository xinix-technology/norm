<?php
namespace Norm\Observer;

use DateTime as NDateTime;
use Norm\Schema\DateTime as SchemaDateTime;
use ROH\Util\Options;

class Timestampable
{
    protected $options;

    public function __construct($options = [])
    {
        $this->options = Options::create([
            'createdKey' => '$created_time',
            'updatedKey' => '$updated_time',
        ])->merge($options);
    }

    public function initialize($context, $next)
    {
        $context['collection']->getSchema()
            ->withField($this->options['createdKey'], SchemaDateTime::create())
            ->withField($this->options['updatedKey'], SchemaDateTime::create());

        $next($context);
    }

    public function save($context, $next)
    {
        $now = new NDateTime();

        if ($context['model']->isNew()) {
            $context['model'][$this->options['updatedKey']] = $now;
            $context['model'][$this->options['createdKey']] = $now;
        } else {
            $context['model'][$this->options['updatedKey']] = $now;
        }

        $next($context);
    }
}
