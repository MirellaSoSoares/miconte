<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Administrador | MiConte+</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container login-card-box">
        <h1>🛠️ Login do Administrador</h1>
        <p>Acesso restrito à administração do MiConte+.</p>

        <?php if (isset($_GET['erro'])): ?>
            <p class="erro-msg">Credenciais de administrador inválidas.</p>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <input type="hidden" name="tipo_login" value="admin">

            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>

            <button type="submit">Entrar como administrador</button>
        </form>

        <p class="voltar-link">
            <a href="index.php">← Voltar</a>
        </p>
    </div>
</body>
</html>