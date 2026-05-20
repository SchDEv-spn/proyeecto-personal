<?php
// En producción (Hostinger): crea ~/domains/[dominio]/tienda_config.php
// (un nivel arriba de public_html). El autodeploy de git nunca lo toca.
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
