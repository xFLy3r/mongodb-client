<?php

class Validator
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

    private $db;

    public function __construct(?string $db = null)
    {
        $this->db = $db;
    }

    public function validate(string $string)
    {
        $operation = $this->checkOperation($string);
        if ($operation === false) {
            return false;
        }

        switch ($operation) {
            case self::SELECT:
                return $this->validateSelect($string);
            case 'use':
                return $this->validateUse($string);
            case 'db':
                return [
                    'operation' => 'db',
                ];
            case 'show dbs':
                return [
                    'operation' => 'show dbs',
                ];
            case 'show databases':
                return [
                    'operation' => 'show dbs',
                ];
            case 'show collections':
                return [
                    'operation' => 'show collections',
                ];
            default:
                return false;
        }
    }

    private function validateUse($string)
    {
        preg_match("/^use\s([a-z]+)$/", $string, $matches);
        if (!$matches[1]) {
            return false;
        }
        return [
            'operation' => 'use',
            'db' => $matches[1],
        ];
    }
    private function validateSelect($string)
    {
        $operatorsInQuery = [];

        foreach (self::AVAILABLE_OPERATORS as $operator) {
            if (substr_count($string, $operator) > 0 ) {
                if (substr_count($string, $operator) > 1) {;
                    return false;
                }

                if ($operator !== self::SELECT  && $operator !== self::FROM) {
                    if (!preg_match("/\s$operator\s/", $string)) {
                        return false;
                    }
                }
                if (!preg_match("/\`($operator)\`/", $string)) {
                    $operatorsInQuery[strpos($string, $operator)] = $operator;
                }
            }
        }
        ksort($operatorsInQuery);
        if (!$this->checkOrderOfOperators($operatorsInQuery)) {
            return false;
        }

        $arr = [];
        foreach ($operatorsInQuery as $operator) {
            $value = $this->callMethod($operator, $string, $operatorsInQuery);
            if ($value === false) {
                return false;
            };
            $arr[$operator] = $value;
        }
        return [
            'operation' => self::SELECT,
            'operatorsInQuery' => $operatorsInQuery
        ];
    }
    private function callMethod($operator, $string, $result)
    {
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

        if (strlen(trim($between)) === 0) {
            echo "Check operator $operator \n";
            return false;
        }

        if ($operator === self::ORDER_BY) {
            $method = 'isValidOrderBy';
        } else {
            $method = 'isValid'. ucfirst($operator);
        }

        return call_user_func_array(array($this, $method), array($between));
    }

    /**
     * Checks what is operation in use
     *
     * @param string $string
     * @return bool
     */
    private function checkOperation(string $string)
    {

        if (strpos($string, self::SELECT) === 0) {
            return self::SELECT;
        }

        if (strpos($string, 'use') === 0) {
            return 'use';
        }

        if (strpos($string, 'show collections') === 0) {
            return 'show collections';
        }

        if (strpos($string, 'show dbs')  === 0 or strpos($string, 'show databases') === 0) {
            return 'show dbs';
        }

        if (strpos($string, 'db') === 0) {
            return 'db';
        }

        return false;
    }

    /**
     * Checks is valid operator 'select'
     *
     * @param $between
     * @return bool
     */
    private function isValidSelect($between)
    {
        if (trim($between) === '*') {
            return $between;
        }
        $fields = explode(',', $between);
        foreach ($fields as $field) {
            if (!preg_match("/^[a-z0-9\s]*$/", $field)) {
                return false;
            }
        }

        return $between;
    }

    /**
     * Checks is valid operator 'from'
     *
     * @param $string
     * @param $array
     * @return bool
     */
    private function isValidFrom($between)
    {
        preg_match("/^\s[a-z]+/", $between, $matches);
        if ($matches[0]) {
            return trim($between);
        }

        return false;
    }

    /**
     * @param $operators
     * @return bool
     */
    private function checkOrderOfOperators(array $operators): bool
    {
        $arrayWithIntersect  = array_values(array_intersect(self::AVAILABLE_OPERATORS, array_values($operators)));

        return (array_values($operators) === $arrayWithIntersect) ? true : false;
    }

    /**
     * @param $between
     * @param $array
     * @return bool
     */
    private function isValidWhere(string $between): bool
    {
        $array = preg_split( "/\s(and|or)\s/", $between);
        if (!$this->isValidValueOfWhere($array)) {
            return false;
        }

        return $between;
    }

    /**
     * Checks is valid value of 'where' operator
     *
     * @param $array
     * @return bool
     */
    private function isValidValueOfWhere(array $array): bool
    {
        foreach ($array as $condition) {
            $condition = trim($condition);
            if(preg_match("/^|\'|\`[a-z][0-9]\s|\'|\`(=|>|>=|<|<=|<>)[a-z0-9]$/", $condition)) {
                $newArray = preg_split("/=|>|>=|<|<=|<>/", $condition);
                if (count($newArray) !== 2) {
                    return false;
                }
                if (!preg_match("/(?:(?:\"(?:\\\\\"|[\"])+\")|(?:'(?:\\\'|[^'])+'))/",trim($newArray[0]))) {
                    if (preg_match("/\s/", trim($newArray[0]))) {
                        return false;
                    }
                }

                if (!preg_match("/\'|(.*?)\'|/",trim($newArray[1]))) {
                    if (preg_match("/[a-z0-9]/", trim($newArray[1]))) {
                        return false;
                    }
                }
                if (!is_numeric($newArray[1])) {
                    if (!preg_match("/(?:(?:\"(?:\\\\\"|[\"])+\")|(?:'(?:\\\'|[^'])+'))/",trim($newArray[1]))) {
                        return false;
                    }
                }
            }
            else {
                return false;
            }
        };

        return true;
    }

    private function isValidOrderBy(string $between)
    {
        $array = explode(',', $between);
        foreach ($array as $element) {
            $arr = preg_split("/(\sdesc|\sasc)$/", trim($element), -1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            if (count($arr) > 2 or count($arr) < 1 or preg_match("/\s/", trim($arr[0]))) {
                return false;
            }
            if (count($arr) === 3) {
                if (substr($arr[1], -1, 3) !== 'asc' or substr($arr[1], -1, 4) !== 'desc') {
                    return false;
                }
            }
        }

        return $between;
    }

    private function isValidSkip(string $between): bool
    {
        if (ctype_digit(trim($between))) {
            return $between;
        }

        return false;
    }

    private function isValidLimit(string $between): bool
    {
        $between = trim($between);
        if (ctype_digit($between)) {
            return $between;
        }
        preg_match("/^(\d+,\s\d+|\d+,\d+)$/", $between, $matches);
        if ($matches[1]) {
            return $between;
        }

        return false;
    }
}
