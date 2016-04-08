<?php
namespace Norm\Dialect;

use Exception;

class Sql
{
    public function esc($str)
    {
        return $str;
    }

    public function grammarInsert($collectionName, $data)
    {
        $fields = [];
        $placeholders = [];

        foreach ($data as $key => $value) {
            $fields[] = $this->esc($key);
            $placeholders[] = ':'.$key;
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->esc($collectionName),
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
    }

    public function grammarSelect($collectionName)
    {
        return sprintf(
            'SELECT %s FROM %s',
            '*',
            $this->esc($collectionName)
        );
    }

    public function grammarUpdate($collectionName, $data)
    {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = $key.' = :'.$key;
        }

        return sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->esc($collectionName),
            implode(', ', $sets)
        );
    }

    public function grammarDelete($collectionName, $id)
    {
        if (is_array($id)) {
            throw new Exception('Unimplemented yet');
        } else {
            return sprintf(
                'DELETE FROM %s WHERE %s = :id',
                $this->esc($collectionName),
                $this->esc('id')
            );
        }
    }

    public function grammarCount($collectionName, $options = [])
    {
        throw new Exception('Unimplemented');
    }

    public function grammarDistinct($collectionName, $options = [])
    {
        throw new Exception('Unimplemented');
    }

    public function grammarExpression($collectionName, $options = [])
    {
        throw new Exception('Unimplemented');
    }

    public function grammarDDL($collectionName, $options = [])
    {
        throw new Exception('Unimplemented');
    }
}
