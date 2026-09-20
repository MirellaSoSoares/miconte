<?php
include 'miconn.php';
session_start();

// Verifica login
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'aluno') {
    header("Location: index.php");
    exit;
}

// Pega o id enviado pelo botão
$livro = null;
if (isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $stmt = $db->prepare("SELECT id, titulo, autor, capa, sinopse FROM livros WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $livro = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo $livro ? $livro['titulo'] : 'Sinopse'; ?></title>
    <link rel="stylesheet" href="css/aluno.css">
    <style>
        .sinopse-container {
            background-color: #f5f5dc; /* fundo bege */
            padding: 30px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .sinopse-card {
            background: #fff;
            border: 1px solid #ccc;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.2);
            max-width: 700px;
            width: 100%;
            position: relative;
        }
        .sinopse-card img {
            display: block;
            margin: 0 auto 20px auto;
            max-height: 200px;
        }
        .sinopse-card h2 {
            text-align: center;
            color: #800000;
            margin-bottom: 5px;
        }
        .sinopse-card .autor {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .sinopse-card p {
            font-size: 16px;
            line-height: 1.6;
            text-align: justify;
        }
        .sinopse-card button {
            display: block;
            margin: 25px auto 0 auto;
            padding: 10px 20px;
            background: #800000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .sinopse-card button:hover {
            background: #a00000;
        }
        .voltar {
            position: absolute;
            top: 10px;
            left: 10px;
            text-decoration: none;
            font-size: 20px;
            color: #800000;
            font-weight: bold;
        }
        .voltar:hover {
            color: #a00000;
        }
    </style>
</head>
<body>
    <header>
        Sinopse
    </header>

    <div class="sinopse-container">
        <?php if ($livro): ?>
            <div class="sinopse-card">
                <a href="aluno.php" class="voltar">←</a>
                <img src="imagens/<?php echo $livro['capa']; ?>" alt="Capa do livro">
                <h2><?php echo strtoupper($livro['titulo']); ?></h2>
                <div class="autor"><?php echo $livro['autor']; ?></div>
                <p><?php echo nl2br($livro['sinopse']); ?></p>
                <form method="POST" action="disponibilidade.php">
                    <input type="hidden" name="id" value="<?php echo $livro['id']; ?>">
                    <button type="submit">Verificar disponibilidade</button>
                </form>
            </div>
        <?php else: ?>
            <p>Livro não encontrado.</p>
        <?php endif; ?>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Biblioteca Escolar - Todos os direitos reservados
    </footer>
</body>
</html>
