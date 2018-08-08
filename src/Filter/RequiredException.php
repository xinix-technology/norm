<?php

namespace Norm\Filter;

use Norm\Exception\FilterException;

class RequiredException extends FilterException
{
    public function __construct(string $field)
    {
        parent::__construct('Field is required');

        $this->setField($field);
    }
}
