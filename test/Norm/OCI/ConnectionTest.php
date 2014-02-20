<?php

namespace Norm\OCI;

use Norm\Norm;
use Norm\Collection;
use Norm\Model;
use Norm\Schema\String;
use Norm\Schema\DateTime;
use Norm\Schema\Text;
use Norm\Schema\Integer;

require_once('Fixture.php');

class ConnectionTest extends \PHPUnit_Framework_TestCase {
    private $connection;


    public function setUp() {
        $config = array(
            'norm.databases' => array(
                'oracle' => array(
                    'driver' => '\\Norm\\Connection\\OCIConnection',
                    'dbname' => '//192.168.1.10:1521/xinix',
                    'username' => 'jams',
                    'password' => 'password'
                )
            ),
            // 'norm.databases' => array(
            //     'mongo' => array(
            //         'driver' => '\\Norm\\Connection\\MongoConnection',
            //         'database' => 'jams',
            //     ),
            // ),
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

        Norm::init($config['norm.databases'],$config['norm.collections']);
    }

    // public function testConnection(){
    //     $user = Norm::factory('Users');
    //     $this->assertNotEmpty($user);
    // }

    // public function testInsert() {
    //     $name = 'Putra';
    //     $address = 'Bekasi';
    //     $country = 'Indonesia';
    //     $last_login = time();

    //     $collection = Norm::factory('Test');

    //     $model = $collection->newInstance();
    //     $model->set('name', $name);
    //     $model->set('address', $address);
    //     $model->set('country', $country);
    //     $model->set('last_login', $last_login);
    //     $result = $model->save();

    //     $this->assertNotEmpty($result, 'is return not empty');
        
    //     $model = $collection->findOne($model->getId());

    //     $this->assertEquals($model->get('name'), $name, 'has valid name field.');
    //     $this->assertEquals($model->get('address'), $address, 'has valid address field.');
    //     $this->assertEquals($model->get('country'), $country, 'has valid country field.');
    // }

    // public function testUpdate() {
    //     $name = 'Joko';

    //     $collection = Norm::factory('Test');
    //     $model = $collection->findOne(array( '$id' => 19 ));

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

    //     $model = $collection->findOne(array( 'id' => 28 ));
    //     $id = $model->getId();
    //     $model->remove();
        
    //     $this->assertNull($model->getId(), 'will lost model id after remove.');

    //     $model = $collection->findOne(array(
    //         'id' => $id
    //     ));

    //     $this->assertNull($model, 'is null after deleted');
    // }

    // public function testSort(){
    //     $collection = Norm::factory('Test');

    //     $sortParam = array(
    //         'id' => 1
    //     );

    //     $cursor = $collection->find()->sort($sortParam);

    //     $data = array();
    //     foreach ($cursor as $key => $value) {
    //         $data[] = $value->toArray();
    //     }

    //     foreach ($data as $key => $v) {
    //         if(!isset($data[$key+1])){
    //             break;
    //         }

    //         if($sortParam['id'] === 1){
    //             $this->assertLessThan($data[$key+1]['$id'], $v['$id']);
    //         } else {
    //             $this->assertGreaterThan($data[$key+1]['$id'], $v['$id']);
    //         }
    //     }
    // }

}








