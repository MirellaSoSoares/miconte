<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiConte+ | Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container landing-login">
        <img src="imagens/logo_miconte.png" alt="MiConte+" class="brand-logo">
        <p class="lead">Seja bem-vindo! Faça login para descobrir novas leituras.</p>

        <div class="tipos-login">
            <a href="login_aluno.php" class="tipo-card aluno-card">
                <div class="icone">🎓</div>
                <h2>Aluno</h2>
                <p>Acesse sua conta para consultar livros e fazer reservas.</p>
            </a>

            <a href="login_professor.php" class="tipo-card professor-card">
                <div class="icone">👨‍🏫</div>
                <h2>Professor</h2>
                <p>Acesse sua área para consultar livros e reservas.</p>
            </a>

            <a href="login_admin.php" class="tipo-card admin-card">
                <div class="icone">⚙️</div>
                <h2>Administrador</h2>
                <p>Acesse o painel de gerenciamento da biblioteca.</p>
            </a>
        </div>
    </div>

    <script>
        if (window.location.search.includes("erro=1")) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>