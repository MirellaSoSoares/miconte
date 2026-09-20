<?php include 'header.php'; ?>

<div id="splash">
  <div class="book">
    <div class="left-page"></div>
    <div class="right-page"></div>
  </div>
</div>

<div class="login-container">
    <p>Seja bem-vindo! Faça login para descobrir novas leituras!</p>
    
    <form method="POST" action="login.php">
        <input type="email" name="email" placeholder="E-mail institucional" required>
        <!-- Mensagem de erro aparece aqui -->
        <?php if (isset($_GET['erro']) && $_GET['erro'] == 1): ?>
            <p class="erro-msg">Email ou senha inválidos!</p>
        <?php endif; ?>

        <input type="password" name="senha" placeholder="Senha" required>
        <button type="submit">Entrar</button>
    </form>

    <p><a href="professor.php" class="professor-link">Sou professor</a></p>
</div>

<?php include 'footer.php'; ?>

<script>
    if (window.location.search.includes("erro=1")) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>
