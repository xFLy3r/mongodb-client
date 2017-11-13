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

        $result = $this->translator->getTranslate($string1);
        $this->assertInternalType('array', $result);

        $result = $this->translator->getTranslate($string2);
        $this->assertInternalType('array', $result);

        $result = $this->translator->getTranslate($string3);
        $this->assertFalse($result);
    }
}