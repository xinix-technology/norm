<?php

namespace Norm\Helpers;

class Generator {

    public function __construct(array $options = array()) {
        // Not implemented yet
    }

    public static function genId() {
        return md5(uniqid(time(), true));
    }

}
