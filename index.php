<?php include 'header.php'; ?>

<div id="splash">
  <div class="book">
    <div class="left-page"></div>
    <div class="right-page"></div>
  </div>
</div>

<div class="login-container">

    <p>Seja bem-vindo! Faça login para descobrir novas leituras!</p>

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

<?php include 'footer.php'; ?>

<style>

    .login-container {
        width: min(850px, 90%);
        box-sizing: border-box;
    }

    .tipos-login {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
        margin-top: 25px;
    }

    .tipo-card {
        display: block;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        padding: 22px 15px;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        background-color: #fff;
        border: 2px solid transparent;
        transition: 0.2s;
        overflow: hidden;
    }

    .tipo-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.15);
    }

    .tipo-card .icone {
        font-size: 35px;
        margin-bottom: 8px;
    }

    .tipo-card h2 {
        margin: 5px 0 8px;
        font-size: 20px;
    }

    .tipo-card p {
        margin: 0;
        font-size: 13px;
        line-height: 1.4;
        color: #555;
    }

    .aluno-card {
        border-color: #722f37;
    }

    .aluno-card h2 {
        color: #722f37;
    }

    .professor-card {
        border-color: #6a0dad;
    }

    .professor-card h2 {
        color: #6a0dad;
    }

    .admin-card {
        border-color: #444;
    }

    .admin-card h2 {
        color: #444;
    }

    @media (max-width: 800px) {
        .tipos-login {
            grid-template-columns: 1fr;
        }

        .login-container {
            width: 90%;
        }
    }

</style>

<script>
    if (window.location.search.includes("erro=1")) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>