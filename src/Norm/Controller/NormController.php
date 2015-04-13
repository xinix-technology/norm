<?php

namespace Norm\Controller;

use Norm\Norm;
use Bono\Controller\RestController;
use ROH\Util\Inflector;

class NormController extends RestController
{

    protected $collection;

    protected $routeModels = array();

    public function __construct($app, $uri)
    {
        parent::__construct($app, $uri);

        $this->collection = Norm::factory($this->clazz);
    }

    public function mapRoute()
    {
        parent::mapRoute();

        $this->map('/null/schema', 'getSchema')->via('GET');
    }

    public function getCriteria()
    {
        $gets = $this->request->get();

        if (empty($this->routeData)) {
            $criteria = array();
        } else {
            $criteria = $this->routeData;
        }
        foreach ($gets as $key => $value) {
            if ($key[0] !== '!') {
                $criteria[$key] = $value;
            }
        }

        $criteria = array_merge($criteria,$this->getOr());

        return $criteria;
    }


    public function getOr(){
        $or = $this->request->get('!or') ? array("!or" => $this->request->get('!or')): array();
        return $or;
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

        if (is_null($limit) && !is_null($this->collection->option('limit'))) {
            $limit = $this->collection->option('limit');
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
        $entry = $this->collection->newInstance()->set($this->getCriteria());

        if ($this->request->isPost()) {
            try {
                $result = $entry->set($this->request->getBody())->save();

                h('notification.info', $this->clazz.' created.');

                h('controller.create.success', array(
                    'model' => $entry
                ));

            } catch (\Slim\Exception\Stop $e) {
                throw $e;
            } catch (\Exception $e) {

                h('notification.error', $e);

                h('controller.create.error', array(
                    'model' => $entry,
                    'error' => $e,
                ));
            }

        }

        $this->data['entry'] = $entry;
    }

    public function read($id)
    {
        $found = false;

        try {
            $this->data['entry'] = $entry = $this->collection->findOne($id);
        } catch (\Exception $e) {
        }
        if (isset($entry)) {
            $found = true;
        }

        if (!$found) {
            return $this->app->notFound();
        }
    }

    public function update($id)
    {
        try {
            $entry = $this->collection->findOne($id);
        } catch (\Exception $e) {
        }

        if (is_null($entry)) {
            return $this->app->notFound();
        }

        if ($this->request->isPost() || $this->request->isPut()) {
            try {
                $merged = array_merge(
                    isset($entry) ? $entry->dump() : array(),
                    $this->request->getBody() ?: array()
                );
                $entry->set($merged)->save();

                h('notification.info', $this->clazz.' updated');

                h('controller.update.success', array(
                    'model' => $entry,
                ));
            } catch (\Slim\Exception\Stop $e) {
                throw $e;
            } catch (\Exception $e) {
                h('notification.error', $e);

                if (empty($entry)) {
                    $model = null;
                }

                h('controller.update.error', array(
                    'error' => $e,
                    'model' => $entry,
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

            try {

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

            } catch (\Slim\Exception\Stop $e) {
                throw $e;
            } catch (\Exception $e) {
                h('notification.error', $e);

                if (empty($model)) {
                    $model = null;
                }

                h('controller.delete.error', array(
                    'error' => $e,
                    'model' => $model,
                ));
            }

        }
    }

    /**
     * @see Bono\Controller\RestController
     */
    public function schema($schema = null)
    {
        if (func_num_args() === 0) {
            return $this->collection->schema();
        }
        return $this->collection->schema($schema);
    }

    public function getSchema()
    {
        $schema = $this->schema();
        $this->data['schema'] = $schema;
        // foreach ($schema as $key => $value) {
        //     $entry = array(
        //         'class' => get_class($value)
        //     );

        //     foreach ($value as $attrKey => $attrValue) {
        //         $entry[$attrKey] = $attrValue;
        //     }

        //     $this->data['schema'][$key] = $entry;
        // }
        // var_dump($schema);
        // exit;
    }

    public function routeModel($key) {
        if (!isset($this->routeModels[$key])) {
            $Clazz = Inflector::classify($key);

            $collection = Norm::factory($this->schema($key)->get('foreign'));


            $this->routeModels[$key] = $collection->findOne($this->routeData($key));
        }

        return $this->routeModels[$key];
    }
}
