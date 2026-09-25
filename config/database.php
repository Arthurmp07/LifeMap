<?php
// Credenciais do MySQL/MariaDB. Os valores padrão são os do XAMPP; para
// outro ambiente, defina as variáveis de ambiente correspondentes.
return [
    'host'    => getenv('LIFEMAP_DB_HOST') ?: '127.0.0.1',
    'dbname'  => getenv('LIFEMAP_DB_NAME') ?: 'academia',
    'user'    => getenv('LIFEMAP_DB_USER') ?: 'root',
    'pass'    => getenv('LIFEMAP_DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];
