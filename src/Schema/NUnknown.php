<?php
namespace Norm\Schema;

use Norm\Repository;
use Norm\Schema;

class NUnknown extends NField
{
    public function __construct(Repository $repository, Schema $schema, array $options = [])
    {
        parent::__construct($repository, $schema, $options);

        $this['unknown'] = true;
    }
}
