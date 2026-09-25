<?php
// Conexão com o banco, aberta só quando alguém pede (páginas estáticas,
// como o gerador de dieta, não dependem do MySQL estar no ar).

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $cfg = require BASE_PATH . '/config/database.php';
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
