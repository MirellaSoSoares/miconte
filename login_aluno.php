<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Aluno | MiConte+</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container login-card-box">
        <h1>🎓 Login do Aluno</h1>
        <p>Entre com seu e-mail institucional e senha.</p>

        <?php if (isset($_GET['erro'])): ?>
            <p class="erro-msg">E-mail ou senha de aluno inválidos.</p>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <input type="hidden" name="tipo_login" value="aluno">

            <input type="email" name="email" placeholder="E-mail institucional" required>
            <input type="password" name="senha" placeholder="Senha" required>

            <button type="submit">Entrar como aluno</button>
        </form>

        <p class="voltar-link">
            <a href="index.php">← Voltar</a>
        </p>
    </div>
</body>
</html>