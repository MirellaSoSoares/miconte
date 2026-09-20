<?php
include 'miconn.php';
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_email'])) {
    echo "Usuário não autenticado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aluno_id    = $_SESSION['usuario_id'];
    $aluno_email = $_SESSION['usuario_email'];
    $livro_id    = $_POST['livro_id'] ?? null;
    $data        = $_POST['data'] ?? null;
    $hora        = $_POST['hora'] ?? null;

    if (!$livro_id || !$data || !$hora) {
        echo "Dados incompletos";
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO reservas 
            (aluno_id, aluno_email, livro_id, data, hora, criado_em) 
            VALUES (:aluno_id, :aluno_email, :livro_id, :data, :hora, CURRENT_TIMESTAMP)");

        $stmt->bindParam(':aluno_id', $aluno_id, PDO::PARAM_INT);
        $stmt->bindParam(':aluno_email', $aluno_email, PDO::PARAM_STR);
        $stmt->bindParam(':livro_id', $livro_id, PDO::PARAM_INT);
        $stmt->bindParam(':data', $data, PDO::PARAM_STR);
        $stmt->bindParam(':hora', $hora, PDO::PARAM_STR);

        $maxAttempts = 6;
        $attempt = 0;
        while (true) {
            try {
                if ($stmt->execute()) {
                    echo "OK";
                } else {
                    echo "Erro ao inserir";
                }
                break;
            } catch (PDOException $e) {
                $attempt++;
                $logLine = date('c') . " | confirmar.php | attempt={$attempt} | " . $e->getMessage() . PHP_EOL;
                @file_put_contents(__DIR__ . '/db_errors.log', $logLine, FILE_APPEND);
                if ($attempt >= $maxAttempts || stripos($e->getMessage(), 'database is locked') === false) {
                    echo "Erro: " . $e->getMessage();
                    break;
                }
                // backoff crescente (ms)
                usleep(200000 * $attempt);
            }
        }

        $stmt = null; // libera statement
        $db   = null; // libera conexão

    } catch (Exception $e) {
        $logLine = date('c') . " | confirmar.php | exception | " . $e->getMessage() . PHP_EOL;
        @file_put_contents(__DIR__ . '/db_errors.log', $logLine, FILE_APPEND);
        echo "Erro: " . $e->getMessage();
    }
}
?>
