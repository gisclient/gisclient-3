<?php

use GisClient\MapServer\Connection\ConnectionString;
use PHPUnit\Framework\TestCase;

/**
 * Il tetto sulla singola query viaggia dentro la stringa di connessione, non
 * come impostazione di ruolo: SET ROLE non riapplica le impostazioni del ruolo
 * impersonato, quindi un ALTER ROLE non avrebbe effetto sui layer RLS. Qui si
 * verifica che il parametro sopravviva alla riscrittura della connessione e
 * che non corrompa nulla di quanto c'era gia'.
 *
 * MapDatabaseRouter legge costanti definite in config.db.php, che in un test
 * unitario non sono ridefinibili: si esercita quindi la composizione a valle,
 * cioe' quello che il router produce e passa a ConnectionString.
 */
class MapDatabaseRouterParamsTest extends TestCase
{
    private const TIMEOUT_PARAM = "options='-c statement_timeout=20000'";

    public function testStatementTimeoutSurvivesTheRewrite()
    {
        $in = 'user=map password=secret dbname=app host=pg-rw port=5432';

        $out = ConnectionString::withParams($in, self::TIMEOUT_PARAM);

        $this->assertSame('-c statement_timeout=20000', ConnectionString::valueOf($out, 'options'));
        $this->assertSame('secret', ConnectionString::valueOf($out, 'password'));
        $this->assertSame('pg-rw', ConnectionString::valueOf($out, 'host'));
    }

    public function testStatementTimeoutDoesNotCorruptAQuotedPassword()
    {
        // Il valore di options contiene uno spazio e va quotato: senza un
        // parser vero, aggiungerlo spezzerebbe la password quotata accanto.
        $in = "user=map password='pa ss word' dbname=app host=pg-rw";

        $out = ConnectionString::withParams($in, self::TIMEOUT_PARAM);

        $this->assertSame('pa ss word', ConnectionString::valueOf($out, 'password'));
        $this->assertSame('-c statement_timeout=20000', ConnectionString::valueOf($out, 'options'));
    }

    public function testDeploymentParamsAreAppliedTogetherWithTheTimeout()
    {
        // Come li compone connectionParams(): tetto prima, parametri del
        // deployment dopo.
        $params = self::TIMEOUT_PARAM . ' target_session_attrs=prefer-standby connect_timeout=2';

        $out = ConnectionString::withParams('dbname=app host=pg-rw', $params);

        $this->assertSame('-c statement_timeout=20000', ConnectionString::valueOf($out, 'options'));
        $this->assertSame('prefer-standby', ConnectionString::valueOf($out, 'target_session_attrs'));
        $this->assertSame('2', ConnectionString::valueOf($out, 'connect_timeout'));
    }

    public function testDeploymentCanOverrideTheTimeoutOnPurpose()
    {
        // libpq tratta "options" come una stringa unica: la chiave viene
        // sostituita, non fusa. E' il motivo per cui il tetto va messo per
        // primo — l'ultimo valore vince.
        $params = self::TIMEOUT_PARAM . " options='-c statement_timeout=5000'";

        $out = ConnectionString::withParams('dbname=app host=pg-rw', $params);

        $this->assertSame('-c statement_timeout=5000', ConnectionString::valueOf($out, 'options'));
    }

    public function testAnExistingOptionsValueIsReplacedNotAppended()
    {
        $in = "dbname=app host=pg-rw options='-c work_mem=64MB'";

        $out = ConnectionString::withParams($in, self::TIMEOUT_PARAM);

        $this->assertSame('-c statement_timeout=20000', ConnectionString::valueOf($out, 'options'));
        $this->assertStringNotContainsString('work_mem', $out);
    }
}
