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

    public function getTranslate(string $string)
    {
        $string = trim(strtolower($string));
        $validator = new \Validator();
        $result = $validator->validate($string);
        if (!is_array($result)) {
            throw new Exception('Syntax error');
        }

        return $this->translate($result, $string);
    }

    private function callMethod($method, $string, $result)
    {
        return call_user_func_array(array($this, $method), array($string, $result));
    }

    /**
     * Returns value of operator 'select'
     *
     * @param string $string
     * @param $result
     * @return array
     */
    private function getValueOfSelect(string $string, $result): array
    {
        preg_match("/select(.*?)from/", $string, $matches);
        if ($matches[1] == '*') {
            return [];
        }
        trim($string);
        $string = preg_replace('/\s+/', '', $matches[1]);
        $fields = explode(',', $string);
        $arr = [];
        foreach ($fields as $field) {
            $arr[$field] = 1;

        }
        return [
            'projection' => $arr
        ];
    }

    /**
     * Returns value of operator 'from'
     *
     * @param $string
     * @param $result
     * @return string
     */
    private function getValueOfFrom($string, $result)
    {
        $arrayWithDefaultKeys = array_values($result);
        $key = array_search(self::FROM, $arrayWithDefaultKeys);
        if (array_key_exists($key+1, $arrayWithDefaultKeys)) {
            $next = $arrayWithDefaultKeys[$key+1];
            preg_match("/from(.*?)$next/", $string, $matches);
        } else {
            preg_match("/from(.*?)$/", $string, $matches);
        }
        return trim($matches[1]);
    }

    /**
     * Returns value of operator 'where'
     *
     * @param $string
     * @param $result
     * @return array
     */
    private function getValueOfWhere($string, $result): array
    {
        $arrayWithDefaultKeys = array_values($result);
        $key = array_search(self::WHERE, $arrayWithDefaultKeys);
        if (array_key_exists($key+1, $arrayWithDefaultKeys)) {
            $next = $arrayWithDefaultKeys[$key+1];
            preg_match("/where(.*?)$next/", $string, $matches);
        } else {
            preg_match("/where(.*?)$/", $string, $matches);
        }
        $array = preg_split( "/\s(and|or)\s/", $matches[1]);
        preg_match("/\s(and|or)\s/", $string, $matches);
        //var_dump($matches[1]);
        if ($matches[1] === 'and') {
            $matches[1] = '&&';
        } else {
            $matches[1] = '||';
        }
        $str = 'return ';
        foreach ($array as $condition) {
            $newArray = preg_split("/(=|>|>=|<|<=|<>)/", trim($condition), null,  PREG_SPLIT_DELIM_CAPTURE);
            if ($newArray[1] === '=') {
                $newArray[1] = '==';
            }

            $str .= "this.$newArray[0] $newArray[1] $newArray[2]";
            if ($array[0] == $condition and count($array) !== 1) {
                $str .= " $matches[1] ";
            }
            //var_dump($newArray);
        }
        echo $str;
        $js = "function() {
                $str;
            }";
        return ['$where' => $js];
    }

    /**
     * Call methods those translate operators which were found in sql
     *
     * @param $result
     * @param $string
     * @return array
     */
    private function translate($result, $string)
    {

        $newArray = [];
        foreach ($result as $operator) {
            $method = 'getValueOf'. ucfirst($operator);
            $newArray[$operator] = $this->callMethod($method, $string, $result);

        }
        var_dump($newArray);
        if (!array_key_exists(self::WHERE, $newArray)) {
            $newArray['where'] = [];
        }

        return [
            'document' => $newArray['from'],
            'filter' => $newArray['where'],
            'options' => $newArray['select'],

        ];
    }
}