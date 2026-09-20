<?php
$sqliteDsn = 'sqlite:C:\laragon\www\miconte\miconte.sqlite';
$mysqlDsn = 'mysql:host=127.0.0.1;dbname=mysql;charset=utf8mb4';
$mysqlUser = 'root';
$mysqlPass = '';

try {
    $sqlite = new PDO($sqliteDsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $mysql = new PDO($mysqlDsn, $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $mysql->exec("CREATE DATABASE IF NOT EXISTS miconte CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysql->exec("USE miconte");

    $mysql->exec("DROP TABLE IF EXISTS reservas");
    $mysql->exec("DROP TABLE IF EXISTS livros");
    $mysql->exec("DROP TABLE IF EXISTS usuarios");

    $mysql->exec("CREATE TABLE usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        senha TEXT NOT NULL,
        tipo VARCHAR(50) DEFAULT 'aluno'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $mysql->exec("CREATE TABLE livros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        autor VARCHAR(255) NOT NULL,
        capa VARCHAR(255) NULL,
        sinopse TEXT NULL,
        quantidade INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $mysql->exec("CREATE TABLE reservas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        aluno_id INT NOT NULL,
        aluno_email VARCHAR(255) NOT NULL,
        livro_id INT NOT NULL,
        data DATE NOT NULL,
        hora TIME NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_reservas_aluno FOREIGN KEY (aluno_id) REFERENCES usuarios(id),
        CONSTRAINT fk_reservas_livro FOREIGN KEY (livro_id) REFERENCES livros(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $tables = [
        'usuarios' => [
            'select' => 'SELECT id, nome, email, senha, tipo FROM usuarios',
            'insert' => 'INSERT INTO usuarios (id, nome, email, senha, tipo) VALUES (?, ?, ?, ?, ?)',
            'columns' => ['id', 'nome', 'email', 'senha', 'tipo'],
        ],
        'livros' => [
            'select' => 'SELECT id, titulo, autor, capa, sinopse, quantidade FROM livros',
            'insert' => 'INSERT INTO livros (id, titulo, autor, capa, sinopse, quantidade) VALUES (?, ?, ?, ?, ?, ?)',
            'columns' => ['id', 'titulo', 'autor', 'capa', 'sinopse', 'quantidade'],
        ],
        'reservas' => [
            'select' => 'SELECT id, aluno_id, aluno_email, livro_id, data, hora, criado_em FROM reservas',
            'insert' => 'INSERT INTO reservas (id, aluno_id, aluno_email, livro_id, data, hora, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?)',
            'columns' => ['id', 'aluno_id', 'aluno_email', 'livro_id', 'data', 'hora', 'criado_em'],
        ],
    ];

    foreach ($tables as $table => $config) {
        $rows = $sqlite->query($config['select'])->fetchAll();
        foreach ($rows as $row) {
            $values = [];
            foreach ($config['columns'] as $column) {
                $values[] = $row[$column] ?? null;
            }
            $stmt = $mysql->prepare($config['insert']);
            $stmt->execute($values);
        }
    }

    echo "MIGRATION_OK\n";
} catch (Throwable $e) {
    echo "MIGRATION_ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
