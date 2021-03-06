<?php

namespace Zazalt\Omen;

class Omen extends Extension\Database
{
    private $query;
    private $statment;
    private $cache = -1;
    private $results = false;
    private $getOne = false;
    private $getCount = false;

    public function __construct(array $configuration)
    {
        parent::__construct($configuration);
        $this->cache = -1;
    }

    public function isConnected(): bool
    {
        return boolval(is_object($this->connection));
    }

    public function query($query)
    {
        $this->query = $query;

        // Statment
        $this->statment = $this->connection->prepare($query);

        // Singleton
        return $this;
    }

    /**
     * @param   string  $what   [ * | ALL | DISTINCT ]
     * @param   array   $where
     * @param   array   $orderBy
     * @param   array   $limitOffset
     */
    public function getAll($what = '*', $where = [], $orderBy = [], $limitOffset = [])
    {
        // What
        if(is_array($what) && count($what) > 0) {
            if(preg_match('/\((.*)\)/', $what[0], $match)) {
                $what = 'COUNT('. $match[1] .')';
                $this->getCount = true;

            } else if($what[0] == '*') {
                $what = '*';

            } else {
                $what = '"'. implode('","', $what) .'"';
            }
        } else {
            $what = '*';
        }

        $query = "SELECT {$what} FROM {$this->modelName}";

        // Where
        if(is_array($where) && count($where) > 0) {
            $query .= ' WHERE '. $this->whereToString($where);
        }

        // Order by
        if(is_array($orderBy) && count($orderBy) > 0) {
            $query .= " ORDER BY";
            $orderItem = 1;
            foreach ($orderBy as $orderRow => $orderDirection) {
                $query .= ($orderItem > 1) ? ',' : null;
                $query .= ' '. $orderRow .' '. $orderDirection;
                ++$orderItem;
            }
        }

        // Limit
		if (is_array($limitOffset) && count($limitOffset) != 0) {
            if(in_array(strtolower($limitOffset[0]), ['random()'])) {

                $xCount = $this->getAll(['COUNT(id)'], $where)->fetch(\PDO::FETCH_ASSOC);
                $this->getOne = true;
                $query .= " LIMIT 1 OFFSET floor(random()*{$xCount['count']})";

            } else {
                if(count($limitOffset) === 1) {
                    $query .= " LIMIT ". key($limitOffset) ." OFFSET ". current($limitOffset);
                } else {
                    $query .= " LIMIT {$limitOffset[0]} OFFSET {$limitOffset[1]}";
                }
            }
		}

        //die($query);

        // Statment
        $this->statment = $this->connection->prepare($query);

        // Singleton
        return $this;
    }

    /**
     * Example:
     *  [['id', '=', 1]]
     */
    public function whereToString(array $array): string
    {
        $return = ' (';
        foreach($array as $columnData) {

            // AND, OR
            if( count($columnData) == 1 && (is_string($columnData) || is_array($columnData)) ) {
                $return .= " ". (is_string($columnData) ? $columnData : $columnData[0]) ." ";

            // Statement
            } else if(count($columnData) == 3 && is_string($columnData[0])) {
                // Like: "row" =
                $return .= "\"$columnData[0]\" $columnData[1] ";

                // String
                if(is_string($columnData[2])) {
                    if(in_array($columnData[1], array('IN', 'IS', 'IS NOT'))) {
                        $return .= "{$columnData[2]}";
                    } else {
                        $return .= "'{$columnData[2]}'";
                    }

                // Numeric
                } else if(is_int($columnData[2]) || is_numeric($columnData[2])) {
                    $return .= $columnData[2];

                // Bolean
                } else if(is_bool($columnData[2])) {
                    $return .= ($columnData[2]) ? 'true' : 'false';

                // Array
                } else {
                    $return .= '('. implode(',', $columnData[2]) .')';
                }

            // Sub Array
            } else {
                $return .= $this->whereToString($columnData);
            }
        }
        $return .= ') ';

        $reg = [
            'find'      => ['/^\s/', '/\s$/', '/\s+/'],
            'replace'   => ['', '', ' ']
        ];
        return preg_replace($reg['find'], $reg['replace'], $return);
    }

    public function contentToString(array $array): string
    {
        $queryPart = '';
        $itemNo = 1;
        foreach ($array as $value) {
            $queryPart .= $itemNo > 1 ? ',' : null;

            if(!is_bool($value) && !is_array($value) && (is_null($value) || strlen($value) == 0)) {
                $queryPart .= 'NULL';

            } else if(is_string($value)) {
                if(!preg_match("/'/", $value)) {
                    $queryPart .= "'{$value}'";

                } else if(!preg_match('/"/', $value)) {
                    $queryPart .= '"'. $value .'"';

                } else {
                    $queryPart .= "'". pg_escape_string($value) ."'";
                }

            } else if(is_numeric($value)) {
                $queryPart .= $value;

            } else if(is_bool($value)) {
                $queryPart .= (($value) ? 'TRUE' : 'FALSE');
            }

            ++$itemNo;
        }

        return $queryPart;
    }

    public function getOne($what = '*', $where = [], $orderBy = [], $limitOffset = [])
    {
        $this->getOne = true;
        // If limit offset is not set properly
        if(!is_array($limitOffset) || (is_array($limitOffset) && count($limitOffset) == 0)) {
            $limitOffset = [1, 0];
        }

        return $this->getAll($what, $where, $orderBy, $limitOffset);
    }

    public function joinLeft($joinWith, $onWhere = [])
    {

    }

    public function insert($content = array(), $returning = array())
    {
        $query = "INSERT INTO {$this->modelName}(\"". implode('","', array_keys($content)) ."\") VALUES (". $this->contentToString($content) .')';

        if(is_array($returning) && count($returning) > 0) {
            $query .= ' RETURNING '. implode(',', $returning);
        } else {
            $query .= ' RETURNING id';
        }

        if(false && preg_match('/^tags$/i', $this->modelName)) {
            echo $this->modelName .'<br />';
            die($query);
        }

        $statment = $this->connection->prepare($query);
        if($statment->execute()) {
            return $this->afterInsert($content, $statment->fetch(\PDO::FETCH_ASSOC));
        } else {
            return false;
        }
    }

	private function insert2($table, $columns, $rowdicts)
    {
        // columns = array of row columns
        // rowdicts = array of dictionaries
        $placeholders = '';
        foreach($columns as $column) {
            $placeholders = $placeholders . $column . ' = ?, ';
        }
        $placeholders = substr($placeholders, 0, -2);
        $stmt = $this->pdo->prepare('INSERT INTO ' . $table . ' SET ' . $placeholders);
        foreach($rowdicts as $rowdict) {
            $vcount = 0;
            foreach($rowdict as $value) {
                $vcount++;
                $stmt->bindValue($vcount, $value);
            }
            $stmt->execute();
        }
    }

    /**
     * $db->insertRows('images', array(
     *      array( 'title' => 'lorem 1', 'content' => 'ipsum 1' ),
     *      array( 'title' => 'lorem 2', 'content' => 'ipsum 2' )
     * );
     */
    public function insertRows($table, $rowdicts)
    {
        $columns = array_keys($rowdicts[0]);
        $this->insert($table, $columns, $rowdicts);
    }

    /**
     * $db->insertRow('images', array( 'title' => 'lorem', 'content' => 'ipsum' ));
     */
    public function insertRow($table, $rowdict)
    {
        $columns = array_keys($rowdict);
        $this->insert($table, $columns, array($rowdict));
    }

    public function updateRaw($content = [], $where = [])
    {
        $query = "UPDATE {$this->modelName} SET ". implode(', ', array_map(function ($value, $column) {

            if(!is_bool($value) && (is_null($value) || strlen($value) == 0)) {
                $value = 'NULL';

            } else if(is_string($value)) {
                if(!preg_match("/'/", $value)) {
                    $value = "'{$value}'";

                } else if(!preg_match('/"/', $value)) {
                    $value = "'". str_replace("'", "''", $value) ."'";

                } else {
                    $value = "'". pg_escape_string($value) ."'";
                }

            } else if(is_numeric($value)) {
                $value = $value;

            } else if(is_bool($value)) {
                $value = (($value) ? 'TRUE' : 'FALSE');
            }

            return '"'. $column . '" = ' . $value;
        }, $content, array_keys($content)));

        $query .= ' WHERE '. $this->whereToString($where);

        if(false && $this->modelName == 'galleries') {
            die($query);
        }

        $statment = $this->connection->prepare($query);
        if($statment->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function delete(array $where = []): bool
    {
        $query = "DELETE FROM {$this->modelName}";

        $query .= ' WHERE '. $this->whereToString($where);

        if(FALSE && $this->modelName == 'galleries') {
            die($query);
        }

        $statment = $this->connection->prepare($query);
        if($statment->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function getByPK()
    {

    }

    public function cache($TTLSeconds = 60)
    {
		if($this->memcached) {
			$this->cache = $TTLSeconds;
			$cache = $this->memcached->get(sha1($this->statment->queryString));
			if($cache) {
				$this->results = $cache;
			}
		}

        // Singleton
        return $this;
    }

    public final function execute()
    {
        $result = $this->statment->execute();
        $this->reset();
        return $result;
    }

    public final function fetch()
    {
        if(!$this->results) {
            $this->statment->execute();
            $this->results = $this->statment->fetchAll(\PDO::FETCH_ASSOC);

            if($this->cache >= 0) {
                $key = sha1($this->statment->queryString);
                $this->memcached->add($key, $this->results, false, $this->cache);
            }
        }

        $return = $this->results;
        if($this->getOne) {
            $return = $this->results[0];
        } else if($this->getCount) {
            $return = $this->results[0]['count'];
        }

        $this->reset();

        return $return;
    }

    private function reset(): void
    {
        // Clear
        unset($this->getOne, $this->query, $this->statment, $this->results);

        $this->results = null;
        $this->getOne = null;
        $this->getCount = null;
    }
}