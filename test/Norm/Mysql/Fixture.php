<?php

namespace Norm\Mysql;

class Fixture {
    public static function config($key = '') {
        $config = array(
            'norm.databases' => array(
                'mysql' => array(
                    'driver' => '\\Norm\\Connection\\MysqlConnection',
                    'database' => 'test',
                    'username' => 'root',
                    'password' => 'password',
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