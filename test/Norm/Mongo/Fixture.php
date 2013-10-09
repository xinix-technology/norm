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
            'norm.schemas' => array(
                'User' => array(
                    'username' => new \Norm\Schema\String(),
                    'password' => new \Norm\Schema\String(),
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