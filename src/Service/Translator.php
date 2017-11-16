<?php

require_once 'Validator.php';

class Translator
{
    const SELECT = 'select';
    const FROM = 'from';
    const WHERE = 'where';
    const ORDER_BY = 'order by';
    const SKIP = 'skip';
    const LIMIT = 'limit';

    const AVAILABLE_OPERATORS = [
        self::SELECT,
        self::FROM,
        self::WHERE,
        self::ORDER_BY,
        self::SKIP,
        self::LIMIT
    ];

    /**
     * @var string
     */
    private $sql;

    /**
     * @var \MongoDB\Client
     */
    private $client;

    /**
     * @var \MongoDB\Database
     */
    private $db;

    /**
     * Translator constructor.
     * @param string $uri
     */
    public function __construct(string $uri = "mongodb://localhost:27017")
    {
        $this->client = new MongoDB\Client($uri);
    }

    /**
     * @return array|bool|\MongoDB\Model\CollectionInfoIterator|\MongoDB\Model\DatabaseInfoIterator|string
     */
    public function getTranslate()
    {
        $string = $this->sql;
        $validator = new \Validator();
        $result = $validator->validate($string);

        if ($result === false) {
            return false;
        }

        switch ($result['operation']) {
            case 'select':
                return $this->translateSelect($result['operatorsInQuery'], $string);
            case 'use':
                $this->db = $this->client->selectDatabase($result['db']);
                return "Database " . $result['db'] . " was selected \n";
            case 'db':
                return $this->db ? $this->db->getDatabaseName() : 'No selected db';
            case 'show dbs':
                return $this->client->listDatabases();
            case 'show databases':
                return $this->client->listDatabases();
            case 'show collections':
                return $this->db ? $this->db->listCollections() : 'Select db';
            default:
                return false;
        }

    }

    private function callMethod($operator, $string, $result)
    {
        if ($operator === self::ORDER_BY) {
            $method = 'getValueOfOrderBy';
        } else {
            $method = 'getValueOf'. ucfirst($operator);
        }
        $key = array_search($operator, $result);
        $currentKey = key($result);
        while ($currentKey !== null && $currentKey != $key) {
            next($result);
            $currentKey = key($result);
        }

        $nextKey = array_search(next($result), $result);
        $startIndex = min($key + strlen($operator), $nextKey);

        if ($nextKey === false) {
            $startIndex = $key + strlen($operator);
            $between = substr($string, $startIndex);
        } else {
            $length = abs($key + strlen($operator) - $nextKey);
            $between = substr($string, $startIndex, $length);
        }

        return call_user_func_array(array($this, $method), array($between));
    }

    /**
     * Returns value of operator 'select'
     *
     * @param string $string
     * @param $result
     * @return array
     */
    private function getValueOfSelect(string $string): array
    {
        $string = trim($string);
        if ($string == '*') {
            return [];
        }
        $string = preg_replace('/\s+/', '', $string);
        $fields = explode(',', $string);
        $arr = [];
        foreach ($fields as $field) {
            $arr[$field] = 1;

        }
        return $arr;
    }

    /**
     * Returns value of operator 'from'
     *
     * @param $string
     * @param $result
     * @return string
     */
    private function getValueOfFrom(string $string)
    {
        return trim($string);
    }

    /**
     * Returns value of operator 'where'
     *
     * @param $string
     * @param $result
     * @return array
     */
    private function getValueOfWhere($string): array
    {
        $array = preg_split( "/\s(and|or)\s/", $string);
        preg_match("/\s(and|or)\s/", $string, $matches);
        if (count($matches) > 1) {
            if (trim($matches[1]) === 'and') {
                $matches[1] = '&&';
            } else {
                $matches[1] = '||';
            }
        }
        $str = 'return ';
        foreach ($array as $condition) {
            $newArray = preg_split("/(=|>|>=|<|<=|<>)/", trim($condition), null,  PREG_SPLIT_DELIM_CAPTURE);
            if ($newArray[1] === '=') {
                $newArray[1] = '==';
            }

            $str .= "this.$newArray[0] $newArray[1] $newArray[2]";
            if ($array[0] == $condition and count($array) !== 1 and array_key_exists(1, $matches)) {
                $str .= " $matches[1] ";
            }
        }
        $js = "function() {
                $str;
            }";
        return ['$where' => $js];
    }

    private function getValueOfOrderBy($string)
    {
        $response = [];
        $array = explode(',', $string);
        foreach ($array as $element) {
            $arr = preg_split("/(\sdesc|\sasc)$/", trim($element), -1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            if (count($arr) === 2) {
                if (trim($arr[1]) === 'desc') {
                    $flag = -1;
                } else {
                    $flag = 1;
                }
            } else {
                $flag = 1;
            }
            $response[$arr[0]] =  $flag;

        }

        return $response;
    }

    private function getValueOfSkip($string)
    {
        return (int) $string;
    }

    private function getValueOfLimit($string)
    {
        return (int) $string;
    }

    /**
     * Call methods those translate operators which were found in sql
     *
     * @param $result
     * @param $string
     */
    private function translateSelect($result, $string)
    {
        if (!$this->db) {
            echo 'Select a database, please' . "\n";
            return false;
        }
        $db = $this->client->selectDatabase($this->db);
        $newArray = [];
        $options = [];
        foreach ($result as $operator) {
            $newArray[$operator] = $this->callMethod($operator, $string, $result);
            if ($operator === self::SELECT) {
                $options['projection'] =  $newArray['select'];
            }
            if ($operator === self::ORDER_BY) {
                $options['sort'] = $newArray['order by'];
            }

            if ($operator === self::LIMIT) {
                $options['limit'] = $newArray['limit'];
            }

            if ($operator === self::SKIP) {
                $options['skip'] = $newArray['skip'];
            }
        }
        if (!array_key_exists(self::WHERE, $newArray)) {
            $newArray['where'] = [];
        }

        $collection = $db->selectCollection($newArray['from']);
        $result = $collection->find(
            $newArray['where'],
            $options
        );

        return $result->toArray();
    }

    public function setQuery(string $sql)
    {
        $this->sql = trim(strtolower($sql));

        return $this;
    }
}