<?php
// Lee credenciales desde variables de entorno (Hostinger PHP Config)
// o desde valores por defecto para desarrollo local.
return [
    'db_host'     => getenv('DB_HOST')     ?: 'localhost',
    'db_name'     => getenv('DB_NAME')     ?: 'tienda_db',
    'db_user'     => getenv('DB_USER')     ?: 'root',
    'db_password' => getenv('DB_PASSWORD') ?: '',
    'base_url'    => getenv('APP_BASE_URL') ?: '/tienda_mvc',
];
