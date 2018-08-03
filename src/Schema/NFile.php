<?php

namespace Norm\Schema;

use Norm\Exception\NormException;
use Norm\Collection;
use Norm\Type\File;

class NFile extends NField
{
    protected $dataDir;

    public function __construct(
        Collection $collection,
        $name,
        $dataDir,
        $filter = null,
        array $format = [],
        array $attributes = []
    ) {
        parent::__construct($collection, $name, $filter, $format, $attributes);

        $this->dataDir = $dataDir ?: '';
    }

    public function getDataDir()
    {
        return $this->dataDir;
    }

    public function execPrepare($value)
    {
        // support empty string or null as null value
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof File) {
            if ($value->getBaseDirectory() !== $this->dataDir) {
                throw new NormException('Incompatible file');
            }
            return $value;
        } elseif (is_string($value)) {
            return new File($this->dataDir, $value);
        }
    }
}
