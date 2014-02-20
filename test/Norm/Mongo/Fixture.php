<?php

namespace Norm\Mongo;

use Norm\Norm;
use Norm\Schema\String;
use Norm\Schema\DateTime;
use Norm\Schema\Text;
use Norm\Schema\Integer;

class Fixture {

    protected static $config;

    public static function config($key = '') {
        if (!isset(static::$config)) {
            static::$config = array(
                'norm.databases' => array(
                    'mongo' => array(
                        'driver' => '\\Norm\\Connection\\MongoConnection',
                        'database' => 'test',
                    ),
                ),
                'norm.collections' => array(
                    'default' => array(
                        'observers' => array(
                            '\\Norm\\Observer\\Ownership' => array(),
                            '\\Norm\\Observer\\Timestampable' => array(),
                        ),
                    ),
                    'mapping' => array(
                        'Test' => array(
                            'schema' => array(
                                'name' => String::getInstance('name'),
                                'address' => Text::getInstance('address'),
                                'country' => String::getInstance('country'),
                                'last_login' => DateTime::getInstance('last_login'),
                            ),
                        )
                    ),
                ),
            );
        }
        if (empty($key)) {
            return Fixture::$config;
        } else {
            return Fixture::$config[$key];
        }
    }

    public static function init() {
        Norm::reset();

        Norm::init(Fixture::config('norm.databases'), Fixture::config('norm.collections'));
        $connection = Norm::getConnection();

        $raw = $connection->getRaw();
        $raw->drop();
        $raw->createCollection("user", false);

        $raw->user->insert(array(
            "firstName" => "putra",
            "lastName" => "pramana",
        ));

        $raw->user->insert(array(
            "firstName" => "farid",
            "lastName" => "hidayat",
        ));

        $raw->user->insert(array(
            "firstName" => "pendi",
            "lastName" => "setiawan",
        ));

        return $connection;
    }
}