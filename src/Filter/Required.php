<?php

namespace Norm\Filter;

use Norm\FilterContext;

class Required
{
    public function __invoke($value, FilterContext $ctx)
    {
        if (null === $value || '' === $value) {
            throw new RequiredException($ctx->getField()->getName());
        }

        return $value;
    }
}
