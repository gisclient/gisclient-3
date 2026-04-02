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

// user with manager permission (can create new users and groups)
define('SUPER_USER', 'admin');
