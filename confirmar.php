<?php
include 'miconn.php';
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_email'])) {
    echo "Usuário não autenticado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Método não permitido";
    exit;
}

$aluno_id    = (int) $_SESSION['usuario_id'];
$aluno_email = $_SESSION['usuario_email'];
$action      = $_POST['action'] ?? 'create';
$livro_id    = (int) ($_POST['livro_id'] ?? 0);
$reserva_id  = (int) ($_POST['reserva_id'] ?? 0);
$data        = trim((string) ($_POST['data'] ?? ''));
$hora        = trim((string) ($_POST['hora'] ?? ''));

if ($action === 'cancel') {
    if (!$reserva_id) {
        echo "Reserva não informada";
        exit;
    }

    try {
        $db->beginTransaction();

        $stmtReserva = $db->prepare("SELECT livro_id FROM reservas WHERE id = :reserva_id AND aluno_id = :aluno_id LIMIT 1 FOR UPDATE");
        $stmtReserva->execute([
            ':reserva_id' => $reserva_id,
            ':aluno_id' => $aluno_id,
        ]);
        $reserva = $stmtReserva->fetch(PDO::FETCH_ASSOC);

        if (!$reserva) {
            $db->rollBack();
            echo "Reserva não encontrada";
            exit;
        }

        $stmtLivro = $db->prepare("SELECT quantidade FROM livros WHERE id = :livro_id FOR UPDATE");
        $stmtLivro->execute([':livro_id' => $reserva['livro_id']]);
        $livro = $stmtLivro->fetch(PDO::FETCH_ASSOC);

        if (!$livro) {
            $db->rollBack();
            echo "Livro não encontrado";
            exit;
        }

        $stmtDelete = $db->prepare("DELETE FROM reservas WHERE id = :reserva_id AND aluno_id = :aluno_id");
        $stmtDelete->execute([
            ':reserva_id' => $reserva_id,
            ':aluno_id' => $aluno_id,
        ]);

        $stmtUpdate = $db->prepare("UPDATE livros SET quantidade = quantidade + 1 WHERE id = :livro_id");
        $stmtUpdate->execute([':livro_id' => $reserva['livro_id']]);

        if ($stmtDelete->rowCount() !== 1 || $stmtUpdate->rowCount() !== 1) {
            $db->rollBack();
            echo "Não foi possível cancelar a reserva";
            exit;
        }

        $db->commit();
        echo "OK";
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $logLine = date('c') . " | confirmar.php | cancel | " . $e->getMessage() . PHP_EOL;
        @file_put_contents(__DIR__ . '/db_errors.log', $logLine, FILE_APPEND);
        echo "Erro ao cancelar reserva";
        exit;
    }
}

if (!$livro_id || !$data || !$hora) {
    echo "Dados incompletos";
    exit;
}

try {
    $db->beginTransaction();

    $stmtLivro = $db->prepare("SELECT quantidade FROM livros WHERE id = :livro_id FOR UPDATE");
    $stmtLivro->execute([':livro_id' => $livro_id]);
    $livro = $stmtLivro->fetch(PDO::FETCH_ASSOC);

    if (!$livro) {
        $db->rollBack();
        echo "Livro não encontrado";
        exit;
    }

    if ((int) $livro['quantidade'] <= 0) {
        $db->rollBack();
        echo "Livro indisponível";
        exit;
    }

    $stmtDuplicado = $db->prepare("SELECT COUNT(*) AS total FROM reservas WHERE aluno_id = :aluno_id AND livro_id = :livro_id");
    $stmtDuplicado->execute([
        ':aluno_id' => $aluno_id,
        ':livro_id' => $livro_id,
    ]);
    $duplicado = $stmtDuplicado->fetch(PDO::FETCH_ASSOC);

    if ((int) $duplicado['total'] > 0) {
        $db->rollBack();
        echo "Você já reservou este livro";
        exit;
    }

    $stmtHorario = $db->prepare("SELECT COUNT(*) AS total FROM reservas WHERE aluno_id = :aluno_id AND livro_id = :livro_id AND data = :data AND hora = :hora");
    $stmtHorario->execute([
        ':aluno_id' => $aluno_id,
        ':livro_id' => $livro_id,
        ':data' => $data,
        ':hora' => $hora,
    ]);
    $horario = $stmtHorario->fetch(PDO::FETCH_ASSOC);

    if ((int) $horario['total'] > 0) {
        $db->rollBack();
        echo "Você já reservou este livro para essa data e horário";
        exit;
    }

    $stmtTotal = $db->prepare("SELECT COUNT(DISTINCT livro_id) AS total FROM reservas WHERE aluno_id = :aluno_id");
    $stmtTotal->execute([':aluno_id' => $aluno_id]);
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC);

    if ((int) $total['total'] >= 3) {
        $db->rollBack();
        echo "Limite de 3 livros por usuário atingido";
        exit;
    }

    $stmtInsert = $db->prepare("INSERT INTO reservas (aluno_id, aluno_email, livro_id, data, hora, criado_em) VALUES (:aluno_id, :aluno_email, :livro_id, :data, :hora, CURRENT_TIMESTAMP)");
    $stmtInsert->execute([
        ':aluno_id' => $aluno_id,
        ':aluno_email' => $aluno_email,
        ':livro_id' => $livro_id,
        ':data' => $data,
        ':hora' => $hora,
    ]);

    $stmtUpdate = $db->prepare("UPDATE livros SET quantidade = quantidade - 1 WHERE id = :livro_id AND quantidade > 0");
    $stmtUpdate->execute([':livro_id' => $livro_id]);

    if ($stmtUpdate->rowCount() !== 1) {
        $db->rollBack();
        echo "Não foi possível atualizar o estoque do livro";
        exit;
    }

    $db->commit();
    echo "OK";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $logLine = date('c') . " | confirmar.php | exception | " . $e->getMessage() . PHP_EOL;
    @file_put_contents(__DIR__ . '/db_errors.log', $logLine, FILE_APPEND);
    echo "Erro ao salvar reserva";
}
?>
