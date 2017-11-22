<?php
namespace Norm\Schema;

use Norm\Collection;

class NUnknown extends NField
{
    public function __construct(
        Collection $collection,
        $name,
        $filter = null,
        array $format = [],
        array $attributes = []
    ) {
        parent::__construct($collection, $name, $filter, $format, $attributes);

        $this['unknown'] = true;
    }
}
