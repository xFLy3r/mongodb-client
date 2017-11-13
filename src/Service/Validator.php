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

    public function validate(string $string)
    {
        if ($this->checkOperation($string) === false) {
            return 'Syntax error';
        }
        if ($this->isValidSelect($string, []) === false || $this->isValidFrom($string, []) === false) {
            return 'Syntax error';
        }

        $operatorsInQuery = [];

        foreach (self::AVAILABLE_OPERATORS as $operator) {
            if (substr_count($string, $operator) > 0 ) {
                if (substr_count($string, $operator) > 1) {
                    return 'Fuq. Duplicate';
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
            return 'Syntax error. Check priority of operators';
        }

        foreach ($operatorsInQuery as $operator) {
            if ($this->callMethod($operator, $string, $operatorsInQuery) === false) {
                return 'not today';
            };
        }

        return $operatorsInQuery;
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

        if ($operator === self::ORDER_BY) {
            $method = 'isValidOrderBy';
        } else {
            $method = 'isValid'. ucfirst($operator);
        }
        return call_user_func_array(array($this, $method), array($string, $between));
    }

    /**
     * Checks what is operation in use
     *
     * @param string $string
     * @return bool
     */
    private function checkOperation(string $string): bool
    {
        if (strpos($string, self::SELECT) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Checks is valid operator 'select'
     *
     * @param $string
     * @return bool
     */
    private function isValidSelect(string $string, $between): bool
    {
        if (preg_match("/select(.*?)from/", $string, $matches)) {
            if (trim($matches[1]) === '*') {
                return true;
            }
            $fields = explode(',', $matches[1]);
            foreach ($fields as $field) {
                if (!preg_match("/^[a-z0-9\s]*$/", $field)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Checks is valid operator 'from'
     *
     * @param $string
     * @param $array
     * @return bool
     */
    private function isValidFrom($string, $array)
    {
        if ($pos = strpos($string, self::FROM)) {
            if (
                ((substr($string, $pos - 1, 1) === '*') ||
                    (substr($string, $pos - 1, 1) === ' '))
            &&
                (substr($string, $pos + 4, 1) === ' ')
            ) {
                return true;
            }
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
     * @param $string
     * @param $array
     * @return bool
     */
    private function isValidWhere(string $string, string $between): bool
    {
        $array = preg_split( "/\s(and|or)\s/", $between);
        if (!$this->isValidValueOfWhere($array)) {
            return false;
        }

        return true;
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

    private function isValidOrderBy(string $string, string $between)
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

        return true;
    }
}
