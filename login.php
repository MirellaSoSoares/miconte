<?php

include 'miconn.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$tipoLogin = $_POST['tipo_login'] ?? '';

$tiposPermitidos = ['aluno', 'professor', 'admin'];

if (
    empty($email) ||
    empty($senha) ||
    !in_array($tipoLogin, $tiposPermitidos, true)
) {
    header("Location: index.php");
    exit;
}

try {

    $stmt = $db->prepare("
        SELECT id, nome, email, senha, tipo
        FROM usuarios
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$usuario ||
        !password_verify($senha, $usuario['senha']) ||
        $usuario['tipo'] !== $tipoLogin
    ) {
        header("Location: login_" . $tipoLogin . ".php?erro=1");
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['tipo'] = $usuario['tipo'];

    if ($usuario['tipo'] === 'aluno') {

        header("Location: aluno.php");

    } elseif ($usuario['tipo'] === 'professor') {

        header("Location: professor.php");

    } elseif ($usuario['tipo'] === 'admin') {

        header("Location: admin.php");
    }

    exit;

} catch (Exception $e) {

    header("Location: login_" . $tipoLogin . ".php?erro=1");
    exit;
}