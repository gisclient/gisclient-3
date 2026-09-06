<?php
//Impostazioni database Postgresql
define('DB_NAME', getenv('DB_DBNAME'));
define('DB_SCHEMA', 'gisclient_34');
define('USER_SCHEMA', 'gisclient_34');
define('CHAR_SET', 'UTF-8');
define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_USER', getenv('DB_USER')); //Superutente
define('DB_PWD', getenv('DB_PASSWORD'));

//Utente scritto sul file .map (fallback al DB_USER se non configurato)
define('MAP_USER', getenv('MAP_USER') ?: DB_USER);
define('MAP_PWD', getenv('MAP_PASSWORD') ?: DB_PWD);

// Endpoint di sola lettura usato da MapServer per le richieste OGC read-only.
// Ricade sul primario se non valorizzato, quindi senza questa variabile il
// comportamento e' identico a prima.
//
// Viene instradato solo l'HOST. Il database resta sempre quello indicato dal
// catalogo: un catalog_path che ne nomina uno esplicitamente lo fa di
// proposito, e sovrascriverlo romperebbe i layer che vivono su un altro
// database. Una replica fisica contiene comunque tutti i database del cluster.
define('MAP_DB_HOST', getenv('MAP_DB_HOST') ?: DB_HOST);
// Parametri libpq aggiuntivi per le connessioni instradate, es.
// "target_session_attrs=prefer-standby connect_timeout=2". Con MAP_DB_HOST
// che elenca piu' host separati da virgola, libpq sceglie da solo lo standby
// e ricade sul primario se nessuno risponde. connect_timeout va sempre
// impostato: senza, il fallback attende il timeout TCP del sistema.
define('MAP_DB_CONN_PARAMS', getenv('MAP_DB_CONN_PARAMS') ?: null);

// Tetto per la singola query delle richieste OGC di sola lettura, in
// millisecondi. 0 lo disattiva.
//
// Serve perche' nessuno, a valle, puo' fermare una query gia' partita: quando
// il browser rinuncia, o quando scade wms_connectiontimeout, la GetMap smette
// di essere attesa ma continua a girare nel database. Senza questo tetto il
// lavoro abbandonato si accumula mentre l'utente ne genera dell'altro.
//
// Vale solo per le connessioni instradate da MapDatabaseRouter, cioe' le sole
// letture OGC: editing, transazioni, import e comandi console non sono
// toccati. Per la stessa ragione non si usa ALTER ROLE, che colpirebbe anche
// quelli — e che comunque non avrebbe effetto sui layer RLS, perche' SET ROLE
// non riapplica le impostazioni del ruolo impersonato.
//
// Deve restare SOTTO il timeout di chi aspetta (wms_connectiontimeout in
// gcWMSMerge.php, 10s di default): un tetto piu' alto di chi attende genera
// per definizione lavoro orfano.
//
// 8000 e non 10000: il timeout HTTP conta dall'inizio della richiesta, mentre
// la query comincia dopo il boot di ows.php, il parsing del mapfile e lo
// scaricamento dell'SLD. A parita' di valore chi aspetta mollerebbe sempre per
// primo, e la query resterebbe a girare per nessuno; i 2s di scarto coprono
// quel preambolo.
//
// Il residuo: il tetto e' per singola query, non per richiesta. Una GetMap che
// disegna N layer puo' quindi spendere fino a N x MAP_DB_STATEMENT_TIMEOUT.
// Per limitare il totale bisogna ridurre i layer, non abbassare il tetto.
define('MAP_DB_STATEMENT_TIMEOUT', (int) (getenv('MAP_DB_STATEMENT_TIMEOUT') !== false
    ? getenv('MAP_DB_STATEMENT_TIMEOUT')
    : 8000));
define('MAP_DB_ROUTING', getenv('MAP_DB_HOST') !== false);

// user with manager permission (can create new users and groups)
define('SUPER_USER', 'admin');
