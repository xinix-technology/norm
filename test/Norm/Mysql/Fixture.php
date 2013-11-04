<?php

namespace Norm\Mysql;

use Norm\Norm;

class Fixture {
    protected static $config = array(
        'norm.databases' => array(
            'mysql' => array(
                'driver' => '\\Norm\\Connection\\PDOConnection',
                'prefix' => 'mysql',
                'dbname' => 'test',
                'username' => 'root',
                'password' => 'password',
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

        $raw->exec("DROP TABLE IF EXISTS user");
        $raw->exec("
            CREATE TABLE IF NOT EXISTS user (
                id int(11) NOT NULL AUTO_INCREMENT,
                firstName varchar(255),
                lastName varchar(255),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB
        ");

        $raw->exec("INSERT INTO user(firstName, lastName) VALUES('putra', 'pramana')");

        return $connection;
    }
}
