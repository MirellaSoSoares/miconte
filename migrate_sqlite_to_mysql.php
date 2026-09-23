<?php

// =====================================================
// CONFIGURAÇÃO
// =====================================================

$sqliteDsn = 'sqlite:C:\Users\Victor Hugo\Documents\miconte\miconte.sqlite';

$mysqlDsn = 'mysql:host=127.0.0.1;dbname=mysql;charset=utf8mb4';
$mysqlUser = 'root';
$mysqlPass = '';

try {

    // =====================================================
    // CONEXÃO COM SQLITE
    // =====================================================

    $sqlite = new PDO($sqliteDsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // =====================================================
    // CONEXÃO COM MYSQL
    // =====================================================

    $mysql = new PDO($mysqlDsn, $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // =====================================================
    // CRIA O BANCO MICONTE
    // =====================================================

    $mysql->exec("
        CREATE DATABASE IF NOT EXISTS miconte
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    $mysql->exec("USE miconte");

    // =====================================================
    // REMOVE TABELAS ANTIGAS
    // =====================================================

    $mysql->exec("SET FOREIGN_KEY_CHECKS = 0");

    $mysql->exec("DROP TABLE IF EXISTS reservas");
    $mysql->exec("DROP TABLE IF EXISTS livros");
    $mysql->exec("DROP TABLE IF EXISTS usuarios");

    $mysql->exec("SET FOREIGN_KEY_CHECKS = 1");

    // =====================================================
    // TABELA USUARIOS
    // =====================================================

    $mysql->exec("
        CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            senha TEXT NOT NULL,
            tipo VARCHAR(50) DEFAULT 'aluno'
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    // =====================================================
    // TABELA LIVROS
    // IMPORTANTE:
    // O SQLite NÃO possui a coluna generos.
    // =====================================================

    $mysql->exec("
        CREATE TABLE livros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            autor VARCHAR(255) NOT NULL,
            capa VARCHAR(255) NULL,
            sinopse TEXT NULL,
            quantidade INT DEFAULT 0
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    // =====================================================
    // TABELA RESERVAS
    // =====================================================

    $mysql->exec("
        CREATE TABLE reservas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            aluno_email VARCHAR(255) NOT NULL,
            livro_id INT NOT NULL,
            data DATE NOT NULL,
            hora TIME NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_reservas_aluno
                FOREIGN KEY (aluno_id)
                REFERENCES usuarios(id),

            CONSTRAINT fk_reservas_livro
                FOREIGN KEY (livro_id)
                REFERENCES livros(id)
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    // =====================================================
    // MIGRAÇÃO DOS USUÁRIOS
    // =====================================================

    $usuarios = $sqlite->query("
        SELECT id, nome, email, senha, tipo
        FROM usuarios
    ")->fetchAll();

    $stmtUsuario = $mysql->prepare("
        INSERT INTO usuarios
        (id, nome, email, senha, tipo)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($usuarios as $usuario) {

        $stmtUsuario->execute([
            $usuario['id'],
            $usuario['nome'],
            $usuario['email'],
            $usuario['senha'],
            $usuario['tipo']
        ]);
    }

    // =====================================================
    // MIGRAÇÃO DOS LIVROS
    // =====================================================

    $livros = $sqlite->query("
        SELECT id, titulo, autor, capa, sinopse, quantidade
        FROM livros
    ")->fetchAll();

    $stmtLivro = $mysql->prepare("
        INSERT INTO livros
        (id, titulo, autor, capa, sinopse, quantidade)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($livros as $livro) {

        $stmtLivro->execute([
            $livro['id'],
            $livro['titulo'],
            $livro['autor'],
            $livro['capa'],
            $livro['sinopse'],
            $livro['quantidade']
        ]);
    }

    // =====================================================
    // MIGRAÇÃO DAS RESERVAS
    // =====================================================

    $reservas = $sqlite->query("
        SELECT id, aluno_id, aluno_email, livro_id, data, hora, criado_em
        FROM reservas
    ")->fetchAll();

    $stmtReserva = $mysql->prepare("
        INSERT INTO reservas
        (id, aluno_id, aluno_email, livro_id, data, hora, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($reservas as $reserva) {

        $stmtReserva->execute([
            $reserva['id'],
            $reserva['aluno_id'],
            $reserva['aluno_email'],
            $reserva['livro_id'],
            $reserva['data'],
            $reserva['hora'],
            $reserva['criado_em']
        ]);
    }

    // =====================================================
    // AJUSTA AUTO_INCREMENT
    // =====================================================

    $mysql->exec("
        ALTER TABLE usuarios
        AUTO_INCREMENT = 1
    ");

    $mysql->exec("
        ALTER TABLE livros
        AUTO_INCREMENT = 1
    ");

    $mysql->exec("
        ALTER TABLE reservas
        AUTO_INCREMENT = 1
    ");

    // =====================================================
    // RESULTADO
    // =====================================================

    echo "========================================\n";
    echo " MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "========================================\n";

    echo "Usuários migrados: " . count($usuarios) . "\n";
    echo "Livros migrados: " . count($livros) . "\n";
    echo "Reservas migradas: " . count($reservas) . "\n";

    echo "\nMIGRATION_OK\n";

} catch (Throwable $e) {

    echo "========================================\n";
    echo " ERRO NA MIGRAÇÃO\n";
    echo "========================================\n";

    echo "MIGRATION_ERROR: " . $e->getMessage() . "\n";

    exit(1);
}