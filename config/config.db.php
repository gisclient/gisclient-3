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
// Entrambe ricadono sul primario se non valorizzate, quindi senza queste
// variabili il comportamento e' identico a prima.
// MAP_DB_NAME serve anche a fissare il database del deployment quando
// catalog_path porta con se' il nome di un altro database (es. un catalogo
// copiato da produzione in un ambiente di test).
define('MAP_DB_HOST', getenv('MAP_DB_HOST') ?: DB_HOST);
define('MAP_DB_NAME', getenv('MAP_DB_NAME') ?: DB_NAME);
define('MAP_DB_ROUTING', getenv('MAP_DB_HOST') !== false || getenv('MAP_DB_NAME') !== false);

// user with manager permission (can create new users and groups)
define('SUPER_USER', 'admin');
