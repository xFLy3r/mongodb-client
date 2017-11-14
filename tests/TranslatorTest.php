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
        $string1 = "select * from test order by field asc";
        $string2 = "select * from test order by field desc";
        $string3 = "select * from test order by field ascdesc";

        $result = $this->translator->setQuery($string1)->getTranslate();
        $this->assertInstanceOf(\MongoDB\Driver\Cursor::class, $result);

        $result = $this->translator->setQuery($string2)->getTranslate();
        $this->assertInstanceOf(\MongoDB\Driver\Cursor::class, $result);

        $result = $this->translator->setQuery($string3)->getTranslate();
        $this->assertFalse($result);
    }
}