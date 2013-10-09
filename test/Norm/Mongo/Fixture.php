<?php

namespace Norm\Mongo;

class Fixture {
    public static function config($key = '') {
        $config = array(
            'norm.databases' => array(
                'mongo' => array(
                    'driver' => '\\Norm\\Connection\\MongoConnection',
                    'database' => 'test',
                ),
            ),
        );

        if (empty($key)) {
            return $config;
        } else {
            return $config[$key];
        }
    }
}