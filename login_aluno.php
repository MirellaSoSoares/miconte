<?php
include 'header.php';
?>

<div class="login-container">

    <h1>🎓 Login do Aluno</h1>

    <p>Entre com seu e-mail institucional e senha.</p>

    <?php if (isset($_GET['erro'])): ?>
        <p class="erro-msg">E-mail ou senha de aluno inválidos.</p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input type="hidden" name="tipo_login" value="aluno">

        <input
            type="email"
            name="email"
            placeholder="E-mail institucional"
            required
        >

        <input
            type="password"
            name="senha"
            placeholder="Senha"
            required
        >

        <button type="submit">Entrar como aluno</button>

    </form>

    <p>
        <a href="index.php">← Voltar</a>
    </p>

</div>

<?php include 'footer.php'; ?>