<?php

namespace Norm\Schema;

use Exception;
use ArrayAccess;
use Norm\Model;
use Norm\Schema;
use Norm\Collection;
use Norm\Repository;
use Closure;
use InvalidArgumentException;
use ROH\Util\StringFormatter;

class NReference extends NField
{
    protected $options;

    function __construct(Repository $repository, Schema $schema, array $options = [])
    {
        parent::__construct($repository, $schema, $options);

        if (isset($options['to'])) {
            $this->to($options['to']);
        }
    }

    public function to($foreign, $foreignLabel = null)
    {
        if (!is_callable($foreign) &&
            !is_array($foreign) &&
            !is_string($foreign) &&
            !($foreign instanceof Collection)
        ) {
            throw new InvalidArgumentException('Foreign must be instance of string, array, callable or Collection');
        }

        if (!is_null($foreignLabel) && !is_string($foreignLabel) && !is_callable($foreignLabel)) {
            throw new InvalidArgumentException('Foreign label must be instance of string or callable');
        }

        if (is_callable($foreign)) {
            $this['foreign'] = $foreign;
        } elseif (is_array($foreign)) {
            $this->options = $foreign;
            $this['foreign'] = [$this, 'fetchForeign'];
        } else {
            $foreign = explode(':', $foreign);
            $this['foreignCollectionId'] = $foreign[0];
            $this['foreignKey'] = isset($foreign[1]) ? $foreign[1] : '$id';
            $this['foreign'] = [$this, 'fetchForeign'];
        }


        $this['foreignLabel'] = is_callable($foreignLabel) ?
            $foreignLabel :
            function ($model) use ($foreignLabel) {
                if (empty($foreignLabel)) {
                    return $model->format();
                } else {
                    $formatter = new StringFormatter($foreignLabel);
                    if ($formatter->isStatic()) {
                        return $model[$foreignLabel];
                    } else {
                        return $formatter->format($model);
                    }
                }
            };

        return $this;
    }

    public function setSort($sort = [])
    {
        $this['foreignSort'] = $sort;
        return $this;
    }

    public function setCriteria($criteria = [])
    {
        $this['foreignCriteria'] = $criteria;
        return $this;
    }

    public function fetchForeign($id = null)
    {
        if (is_null($this->options)) {
            $this->options = [];

            $cursor = $this->factory($this['foreignCollectionId'])
                ->find($this['foreignCriteria']);
            if ($this['foreignSort']) {
                $cursor->sort($this['foreignSort']);
            }
            foreach ($cursor as $model) {
                $this->options[$model[$this['foreignKey']]] = $this['foreignLabel']($model);
            }
        }

        if (0 === func_num_args()) {
            return $this->options;
        } elseif (is_null($id)) {
            return null;
        } else {
            return $this->options[$id];
        }
    }

    public function prepare($value)
    {
        $value = $value ?: null;

        if (is_array($value) || $value instanceof ArrayAccess) {
            if (isset($value['$id'])) {
                return $value['$id'];
            } else {
                throw new \Exception('Unable to get reference id from value');
            }
        } else {
            return $value;
        }
    }

    public function toJSON($value)
    {
        if (!is_string($this['foreign'])) {
            $foreign = val($this['foreign']);
            if (isset($foreign[$value])) {
                if (is_scalar($foreign[$value])) {
                    return $value;
                } else {
                    return $foreign[$value];
                }
            }
            return null;
        }

        $foreignCollection = Norm::factory($this['foreign']);

        if (Norm::options('include')) {
            $foreignKey = $this['foreignKey'];

            if (is_null($foreignKey)) {
                return $foreignCollection->findOne($value);
            } else {
                return $foreignCollection->findOne(array($this['foreignKey'] => $value));
            }
        }

        return $value;
    }

    public function format($name, $valueOrCallable, $model = null)
    {
        if (is_null($this['foreign'])) {
            throw new Exception('Reference schema should invoke Reference::to()');
        }

        return parent::format($name, $valueOrCallable, $model);
    }

    protected function formatPlain($value, $model = null)
    {
        return $this['foreign']($value);
    }

    protected function formatInput($value, $model = null)
    {
        return $this->render('_schema/reference/input', array(
            'self' => $this,
            'value' => $value,
            'entry' => $model,
        ));
    }
}
