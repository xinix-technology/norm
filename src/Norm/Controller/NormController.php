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
            if ($key[0] !== '!') {
                $criteria[$key] = $value;
            }
        }
        return $criteria;
    }

    public function getSort() {
        $sorts = $get = $this->request->get('!sort') ?: array();
        foreach ($sorts as $key => &$value) {
            $value = (int) $value;
        }
        return $sorts;
    }

    public function getSkip() {
        $skip = $this->request->get('!skip') ?: null;
        return $skip;
    }

    public function getLimit() {
        $limit = $this->request->get('!limit') ?: null;
        return $limit;
    }

    public function getMatch() {
        $match = $this->request->get('!match') ?: null;
        return $match;
    }

    public function search() {
        $entries = $this->collection->find($this->getCriteria())
            ->match($this->getMatch())
            ->sort($this->getSort())
            ->skip($this->getSkip())
            ->limit($this->getLimit());

        $this->data['entries'] = $entries;
    }

    public function create() {
        $entry = $this->getCriteria();

        if ($this->request->isPost()) {
            try {
                $entry = array_merge($entry, $this->request->post());
                $model = $this->collection->newInstance();
                $result = $model->set($entry)->save();

                h('controller.create.success', $this);

                $this->flashNow('info', $this->clazz.' created.');
                // $this->redirect($this->getRedirectUri());

            } catch(\Exception $e) {

                h('controller.create.error', $this);

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
        $entry = $this->collection->findOne($id);
        if (isset($entry)) {
            $entry = $entry->toArray();
        }

        if ($this->request->isPost() || $this->request->isPut()) {
            try {
                $entry = array_merge($entry, $this->request->post());
                $model = $this->collection->findOne($id);
                $model->set($entry)->save();

                h('controller.update.success', $this);

                $this->flashNow('info', $this->clazz.' updated.');
                // $this->redirect($this->getRedirectUri());

            } catch(\Exception $e) {

                h('controller.update.error', $this);

                $this->flashNow('error', ''.$e);
            }
        }
        $this->data['entry'] = $entry;
    }

    public function delete($id) {
        $id = explode(',', $id);
        if ($this->request->isPost() || $this->request->isDelete()) {

            $this->data['entries'] = array();
            foreach ($id as $value) {
                $model = $this->collection->findOne($value);
                $model->remove();

                $this->data['entries'][] = $model;
            }

            h('controller.delete.success', $this);

            $this->flashNow('info', $this->clazz.' deleted.');
            // $this->redirect($this->getRedirectUri());
        }

        $this->data['ids'] = $id;
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
