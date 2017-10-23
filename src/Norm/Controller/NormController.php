<?php namespace Norm\Controller;

use Exception;
use Norm\Norm;
use ROH\Util\Inflector;
use Slim\Exception\Stop;
use Bono\Controller\RestController;

/**
 * Controller used by Bono App to integrate with NORM easily.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class NormController extends RestController
{
    /**
     * Collection for current request.
     *
     * @var \Norm\Collection
     */
    protected $collection;

    /**
     * Route that has a model attached in it.
     *
     * @var array
     */
    protected $routeModels = array();

    /**
     * Class constructor.
     *
     * @param \Bono\App $app
     * @param string    $uri
     */
    public function __construct($app, $uri)
    {
        parent::__construct($app, $uri);

        $this->collection = Norm::factory($this->clazz);
    }

    /**
     * Map route schema
     *
     * @return void
     */
    public function mapRoute()
    {
        parent::mapRoute();

        $this->map('/null/schema', 'getSchema')->via('GET');
    }

    /**
     * Get criteria of current request
     *
     * @return array
     */
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

    /**
     * Get **or** criteria of current request.
     *
     * @return array
     */
    public function getOr()
    {
        $or = array();
        
        
        if($this->request->get('!or')){
            foreach ($this->request->get('!or') as $key => $value) {
                if(is_array($value)){
                    foreach($value as $k => $v){
                        $or[] = array($key => $v);
                    }
                }else{
                    $or[] = array($key => $value);    
                }
                
            }

            $or = array("!or" => $or);
        }

        return $or;
    }

    /**
     * Get **sort** command of current request.
     *
     * @return array
     */
    public function getSort()
    {
        $sorts = $get = $this->request->get('!sort') ?: array();

        foreach ($sorts as $key => &$value) {
            $value = (int) $value;
        }

        return $sorts;
    }

    /**
     * Get **skip** command of current request.
     *
     * @return array
     */
    public function getSkip()
    {
        $skip = $this->request->get('!skip') ?: null;

        return $skip;
    }

    /**
     * Get **limit** command of current request.
     *
     * @return array
     */
    public function getLimit()
    {
        $limit = $this->request->get('!limit');

        if (is_null($limit) && !is_null($this->collection->option('limit'))) {
            $limit = $this->collection->option('limit');
        }

        return $limit;
    }

    /**
     * Get **match** criteria of current request.
     *
     * @return array
     */
    public function getMatch()
    {
        $match = $this->request->get('!match') ?: null;

        return $match;
    }

    /**
     * Handle **search / listing** request.
     *
     * @return void
     */
    public function search()
    {
        $entries = $this->collection->find($this->getCriteria())
            ->match($this->getMatch())
            ->sort($this->getSort())
            ->skip($this->getSkip())
            ->limit($this->getLimit());

        $this->data['entries'] = $entries;
    }

    /**
     * Handle creation of new document.
     *
     * @return void
     */
    public function create()
    {
        $entry = $this->collection->newInstance()->set($this->getCriteria());

        $this->data['entry'] = $entry;

        if ($this->request->isPost()) {
            try {
                $result = $entry->set($this->request->getBody())->save();

                h('notification.info', $this->clazz.' created.');

                h('controller.create.success', array(
                    'model' => $entry
                ));
            } catch (Stop $e) {
                throw $e;
            } catch (Exception $e) {
                // no more set notification.error since notificationmiddleware will
                // write this later
                // h('notification.error', $e);

                h('controller.create.error', array(
                    'model' => $entry,
                    'error' => $e,
                ));

                // rethrow error to make sure notificationmiddleware know what todo
                throw $e;
            }
        }

    }

    /**
     * Show a document **detail** by an ID.
     *
     * @param mixed $id
     *
     * @return void
     */
    public function read($id)
    {
        $found = false;

        try {
            $this->data['entry'] = $entry = $this->collection->findOne($id);
        } catch (Exception $e) {
            // noop
        }

        if (isset($entry)) {
            $found = true;
        }

        if (! $found) {
            return $this->app->notFound();
        }
    }

    /**
     * Perform **updating** a document.
     *
     * @param mixed $id
     *
     * @return void
     */
    public function update($id)
    {
        try {
            $entry = $this->collection->findOne($id);
        } catch (Exception $e) {
            // noop
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
            } catch (Stop $e) {
                throw $e;
            } catch (Exception $e) {
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

    /**
     * Perform **deletion** of a document by an ID given.
     *
     * @param mixed $id
     *
     * @return void
     */
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

            } catch (Stop $e) {
                throw $e;
            } catch (Exception $e) {
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
     * Get schema of collection
     *
     * @param string|null $schema
     *
     * @return mixed
     */
    public function schema($schema = null)
    {
        if (func_num_args() === 0) {
            return $this->collection->schema();
        }

        return $this->collection->schema($schema);
    }

    /**
     * Get data schema attached to this class.
     *
     * @return array
     */
    public function getSchema()
    {
        $schema = $this->schema();

        $this->data['schema'] = $schema;
    }

    /**
     * Register a route model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function routeModel($key)
    {
        if (! isset($this->routeModels[$key])) {
            $Clazz = Inflector::classify($key);

            $collection = Norm::factory($this->schema($key)->get('foreign'));

            $this->routeModels[$key] = $collection->findOne($this->routeData($key));
        }

        return $this->routeModels[$key];
    }
}
