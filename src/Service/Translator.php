<?php
/**
 * Created by PhpStorm.
 * User: xfly3r
 * Date: 07.11.17
 * Time: 21:49
 */

class Translator
{
    const SELECT = 'select';
    const FROM = 'from';
    const WHERE = 'where';
    const ORDER_BY = 'order by';
    const ASC = 'asc';
    const DESC = 'desc';
    const SKIP = 'skip';
    const LIMIT = 'limit';

    const AVAILABLE_OPERATORS = [
        self::SELECT,
        self::FROM,
        self::WHERE,
        self::ORDER_BY,
        self::ASC,
        self::DESC,
        self::SKIP,
        self::LIMIT
    ];

    public function getTranslate(string $string)
    {

    }
}