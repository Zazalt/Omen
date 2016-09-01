<?php

namespace Zazalt\Omen;

class Omen extends Extension\Database
{
    private $query;
    private $statment;
    private $cache = -1;
    private $results = false;
    private $getOne = false;

    public function __construct(Array $configuration)
    {
        parent::__construct($configuration);
        $this->cache = -1;
    }

    public function isConnected()
    {
        return is_object($this->connection);
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
            $i = 1;
            foreach ($orderBy as $orderRow => $orderDirection) {
                $query .= ($i > 1) ? ',' : null;
                $query .= ' '. $orderRow .' '. $orderDirection;
                ++$i;
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

        if(FALSE && $this->modelName == 'images') {
            die($query);
        }

        // Statment
        $this->statment = $this->connection->prepare($query);

        // Singleton
        return $this;
    }

    public function whereToString($array)
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

    public function contentToString($array)
    {
        $q = '';
        $i = 1;
        foreach ($array as $value) {
            $q .= $i > 1 ? ',' : null;

            if(!is_bool($value) && !is_array($value) && (is_null($value) || strlen($value) == 0)) {
                $q .= 'NULL';

            } else if(is_string($value)) {
                if(!preg_match("/'/", $value)) {
                    $q .= "'{$value}'";

                } else if(!preg_match('/"/', $value)) {
                    $q .= '"'. $value .'"';

                } else {
                    $q .= "'". pg_escape_string($value) ."'";
                }

            } else if(is_numeric($value)) {
                $q .= $value;

            } else if(is_bool($value)) {
                $q .= (($value) ? 'TRUE' : 'FALSE');
            }

            ++$i;
        }

        return $q;
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
        $q = "INSERT INTO {$this->modelName}(\"". implode('","', array_keys($content)) ."\") VALUES (". $this->contentToString($content) .')';

        if(is_array($returning) && count($returning) > 0) {
            $q .= ' RETURNING '. implode(',', $returning);
        } else {
            $q .= ' RETURNING id';
        }

        if(false && preg_match('/^tags$/i', $this->modelName)) {
            echo $this->modelName .'<br />';
            die($q);
        }

        $statment = $this->connection->prepare($q);
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

    public function update($content = [], $where = [])
    {
        $q = "UPDATE {$this->modelName} SET ". implode(', ', array_map(function ($value, $column) {

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

        $q .= ' WHERE '. $this->whereToString($where);

        if(false && $this->modelName == 'galleries') {
            die($q);
        }

        $statment = $this->connection->prepare($q);
        if($statment->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function delete($where = [])
    {
        $q = "DELETE FROM {$this->modelName}";

        $q .= ' WHERE '. $this->whereToString($where);

        if(FALSE && $this->modelName == 'galleries') {
            die($q);
        }

        $statment = $this->connection->prepare($q);
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

    public function execute()
    {
        $this->statment->execute();
    }

    public function fetch()
    {
        if(!$this->results) {
            $this->statment->execute();
            $this->results = $this->statment->fetchAll(\PDO::FETCH_ASSOC);

            if($this->cache >= 0) {
                $key = sha1($this->statment->queryString);
                $this->memcached->add($key, $this->results, false, $this->cache);
            }
        }

        $return = ($this->getOne) ? $this->results[0] : $this->results;

        // Clear
        unset($this->getOne, $this->query, $this->statment, $this->results, $this);

        return $return;
    }
}