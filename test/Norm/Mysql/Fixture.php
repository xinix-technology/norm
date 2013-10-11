<?php

namespace Norm\Mysql;

class Fixture {
    protected static $config = array(
        'norm.databases' => array(
            'mysql' => array(
                'driver' => '\\Norm\\Connection\\MysqlConnection',
                'database' => 'test',
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
        $username = Fixture::$config['norm.databases']['mysql']['username'];
        $password = Fixture::$config['norm.databases']['mysql']['password'];
        $database = Fixture::$config['norm.databases']['mysql']['database'];
        $sqlFile ='./test/Norm/Mysql/Test.sql';

        $command='mysql -u' .$username .' -p' .$password .' ' .$database .' < ' .$sqlFile;
        echo (exec($command));
    }
}
