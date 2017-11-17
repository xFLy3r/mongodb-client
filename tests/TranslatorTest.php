<?php

require __DIR__ . '/../src/Service/Translator.php';

use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{
    /**
     * @var Translator
     */
    private $translator;

    protected function setUp()
    {
        $this->translator = new Translator();
    }

    protected function tearDown()
    {
        $this->translator = NULL;
    }

    public function testGetTranslate()
    {
        $string = "use test";
        /** Set test db */
        $this->translator->setQuery($string)->getTranslate();

        $string = "select * from test order by field asc";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertInternalType('array', $result);

        $string = "select * from test order by field desc";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertInternalType('array', $result);

        $string = "select * from test order by field asc desc";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "select ** from test";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "select * from  5";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "select from test order by name where a=1";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "order by name select * from test";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "select * from select";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "select * from `select`SU;";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "select * from test where item=test";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertFalse($result);

        $string = "select * from test where item='test'";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertInternalType('array', $result);

        $string = "select * from test where item=4";
        $result = $this->translator->setQuery($string)->getTranslate();
        $this->assertInternalType('array', $result);

    }
}