<?php
include 'miconn.php';
session_start();

try {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        // Busca usuário pelo email
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se encontrou e se a senha confere
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // guarda dados essenciais na sessão
            $_SESSION['usuario_id']    = $usuario['id'];
            $_SESSION['usuario_email'] = $usuario['email']; // nome alinhado com confirmar.php
            $_SESSION['usuario_nome']  = $usuario['nome'];
            $_SESSION['tipo']          = $usuario['tipo'];

            // redireciona conforme o tipo
            if ($usuario['tipo'] == 'aluno') {
                header("Location: aluno.php");
            } elseif ($usuario['tipo'] == 'professor') {
                header("Location: professor.php");
            } else {
                header("Location: admin.php");
            }
            exit;
        } else {
            // volta para index com erro
            header("Location: index.php?erro=1");
            exit;
        }
    }
} catch (Exception $e) {
    // em caso de falha inesperada
    header("Location: index.php?erro=1");
    exit;
}
?>
