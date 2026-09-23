<?php

$db = new PDO('sqlite:C:\laragon\www\miconte\miconte.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$resultado = $db->query("PRAGMA table_info(livros)");

foreach ($resultado as $coluna) {
    echo $coluna['name'] . PHP_EOL;
}