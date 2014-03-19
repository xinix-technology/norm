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
                            'country' => String::getInstance('country')
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

    //     $collection = Norm::factory('Test');

    //     $model = $collection->newInstance();
    //     $model->set('name', $name);
    //     $model->set('address', $address);
    //     $model->set('country', $country);
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
    //     $model = $collection->findOne(array( '$id' => 1 ));

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

    //     $model = $collection->findOne(array( 'id' => 1 ));
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
    //         'id' => 13
    //     );

    //     $cursor = $collection->find()->sort($sortParam)->limit(3);

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

    // public function testLimit(){
    //     $collection = Norm::factory('Test');

    //     $max = 8;
    //     $cursor = $collection->find()->limit($max);

    //     $data = array();
    //     foreach ($cursor as $key => $value) {
    //         $data[] = $value->toArray();
    //     }

    //     $this->assertEquals($max, count($data), 'has valid count data.');
    // }

    // public function testSkip(){
    //     $collection = Norm::factory('Test');

    //     $s = 10;
    //     $cursor = $collection->find()->skip($s);

    //     $data = array();
    //     foreach ($cursor as $key => $value) {
    //         $data[] = $value->toArray();
    //     }

    //     $this->assertLessThan($data[0]['rnum'], $s);
    // }

    public function testCount(){
        $collection = Norm::factory('Test');

        $cursor = $collection->find()->count();

        var_dump($cursor);
        exit();
    }
}








