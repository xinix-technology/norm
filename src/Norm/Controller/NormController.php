<?php

namespace Norm\Controller;

use \Bono\Controller\RestController;
use \Norm\Norm;

class NormController extends RestController {

    protected $collection;

    public function __construct($app, $uri) {
        parent::__construct($app, $uri);


        $this->collection = Norm::factory($this->clazz);

        $this->data['_schema'] = $this->collection->schema;

    }

    public function search() {
        $entries = $this->collection->find($this->request->get());

        $this->data['_actions'] = array(
            'update' => NULL,
            'delete' => NULL,
        );

        $this->data['entries'] = $entries;
    }

    public function create() {
        if ($this->request->isPost()) {
            // FIXME reekoheek validation
            unset($this->data['entry']['password_retype']);

            $model = $this->collection->newInstance();
            $model->set($this->data['entry'])->save();

            $this->redirect($this->getBaseUri());
        }
    }

    public function read($id) {

    }

    public function update($id) {
        if ($this->request->isPost()) {
            // FIXME reekoheek validation
            unset($this->data['entry']['password']);
            unset($this->data['entry']['password_retype']);

            $model = $this->collection->findOne($id);
            $model->set($this->data['entry'])->save();

            $this->redirect($this->getBaseUri());
        } else {
            $model = $this->collection->findOne($id);
            $this->data['entry'] = $model;
        }
    }

    public function delete($id) {
        if ($this->request->isPost()) {
            $model = $this->collection->findOne($id);
            $model->remove();

            $this->flash('info', 'Deleted.');
            $this->redirect($this->getBaseUri());
        }
    }
}