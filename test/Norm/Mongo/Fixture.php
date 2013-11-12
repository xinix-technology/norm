<?php

namespace Norm\Mongo;

use Norm\Norm;

class Fixture {

    protected static $config = array(
        'norm.databases' => array(
            'mongo' => array(
                'driver' => '\\Norm\\Connection\\MongoConnection',
                'database' => 'test',
            ),
        ),
    );

    public static function config($key = '') {
        if (empty($key)) {
            return Fixture::$config;
        } else {
            return Fixture::$config[$key];
        }
    }

    public static function init() {
        Norm::reset();
        Norm::init(Fixture::config('norm.databases'));
        $connection = Norm::getConnection();

        $raw = $connection->getRaw();
        $raw->drop();
        $raw->createCollection("user", false);

        $raw->user->insert(array(
            "firstName" => "putra",
            "lastName" => "pramana",
        ));

        return $connection;
    }
}