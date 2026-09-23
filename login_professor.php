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

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background-color: #722f37;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: #f5e8d3;
            padding: 40px;
            border-radius: 10px;
            width: 350px;
            text-align: center;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }

        h1 {
            color: #722f37;
            margin-bottom: 10px;
        }

        .subtitulo {
            color: #333;
            margin-bottom: 25px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background-color: #722f37;
            color: #fff;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }

        button:hover {
            background-color: #7a2f2f;
        }

        .erro {
            color: #cc0000;
            margin: 10px 0;
            font-size: 14px;
        }

        .voltar {
            display: block;
            margin-top: 20px;
            color: #6a0dad;
            font-weight: bold;
            text-decoration: none;
        }

        .voltar:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="login-container">

    <h1>👨‍🏫 Professor</h1>

    <p class="subtitulo">
        Acesse sua conta do MiConte+
    </p>

    <?php if ($erro): ?>
        <p class="erro">
            E-mail ou senha inválidos!
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input type="hidden" name="tipo_login" value="professor">

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

        <button type="submit">
            Entrar
        </button>

    </form>

    <a href="index.php" class="voltar">
        ← Voltar
    </a>

</div>

</body>
</html>