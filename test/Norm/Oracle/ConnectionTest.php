<?php

namespace Norm\Oracle;

use Norm\Norm;
use Norm\Collection;
use Norm\Model;
use Norm\Schema\String;
use Norm\Schema\Text;
use Norm\Schema\Integer;

require_once('Fixture.php');

class ConnectionTest extends \PHPUnit_Framework_TestCase {
    private $connection;


    public function setUp() {
        $config = array(
            'norm.databases' => array(
                'oracle' => array(
                    'driver' => '\\Norm\\Connection\\PDOConnection',
                    'prefix' => 'oci',
                    'dbname' => '//192.168.1.128:1521/orcl',
                    'username' => 'proddgipr',
                    'password' => 'proddgipr',
                    'dialect' => '\\Norm\\Dialect\\OracleDialect'
                )
            ),
            'norm.collections' => array(
                'mapping' => array(
                    'Test' => array(
                        'schema' => array(
                            'id' => Integer::getInstance('id'),
                            'name' => String::getInstance('name'),
                            'address' => Text::getInstance('address'),
                            'country' => String::getInstance('country'),
                        ),
                    )
                ),
            ),
        );

        Norm::init($config['norm.databases'],$config['norm.collections']);
    }

    // public function testInsert() {
    //     $name = 'Putra';
    //     $address = 'Bekasi';
    //     $country = 'Indonesia';

    //     $collection = Norm::factory('Test');

    //     $model = $collection->newInstance();
    //     $model->set('name', $name);
    //     $model->set('address', $address);
    //     $model->set('country', $country);
    //     $result = $model->save();

    //     $this->assertNotEmpty($result, 'is return not empty');
        
    //     $model = $collection->findOne(array(
    //         'id' => $model->getId()
    //     ));
        
    //     $this->assertEquals($model->get('name'), $name, 'has valid name field.');
    //     $this->assertEquals($model->get('address'), $address, 'has valid address field.');
    //     $this->assertEquals($model->get('country'), $country, 'has valid country field.');
    // }

    // public function testUpdate() {
    //     $name = 'Putra';

    //     $collection = Norm::factory('Test');
    //     $model = $collection->findOne(array( 'id' => 1 ));
    //     $model->set('name', $name);
    //     $result = $model->save();

    //     $this->assertNotEmpty($result, 'is return not empty');

    //     $model = $collection->findOne(array(
    //         'id' => $model->getId()
    //     ));

    //     $this->assertEquals($model->get('name'), $name, 'has valid lastName field.');
    // }

    // public function testRemove() {

    //     $collection = Norm::factory('Test');

    //     $model = $collection->findOne(array( 'id' => 5 ));
    //     $id = $model->getId();
    //     $model->remove();
        
    //     $this->assertNull($model->getId(), 'will lost model id after remove.');

    //     $model = $collection->findOne(array(
    //         'id' => $id
    //     ));

    //     $this->assertNull($model, 'is null after deleted');
    // }

}