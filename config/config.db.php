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
define('MAP_DB_ROUTING', getenv('MAP_DB_HOST') !== false);

// user with manager permission (can create new users and groups)
define('SUPER_USER', 'admin');
