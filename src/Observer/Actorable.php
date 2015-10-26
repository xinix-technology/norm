<?php

namespace Norm\Observer;

use Norm\Schema\Reference;
use ROH\Util\Options;

class Actorable
{
    protected $options;

    public function __construct($options = [])
    {
        $this->options = Options::create([
            'createdKey' => '$created_by',
            'updatedKey' => '$updated_by',
            'createdField' => Reference::create()->to('User'),
            'updatedField' => Reference::create()->to('User'),
            'userCallback' => function () {
                return isset($_SESSION['user']['$id']) ? $_SESSION['user']['$id'] : null;
            }
        ])->merge($options);

        if (!is_callable($this->options['userCallback'])) {
            throw new InvalidArgumentException('Actorable needs userCallback as callable');
        }
    }

    public function initialize($context)
    {

        $context['collection']->getSchema()
            ->withField($this->options['createdKey'], $this->options['createdField'])
            ->withField($this->options['updatedKey'], $this->options['updatedField']);
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
