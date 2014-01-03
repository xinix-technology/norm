<?php

namespace Norm\Type;

class DateTime extends \DateTime implements \JsonKit\JsonSerializer {
    public function jsonSerialize() {
        return $this->format('c');
    }
}