<?php

namespace Norm\Schema;

class NormArray extends Field {

    public function prepare($value) {

    	if(empty($value)){
    		return '';
    	}

        if (is_string($value)) {
            $value = json_decode($value);
        }

        return new \Norm\Type\NormArray($value);
    }

}