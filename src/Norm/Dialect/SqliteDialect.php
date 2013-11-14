<?php

namespace Norm\Dialect;

class SqliteDialect extends SQLDialect {
    public function listCollections() {
        $statement = $this->raw->query("SELECT * FROM sqlite_master WHERE type='table'");
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $retval = array();
        foreach ($result as $key => $value) {
            $retval[] = $value['name'];
        }
        return $retval;
    }
}