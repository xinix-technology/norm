<?php

namespace Norm\Schema;

class DateTime extends Field {
    public function prepare($value) {
        if ($value instanceof \DateTime) {
            return $value->format('c');
        } elseif (is_string($value)) {
            return date('c', strtotime($value));
        }
        return date('c', (int) $value);
    }
}