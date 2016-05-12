<?php

namespace Norm\Observer;

use Norm\Schema\NReference;
use ROH\Util\Options;
use Norm\Exception\NormException;

class Actorable
{
    protected $options;

    public function __construct($options = [])
    {
        $this->options = (new Options([
            'createdKey' => '$created_by',
            'updatedKey' => '$updated_by',
            'createdField' => [ NReference::class, [
                'to' => 'User',
                'filter' => [],
            ]],
            'updatedField' => [ NReference::class, [
                'to' => 'User',
                'filter' => [],
            ]],
            'userCallback' => [$this, 'defaultUserCallback'],
        ]))->merge($options)->toArray();

        $this->options['createdField'][1]['name'] = $this->options['createdKey'];
        $this->options['updatedField'][1]['name'] = $this->options['updatedKey'];

        if (!is_callable($this->options['userCallback'])) {
            throw new NormException('Actorable needs userCallback as callable');
        }
    }

    public function defaultUserCallback()
    {
        return isset($_SESSION['user']['$id']) ? $_SESSION['user']['$id'] : null;
    }

    public function initialize($context)
    {
        $createdField = $this->options['createdField'];
        $context['collection']->addField($createdField);

        $updatedField = $this->options['updatedField'];
        $context['collection']->addField($updatedField);
    }

    public function save($context, $next)
    {
        $user = $this->options['userCallback']($context);
        if ($context['model']->isNew()) {
            $context['model']['$updated_by'] = $context['model']['$created_by'] = $user;
        } else {
            $context['model']['$updated_by'] = $user;
        }

        $next($context);
    }
}
