<?php
try {
    $db = new PDO(
        'mysql:host=127.0.0.1;dbname=miconte;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]
    );
} catch (Exception $e) {
    die('Erro ao conectar: ' . $e->getMessage());
}
?>
