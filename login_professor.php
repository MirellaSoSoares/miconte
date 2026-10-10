<?php
session_start();

$erro = isset($_GET['erro']) && $_GET['erro'] == 1;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor | MiConte+</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container login-card-box">
        <h1>👨‍🏫 Professor</h1>
        <p>Acesse sua conta do MiConte+</p>

        <?php if ($erro): ?>
            <p class="erro-msg">E-mail ou senha inválidos!</p>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <input type="hidden" name="tipo_login" value="professor">

            <input type="email" name="email" placeholder="E-mail institucional" required>
            <input type="password" name="senha" placeholder="Senha" required>

            <button type="submit">Entrar</button>
        </form>

        <p class="voltar-link">
            <a href="index.php">← Voltar</a>
        </p>
    </div>
</body>
</html>