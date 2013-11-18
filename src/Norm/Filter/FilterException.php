<?php

namespace Norm\Filter;

class FilterException extends \RuntimeException {

    protected $name;
    protected $sub;

    public static function factory($message) {
        return new static($message);
    }

    public function name($name) {
        $this->name = $name;
        return $this;
    }

    public function sub($sub) {
        $this->sub = $sub;
        return $this;
    }

    public function __toString() {
        $str = '';
        if (is_array($this->sub)) {
            foreach ($this->sub as $c) {
                $str .= '<p>'.$c."</p>\n";
            }
        } else {
            $str .= sprintf($this->getMessage(), $this->name);
        }
        return $str;
    }
}