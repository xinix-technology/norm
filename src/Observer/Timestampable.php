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
        $this->options = Options::create([
            'createdKey' => '$created_time',
            'updatedKey' => '$updated_time',
        ])->merge($options);
    }

    public function initialize($context, $next)
    {
        $context['collection']->getSchema()
            ->addField([ NDateTime::class, [
                'options' => [
                    'name' => $this->options['createdKey']
                ]
            ]])
            ->addField([ NDateTime::class, [
                'options' => [
                    'name' => $this->options['updatedKey']
                ]
            ]]);

        $next($context);
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
