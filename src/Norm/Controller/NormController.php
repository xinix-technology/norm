<?php

namespace Norm\Controller;

use \Bono\Controller\RestController;
use \Norm\Norm;

class NormController extends RestController
{

    protected $collection;

    public function __construct($app, $uri)
    {
        parent::__construct($app, $uri);

        $this->collection = Norm::factory($this->clazz);
    }

    public function getCriteria()
    {
        $gets = $this->request->get();

        $criteria = array();
        foreach ($gets as $key => $value) {
            if ($key[0] !== '!') {
                $criteria[$key] = $value;
            }
        }
        return $criteria;
    }

    public function getSort()
    {
        $sorts = $get = $this->request->get('!sort') ?: array();
        foreach ($sorts as $key => &$value) {
            $value = (int) $value;
        }
        return $sorts;
    }

    public function getSkip()
    {
        $skip = $this->request->get('!skip') ?: null;
        return $skip;
    }

    public function getLimit()
    {
        $limit = $this->request->get('!limit');

        if (!isset($limit) && isset($this->collection->options['limit'])) {
            $limit = $this->collection->options['limit'];
        }

        return $limit;
    }

    public function getMatch()
    {
        $match = $this->request->get('!match') ?: null;
        return $match;
    }

    public function search()
    {
        $entries = $this->collection->find($this->getCriteria())
            ->match($this->getMatch())
            ->sort($this->getSort())
            ->skip($this->getSkip())
            ->limit($this->getLimit());

        $this->data['entries'] = $entries;
    }

    public function create()
    {
        $entry = $this->getCriteria();

        if ($this->request->isPost()) {
            try {
                $entry = array_merge($entry, $this->request->post());
                $model = $this->collection->newInstance();
                $result = $model->set($entry)->save();

                $entry = $model;

                h('notification.info', $this->clazz.' created.');

                h('controller.create.success', array(
                    'model' => $model
                ));

            } catch (\Slim\Exception\Stop $e) {
                throw $e;
            } catch (\Exception $e) {

                h('notification.error', $e);

                h('controller.create.error', array(
                    'model' => $model,
                    'error' => $e,
                ));

                // $this->flashNow('error', $e);
            }

        }

        $this->data['entry'] = $entry;
    }

    public function read($id)
    {
        $found = false;

        $this->data['entry'] = $entry = $this->collection->findOne($id);
        if (isset($entry)) {
            $found = true;
        }

        if (!$found) {
            return $this->app->notFound();
        }
    }

    public function update($id)
    {
        $found = false;
        try {
            $entry = $this->collection->findOne($id);
            if (isset($entry)) {
                $found = true;
            }
        } catch (\Exception $e) {

        }

        if (!$found) {
            return $this->app->notFound();
        }

        if (isset($entry)) {
            $entry = $entry->toArray();
        }

        if ($this->request->isPost() || $this->request->isPut()) {

            try {
                $entry = array_merge($entry, $this->request->post());
                $model = $this->collection->findOne($id);
                $model->set($entry)->save();

                $entry = $model;

                h('notification.info', $this->clazz.' updated');

                h('controller.update.success', array(
                    'model' => $model,
                ));
            } catch (\Slim\Exception\Stop $e) {
                throw $e;
            } catch (\Exception $e) {

                h('notification.error', $e);

                h('controller.update.error', array(
                    'error' => $e,
                    'model' => $model,
                ));
            }
        }
        $this->data['entry'] = $entry;
    }

    public function delete($id)
    {
        $id = explode(',', $id);
        if ($this->request->isPost() || $this->request->isDelete()) {

            $single = false;
            if (count($id) === 1) {
                $single = true;
            }

            $this->data['entries'] = array();
            foreach ($id as $value) {
                $model = $this->collection->findOne($value);

                if (is_null($model)) {
                    if ($single) {
                        $this->app->notFound();
                    }
                    continue;
                }

                $model->remove();

                $this->data['entries'][] = $model;
            }

            h('notification.info', $this->clazz.' deleted.');

            h('controller.delete.success', array(
                'models' => $this->data['entries'],
            ));
        }

        // $this->data['ids'] = $id;
    }

    public function schema($schema = null)
    {
        if (func_num_args() === 0) {
            return $this->collection->schema();
        }
        return $this->collection->schema($schema);
    }
}
