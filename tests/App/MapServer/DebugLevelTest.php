<?php

use GisClient\Author\Utils\DebugLevel;
use PHPUnit\Framework\TestCase;

/**
 * L'interruttore per-richiesta e' l'unico punto in cui un valore che arriva da
 * fuori decide quanto l'applicazione scrive nei log. Le proprieta' che devono
 * reggere sono tre: e' spento se non lo si abilita, puo' solo alzare il
 * livello, e non accetta valori arbitrari.
 */
class DebugLevelTest extends TestCase
{
    protected function setUp(): void
    {
        $this->reset();
    }

    protected function tearDown(): void
    {
        $this->reset();
    }

    private function reset(): void
    {
        putenv('GC_MS_DEBUG_LEVEL');
        putenv('GC_DEBUG_ALLOW_REQUEST');
        unset($_REQUEST[DebugLevel::QUERY_PARAM]);
    }

    public function testDefaultsToErrorsOnly()
    {
        $this->assertSame(DebugLevel::DEFAULT_LEVEL, DebugLevel::resolve());
        $this->assertSame(1, DebugLevel::resolve());
    }

    public function testEnvironmentSetsTheFloor()
    {
        putenv('GC_MS_DEBUG_LEVEL=2');
        $this->assertSame(2, DebugLevel::resolve());
    }

    public function testRequestIsIgnoredUnlessExplicitlyAllowed()
    {
        // ows.php e' pubblico: senza il gate chiunque potrebbe far scrivere
        // nei log la SQL generata.
        $_REQUEST[DebugLevel::QUERY_PARAM] = '5';

        $this->assertSame(1, DebugLevel::resolve());
        $this->assertSame('', DebugLevel::asQueryFragment());
    }

    public function testRequestCanRaiseTheLevelWhenAllowed()
    {
        putenv('GC_DEBUG_ALLOW_REQUEST=1');
        $_REQUEST[DebugLevel::QUERY_PARAM] = '3';

        $this->assertSame(3, DebugLevel::resolve());
        $this->assertSame('GC_DEBUG=3', DebugLevel::asQueryFragment());
    }

    public function testRequestCannotLowerTheLevel()
    {
        // altrimenti una richiesta potrebbe zittire gli errori
        putenv('GC_MS_DEBUG_LEVEL=3');
        putenv('GC_DEBUG_ALLOW_REQUEST=1');
        $_REQUEST[DebugLevel::QUERY_PARAM] = '0';

        $this->assertSame(3, DebugLevel::resolve());
    }

    public function testValueIsClampedToTheMapServerMaximum()
    {
        putenv('GC_DEBUG_ALLOW_REQUEST=1');
        $_REQUEST[DebugLevel::QUERY_PARAM] = '99';

        $this->assertSame(DebugLevel::MAX, DebugLevel::resolve());
        $this->assertSame('GC_DEBUG=5', DebugLevel::asQueryFragment());
    }

    /**
     * @dataProvider rubbishValues
     */
    public function testNonNumericValuesAreIgnored($value)
    {
        putenv('GC_DEBUG_ALLOW_REQUEST=1');
        $_REQUEST[DebugLevel::QUERY_PARAM] = $value;

        $this->assertSame(1, DebugLevel::resolve());
        $this->assertSame('', DebugLevel::asQueryFragment());
    }

    public function rubbishValues()
    {
        return [
            'testo' => ['tre'],
            'negativo' => ['-1'],
            'iniezione' => ['3&FOO=bar'],
            'vuoto' => [''],
            'array' => [['3']],
        ];
    }

    /**
     * @dataProvider truthyValues
     */
    public function testTheGateAcceptsTheUsualSpellings($value)
    {
        putenv('GC_DEBUG_ALLOW_REQUEST=' . $value);
        $this->assertTrue(DebugLevel::isRequestOverrideAllowed(), $value);
    }

    public function truthyValues()
    {
        return [['1'], ['true'], ['TRUE'], ['yes'], ['on'], [' 1 ']];
    }

    /**
     * @dataProvider falsyValues
     */
    public function testTheGateRejectsEverythingElse($value)
    {
        putenv('GC_DEBUG_ALLOW_REQUEST=' . $value);
        $this->assertFalse(DebugLevel::isRequestOverrideAllowed(), $value);
    }

    public function falsyValues()
    {
        return [['0'], ['false'], ['no'], ['off'], [''], ['si']];
    }
}
