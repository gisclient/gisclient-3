<?php

use GisClient\MapServer\Connection\ConnectionString;
use PHPUnit\Framework\TestCase;

class ConnectionStringTest extends TestCase
{
    public function testSwapsHostAndDatabaseKeepingEverythingElse()
    {
        $in = 'user=mapserver password=secret dbname=app host=pg-rw port=5432';
        $out = ConnectionString::withEndpoint($in, 'pg-ro', 'app_staging');

        $this->assertSame('user=mapserver password=secret dbname=app_staging host=pg-ro port=5432', $out);
    }

    public function testLeavesTheStringAloneWhenNothingIsRequested()
    {
        $in = 'user=mapserver dbname=app host=pg-rw';
        $this->assertSame($in, ConnectionString::withEndpoint($in, null, null));
        $this->assertSame($in, ConnectionString::withEndpoint($in, '', ''));
    }

    public function testCanChangeOnlyTheDatabase()
    {
        $out = ConnectionString::withEndpoint('dbname=app host=pg-rw', null, 'app_staging');
        $this->assertSame('dbname=app_staging host=pg-rw', $out);
    }

    public function testPreservesQuotedValuesContainingSpaces()
    {
        // A naive str_replace would corrupt the password here.
        $in = "user=map password='pa ss word' dbname=app host=pg-rw";
        $out = ConnectionString::withEndpoint($in, 'pg-ro', null);

        $this->assertStringContainsString("password='pa ss word'", $out);
        $this->assertStringContainsString('host=pg-ro', $out);
    }

    public function testPreservesEscapedQuoteInsideAValue()
    {
        $in = "user=map password='it\\'s' dbname=app host=pg-rw";
        $out = ConnectionString::withEndpoint($in, 'pg-ro', null);

        $this->assertStringContainsString("password='it\\'s'", $out);
    }

    public function testAppendsHostWhenTheStringHasNone()
    {
        $out = ConnectionString::withEndpoint('dbname=app user=map', 'pg-ro', null);

        $this->assertStringContainsString('host=pg-ro', $out);
        $this->assertStringContainsString('dbname=app', $out);
    }

    public function testDropsHostaddrWhichWouldOverrideHost()
    {
        // libpq gives hostaddr precedence: leaving it would silently keep the
        // connection on the primary while the config says otherwise.
        $out = ConnectionString::withEndpoint('dbname=app host=pg-rw hostaddr=10.0.0.1', 'pg-ro', null);

        $this->assertStringNotContainsString('hostaddr', $out);
        $this->assertStringContainsString('host=pg-ro', $out);
    }

    public function testMalformedStringIsReturnedUnchanged()
    {
        // Fail-safe: an unparsable connection keeps pointing where it pointed.
        foreach (['this is not a conninfo', 'dbname', "dbname='unterminated"] as $bad) {
            $this->assertSame($bad, ConnectionString::withEndpoint($bad, 'pg-ro', 'app_staging'), $bad);
        }
    }

    public function testRewriteIsIdempotent()
    {
        $once = ConnectionString::withEndpoint('dbname=app host=pg-rw port=5432', 'pg-ro', 'app_staging');
        $twice = ConnectionString::withEndpoint($once, 'pg-ro', 'app_staging');

        $this->assertSame($once, $twice);
    }

    public function testAddsExtraParametersWithoutTouchingCredentials()
    {
        $out = ConnectionString::withParams(
            "user=map password='pa ss' dbname=app host=ro,rw port=5432",
            'target_session_attrs=prefer-standby connect_timeout=2'
        );

        $this->assertStringContainsString('target_session_attrs=prefer-standby', $out);
        $this->assertStringContainsString('connect_timeout=2', $out);
        $this->assertStringContainsString("password='pa ss'", $out);
        $this->assertStringContainsString('host=ro,rw', $out);
    }

    public function testExtraParametersOverrideExistingKeys()
    {
        $out = ConnectionString::withParams('dbname=app host=rw connect_timeout=30', 'connect_timeout=2');

        $this->assertStringContainsString('connect_timeout=2', $out);
        $this->assertStringNotContainsString('connect_timeout=30', $out);
    }

    public function testEmptyParametersLeaveTheStringAlone()
    {
        $in = 'dbname=app host=rw';
        $this->assertSame($in, ConnectionString::withParams($in, null));
        $this->assertSame($in, ConnectionString::withParams($in, '   '));
    }

    public function testRedactHidesThePasswordOnly()
    {
        $out = ConnectionString::redact('user=map password=secret dbname=app host=pg-ro');

        $this->assertStringNotContainsString('secret', $out);
        $this->assertStringContainsString('password=***', $out);
        $this->assertStringContainsString('dbname=app', $out);
        $this->assertStringContainsString('host=pg-ro', $out);
    }
}
