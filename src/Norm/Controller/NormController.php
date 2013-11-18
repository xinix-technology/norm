<?php

namespace Norm\Controller;

use \Bono\Controller\RestController;
use \Norm\Norm;

class NormController extends RestController {

    protected $collection;

    public function __construct($app, $uri) {
        parent::__construct($app, $uri);

        $this->collection = Norm::factory($this->clazz);
    }

    public function search() {
        $entries = $this->collection->find($this->request->get());

        $this->data['entries'] = $entries;
    }

    public function create() {
        if ($this->request->isPost()) {
            try {
                $model = $this->collection->newInstance();
                $result = $model->set($this->data['entry'])->save();
                $this->flash('info', $this->clazz.' created.');
                $this->redirect($this->getBaseUri());
            } catch(\Exception $e) {
                $this->flashNow('error', ''.$e);
            }

        }
    }

    public function read($id) {
        $this->data['entry'] = $this->collection->findOne($id);

        if (is_null($this->data['entry'])) {
            $this->app->notFound();
        }
    }

    public function update($id) {
        if ($this->request->isPost() || $this->request->isPut()) {

            try {
                $model = $this->collection->findOne($id);
                $model->set($this->data['entry'])->save();
                $this->flash('info', $this->clazz.' updated.');
                $this->redirect($this->getBaseUri());
            } catch(\Exception $e) {
                $this->flashNow('error', ''.$e);
            }

        } else {
            $model = $this->collection->findOne($id);
            $this->data['entry'] = $model;
        }
    }

    public function delete($id) {
        if ($this->request->isPost() || $this->request->isDelete()) {
            $model = $this->collection->findOne($id);
            $model->remove();

            $this->flash('info', $this->clazz.' deleted.');
            $this->redirect($this->getBaseUri());
        }
    }
}