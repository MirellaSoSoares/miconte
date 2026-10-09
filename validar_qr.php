<?php
include 'miconn.php';
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo'])) {
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado']);
    exit;
}

$tipoUsuario = strtolower((string) $_SESSION['tipo']);
if (!in_array($tipoUsuario, ['professor', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Acesso restrito ao bibliotecário']);
    exit;
}

$payload = $_POST['qr'] ?? $_POST['payload'] ?? '';
if ($payload === '') {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Código QR não informado']);
    exit;
}

if (str_contains($payload, '|')) {
    $partes = explode('|', $payload);
    if (count($partes) !== 6 || $partes[0] !== 'miconte') {
        http_response_code(400);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Payload QR inválido']);
        exit;
    }

    $reservaId = (int) $partes[1];
    $livroId = (int) $partes[2];
    $alunoId = (int) $partes[3];
    $dataReserva = trim((string) $partes[4]);
    $horaReserva = trim((string) $partes[5]);
} else {
    $data = json_decode($payload, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Payload QR inválido']);
        exit;
    }

    if (($data['tipo'] ?? '') !== 'miconte_reserva') {
        http_response_code(400);
        echo json_encode(['status' => 'erro', 'mensagem' => 'QR de reserva inválido']);
        exit;
    }

    $reservaId = (int) ($data['reserva_id'] ?? 0);
    $livroId = (int) ($data['livro_id'] ?? 0);
    $alunoId = (int) ($data['aluno_id'] ?? 0);
    $dataReserva = trim((string) ($data['data'] ?? ''));
    $horaReserva = trim((string) ($data['hora'] ?? ''));
}

if ($reservaId <= 0 || $livroId <= 0 || $alunoId <= 0 || $dataReserva === '' || $horaReserva === '') {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Dados da reserva incompletos']);
    exit;
}

try {
    $stmt = $db->prepare(
        "SELECT r.id, r.aluno_id, r.livro_id, r.data, r.hora, r.criado_em, l.titulo, u.nome AS aluno_nome
         FROM reservas r
         LEFT JOIN livros l ON l.id = r.livro_id
         LEFT JOIN usuarios u ON u.id = r.aluno_id
         WHERE r.id = :reserva_id
           AND r.aluno_id = :aluno_id
           AND r.livro_id = :livro_id
           AND r.data = :data
           AND r.hora = :hora
         LIMIT 1"
    );

    $stmt->execute([
        ':reserva_id' => $reservaId,
        ':aluno_id' => $alunoId,
        ':livro_id' => $livroId,
        ':data' => $dataReserva,
        ':hora' => $horaReserva,
    ]);

    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        http_response_code(404);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Reserva não encontrada ou inválida']);
        exit;
    }

    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Reserva validada com sucesso',
        'reserva' => [
            'id' => (int) $reserva['id'],
            'aluno_id' => (int) $reserva['aluno_id'],
            'livro_id' => (int) $reserva['livro_id'],
            'data' => $reserva['data'],
            'hora' => $reserva['hora'],
            'titulo_livro' => $reserva['titulo'] ?? 'Livro',
            'nome_aluno' => $reserva['aluno_nome'] ?? 'Aluno'
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    @file_put_contents(__DIR__ . '/db_errors.log', date('c') . ' | validar_qr.php | ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao validar QR']);
}
