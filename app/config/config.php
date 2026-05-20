<?php
// En producción (Hostinger): crea /home/[usuario]/tienda_config.php
// con las credenciales reales. Ese archivo vive fuera de public_html
// y el autodeploy de git nunca lo toca.
$_external = dirname(dirname(dirname(__DIR__))) . '/tienda_config.php';
if (file_exists($_external)) {
    return require $_external;
}

// Desarrollo local (XAMPP)
return [
    'db_host'     => 'localhost',
    'db_name'     => 'tienda_db',
    'db_user'     => 'root',
    'db_password' => '',
    'base_url'    => '/tienda_mvc',
];
