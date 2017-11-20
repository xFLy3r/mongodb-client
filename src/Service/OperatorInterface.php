<?php

interface OperatorInterface {
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
}