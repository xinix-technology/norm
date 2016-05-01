<?php
namespace Norm\Dialect;

use Norm\Cursor;
use Norm\Collection;
use Norm\Exception\NormException;

abstract class Sql
{
    static protected $OPERATORS = [
        '' => '=',
        'eq' => '=',
        'ne' => '!=',
        'lt' => '<',
        'lte' => '<=',
        'gt' => '>',
        'gte' => '>=',
    ];

    public function esc($str)
    {
        return $str;
    }

    public function grammarInsert($collectionId, $data)
    {
        $fields = [];
        $placeholders = [];

        foreach ($data as $key => $value) {
            $fields[] = $this->esc($key);
            $placeholders[] = ':'.$key;
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->esc($collectionId),
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
    }

    public function grammarSelect($collectionId)
    {
        return sprintf(
            'SELECT %s FROM %s',
            '*',
            $this->esc($collectionId)
        );
    }

    public function grammarUpdate($collectionId, $data)
    {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = $key.' = :'.$key;
        }

        return sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->esc($collectionId),
            implode(', ', $sets)
        );
    }

    public function grammarWhere(array $criteria = [])
    {
        if (empty($criteria)) {
            return '';
        }
        $wheres = [];
        foreach ($criteria as $key => $value) {
            @list($field, $op) = explode('!', $key);
            if (!isset(static::$OPERATORS[$op])) {
                throw new NormException('Operator '.$op.' not defined yet');
            }
            $wheres[] = $field.' '.static::$OPERATORS[$op].' :'.$field;
        }
        return 'WHERE '.implode(' AND ', $wheres);
    }

    public function grammarDelete($collectionId, array $criteria = [])
    {
        return sprintf(
            'DELETE FROM %s %s',
            $this->esc($collectionId),
            $this->grammarWhere($criteria)
        );
    }

    public function grammarCount($collectionId, array $criteria = [], array $sort = [], $skip = 0, $limit = -1)
    {
        $exprs = [ 'SELECT COUNT(*) AS ' . $this->esc('count') ];
        $exprs[] = 'FROM ' . $this->esc($collectionId);
        $exprs[] = $this->grammarWhere($criteria);
        $exprs[] = $this->grammarOrder($sort);
        $exprs[] = $this->grammarLimit($skip, $limit);

        return implode(' ', array_filter($exprs));
    }

    public function grammarOrder(array $sort)
    {
        if (empty($sort)) {
            return '';
        }

        $sorts = [];
        foreach ($sort as $field => $flow) {
            $sorts[] = sprintf('%s %s', $this->esc($field), $flow === 1 ? 'ASC' : 'DESC');
        }
        return sprintf('ORDER BY %s', implode(',', $sorts));
    }

    public function grammarLimit($skip, $limit)
    {
        if ($limit < 0) {
            return '';
        }
        return sprintf('LIMIT %s, %s', $skip, $limit);
    }

    public function grammarDistinct($collectionId, $key, array $criteria = [])
    {
        $exprs = [ 'SELECT DISTINCT ' . $this->esc($key) ];
        $exprs[] = 'FROM ' . $this->esc($collectionId);
        $exprs[] = $this->grammarWhere($criteria);

        return implode(' ', array_filter($exprs));
    }

    // public function grammarExpression($collectionId, $options = [])
    // {
    //     throw new NormException('Unimplemented');
    // }

    // public function grammarDDL($collectionId, $options = [])
    // {
    //     throw new NormException('Unimplemented');
    // }
}
