<?php
include 'header.php';
?>

<div class="login-container">

    <h1>🛠️ Login do Administrador</h1>

    <p>Acesso restrito à administração do MiConte+.</p>

    <?php if (isset($_GET['erro'])): ?>
        <p class="erro-msg">Credenciais de administrador inválidas.</p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input type="hidden" name="tipo_login" value="admin">

        <input
            type="email"
            name="email"
            placeholder="E-mail"
            required
        >

        <input
            type="password"
            name="senha"
            placeholder="Senha"
            required
        >

        <button type="submit">Entrar como administrador</button>

    </form>

    <p>
        <a href="index.php">← Voltar</a>
    </p>

</div>

<?php include 'footer.php'; ?>