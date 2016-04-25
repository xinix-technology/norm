<?php
namespace Norm\Schema;

use Norm\Repository;
use Norm\Schema;

class NUnknown extends NField
{
    public function __construct(Schema $schema = null, $name = '', $filter = null, array $attributes = [])
    {
        parent::__construct($schema, $name, $filter, $attributes);

        $this['unknown'] = true;
    }
}
