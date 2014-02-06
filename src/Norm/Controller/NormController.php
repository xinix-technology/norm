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

    public function getCriteria() {
        $gets = $this->request->get();
        $criteria = array();
        foreach ($gets as $key => $value) {
            if ($key[0] != '@') {
                $criteria[$key] = $value;
            }
        }
        return $criteria;
    }

    public function getSort() {
        $get = $this->request->get('@sort');
        $get = explode(',', $get);
        $sorts = array();
        foreach ($get as $value) {
            $value = trim($value);
            $value = explode(':', $value);
            if (!empty($value[0])) {
                $sorts[$value[0]] = (isset($value[1])) ? (int) $value[1] : 1;
            }
        }
        return $sorts;
    }

    public function search() {
        $entries = $this->collection->find($this->getCriteria())->sort($this->getSort());

        $this->data['entries'] = $entries;
    }

    public function create() {
        $entry = $this->getCriteria();

        if ($this->request->isPost()) {
            try {
                $entry = array_merge($entry, $this->request->post());
                $model = $this->collection->newInstance();
                $result = $model->set($entry)->save();

                $this->flash('info', $this->clazz.' created.');
                $this->redirect($this->getRedirectUri());
            } catch(\Exception $e) {
                $this->data['entry'] = $entry;
                $this->flashNow('error', ''.$e);
            }

        }

        $this->data['entry'] = $entry;
    }

    public function read($id) {
        $this->data['entry'] = $this->collection->findOne($id);

        if (is_null($this->data['entry'])) {
            $this->app->notFound();
        }
    }

    public function update($id) {

        if ($this->request->isPost() || $this->request->isPut()) {
            $entry = $this->getCriteria();

            try {
                $entry = array_merge($entry, $this->request->post());
                $model = $this->collection->findOne($id);
                $model->set($entry)->save();
                $this->flash('info', $this->clazz.' updated.');
                $this->redirect($this->getRedirectUri());
            } catch(\Exception $e) {
                $this->data['entry'] = $entry;
                $this->flashNow('error', ''.$e);
            }
            $this->data['entry'] = $entry;
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
            $this->redirect($this->getRedirectUri());
        }
    }

    public function getRedirectUri() {
        $continue = $this->request->get('@continue');
        if (empty($continue)) {
            return $this->getBaseUri();
        } else {
            return $continue;
        }
    }
}
