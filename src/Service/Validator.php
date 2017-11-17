<?php

class Validator
{
    const SELECT = 'select';
    const FROM = 'from';
    const WHERE = 'where';
    const ORDER_BY = 'order by';
    const SKIP = 'skip';
    const LIMIT = 'limit';
    const USE = 'use';
    const DB = 'db';
    const SHOW_COLLECTIONS = 'show collections';
    const SHOW_DBS = 'show dbs';
    const SHOW_DATABASES = 'show databases';


    const AVAILABLE_OPERATORS = [
        self::SELECT,
        self::FROM,
        self::WHERE,
        self::ORDER_BY,
        self::SKIP,
        self::LIMIT
    ];

    /**
     * Validate string. If string is valid returns array, if not - false
     *
     * @param string $string
     * @return array|bool
     */
    public function validate(string $string)
    {

        if (!$cmd = $this->checkCommand($string)) {
            return false;
        }

        switch ($cmd) {
            case self::SELECT:
                return $this->validateSelect($string);
            case self::USE:
                return $this->validateUse($string);
            case self::DB:
                return [
                    'cmd' => 'db',
                ];
            case self::SHOW_DATABASES:
                return [
                    'cmd' => 'show dbs',
                ];
            case self::SHOW_DBS:
                return [
                    'cmd' => 'show dbs',
                ];
            case self::SHOW_COLLECTIONS:
                return [
                    'cmd' => 'show collections',
                ];
            default:
                return false;
        }
    }

    /**
     * Checks what is operation in use
     *
     * @param string $string
     * @return bool
     */
    private function checkCommand(string $string)
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
     * Validates cmd use
     *
     * @param $string
     * @return array|bool
     */
    private function validateUse(string $string)
    {
        preg_match("/^use\s([a-z]+)$/", $string, $matches);
        if (!$matches[1]) {
            return false;
        }
        return [
            'cmd' => 'use',
            'db' => $matches[1],
        ];
    }

    /**
     * Validates cmd select. If is valid returns array like ['select' => valueOfSelect], etc.
     *
     * @param $string
     * @return array|bool
     */
    private function validateSelect(string $string)
    {
        $operatorsInQuery = [];

        /**
         * Getting operators in select query (from, where, etc)
         */
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

        $arrWithValuesOfOperators = [];

        /**
         * Validating every operator in select query
         */
        foreach ($operatorsInQuery as $operator) {
            $value = $this->callMethod($operator, $string, $operatorsInQuery);
            if ($value === false) {
                return false;
            };
            $arrWithValuesOfOperators[$operator] = $value;
        }

        return [
            'cmd' => self::SELECT,
            'values' => $arrWithValuesOfOperators
        ];
    }

    /**
     * @param string $operator
     * @param string $string
     * @param array $operatorsInQuery
     * @return bool|mixed
     */
    private function callMethod(string $operator, string $string, array $operatorsInQuery)
    {
        $key = array_search($operator, $operatorsInQuery);
        $currentKey = key($operatorsInQuery);
        while ($currentKey !== null && $currentKey != $key) {
            next($operatorsInQuery);
            $currentKey = key($operatorsInQuery);
        }

        $nextKey = array_search(next($operatorsInQuery), $operatorsInQuery);
        $startIndex = min($key + strlen($operator), $nextKey);

        if ($nextKey === false) {
            $startIndex = $key + strlen($operator);
            $between = substr($string, $startIndex);
        } else {
            $length = abs($key + strlen($operator) - $nextKey);
            $between = substr($string, $startIndex, $length);
        }

        if (strlen(trim($between)) === 0) {
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
     * Checks is valid operator 'select'
     *
     * @param string$between
     * @return bool|string
     */
    private function isValidSelect(string $between)
    {
        if (trim($between) === '*') {
            return $between;
        }
        $fields = explode(',', $between);

        foreach ($fields as $field) {
            $field = trim($field);
            if (!preg_match(
                "/^[a-z0-9]*$|^([a-z0-9]+.[a-z0-9]+)$|`([a-z0-9]+.[a-z0-9]+)`$|^`([a-z0-9]+.[*]+)`$|^([a-z0-9]+.[*]+)$/",
                $field))  {
                return false;
            }
        }

        return $between;
    }

    /**
     * Checks is valid operator 'from'
     *
     * @param string $between
     * @return bool|string
     */
    private function isValidFrom(string $between)
    {
        preg_match("/^\s[a-z]+/", $between, $matches);
        // TODO: validation
        if (preg_match("/\s([a-z]+)/", $between, $matches) or preg_match("/\s`([a-z`]+)`/", $between, $matches)) {
            return trim($matches[0]);
        }

        return false;
    }

    /**
     * Check order of operators in sql
     *
     * @param $operators
     * @return bool
     */
    private function checkOrderOfOperators(array $operators): bool
    {
        $arrayWithIntersect  = array_values(array_intersect(self::AVAILABLE_OPERATORS, array_values($operators)));

        return (array_values($operators) === $arrayWithIntersect) ? true : false;
    }

    /**
     * Checks is valid operator 'where'
     *
     * @param $between
     * @return bool|string
     */
    private function isValidWhere(string $between)
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

    /**
     * Checks is valid operator 'Order by'
     *
     * @param string $between
     * @return bool|string
     */
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

    /**
     * Checks is valid operator skip
     *
     * @param string $between
     * @return bool|string
     */
    private function isValidSkip(string $between)
    {
        if (ctype_digit(trim($between))) {
            return $between;
        }

        return false;
    }

    /**
     * Checks is valid operator limit
     *
     * @param string $between
     * @return bool|string
     */
    private function isValidLimit(string $between)
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
