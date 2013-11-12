<?php

namespace Norm\Sqlite;

use Norm\Norm;

class Fixture {
    protected static $config = array(
        'norm.databases' => array(
            'sqlite' => array(
                'driver' => '\\Norm\\Connection\\PDOConnection',
                'prefix' => 'sqlite',
                'database' => 'test.sqlite3',
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
              id INTEGER PRIMARY KEY,
              firstName TEXT,
              lastName TEXT
            )");

        $raw->exec("INSERT INTO user(firstName, lastName) VALUES('putra', 'pramana')");

        return $connection;
    }
}
