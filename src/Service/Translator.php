<?php

require_once 'Validator.php';
require_once 'OperatorInterface.php';

class Translator implements OperatorInterface
{
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
     * @param \MongoDB\Client $client
     * @param string $db
     */
    public function __construct(\MongoDB\Client $client, string $db = null)
    {
        $this->client = $client;
        if ($db) {
            $this->db = $client->selectDatabase($db);
        }
    }

    /**
     * @return array|bool|\MongoDB\Model\CollectionInfoIterator|\MongoDB\Model\DatabaseInfoIterator|string
     */
    public function getTranslate()
    {
        $string = $this->sql;
        $validator = new \Validator();

        if (!$result = $validator->validate($string)) {
            return false;
        }

        switch ($result['cmd']) {
            case 'select':
                return $this->translateSelect($result['values']);
            case 'use':
                $this->db = $this->client->selectDatabase($result['db']);
                return "Database " . $result['db'] . " was selected \n";
            case 'db':
                return $this->db ? $this->db->getDatabaseName() . "\n" : "No selected db \n";
            case 'show dbs':
                return $this->client->listDatabases();
            case 'show databases':
                return $this->client->listDatabases();
            case 'show collections':
                return $this->db ? $this->getCollections() : "Select db \n";
            default:
                return false;
        }
    }

    private function getCollections()
    {
        $collections = [];
        foreach ($this->db->listCollections() as $collection) {
            $collections[] = $collection->getName();
        }

        return $collections;
    }
    private function callMethod($operator, $value)
    {
        if ($operator === self::ORDER_BY) {
            $method = 'getValueOfOrderBy';
        } else {
            $method = 'getValueOf'. ucfirst($operator);
        }

        return call_user_func_array(array($this, $method), array($value));
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
            if (preg_match('/`(.*?)`/', $field, $match)) {
                $field = rtrim($match[1], '.*');
            } else {
                $field = rtrim($field, '.*');
            }
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
     * @param array $result
     * @return array|bool
     */
    private function translateSelect(array $result)
    {
        if (!$this->db) {
            echo "Select a database, please\n";
            return false;
        }

        $values = [];
        $options = [];
        foreach ($result as $operator => $value) {
            $values[$operator] = $this->callMethod($operator, $value);
            if ($operator === self::SELECT) {
                $options['projection'] =  $values['select'];
            }
            if ($operator === self::ORDER_BY) {
                $options['sort'] = $values['order by'];
            }

            if ($operator === self::LIMIT) {
                $options['limit'] = $values['limit'];
            }

            if ($operator === self::SKIP) {
                $options['skip'] = $values['skip'];
            }
        }
        if (!array_key_exists(self::WHERE, $values)) {
            $values['where'] = [];
        }

        $collection = $this->db->selectCollection($values['from']);
        $result = $collection->find(
            $values['where'],
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
