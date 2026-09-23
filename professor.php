<?php
session_start();
include 'miconn.php';

/* =========================
   PROTEÇÃO DA PÁGINA
========================= */

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'professor') {
    header("Location: index.php");
    exit;
}

/* =========================
   DADOS DO PROFESSOR
========================= */

$nomeProfessor = $_SESSION['usuario_nome'] ?? 'Professor';

/* =========================
   QUANTIDADE DE LIVROS
========================= */

$stmt = $db->query("SELECT COUNT(*) FROM livros");
$totalLivros = $stmt->fetchColumn();

/* =========================
   LIVROS DISPONÍVEIS
========================= */

$stmt = $db->query("SELECT COALESCE(SUM(quantidade), 0) FROM livros");
$livrosDisponiveis = $stmt->fetchColumn();

/* =========================
   TOTAL DE RESERVAS
========================= */

$stmt = $db->query("SELECT COUNT(*) FROM reservas");
$totalReservas = $stmt->fetchColumn();

/* =========================
   TOTAL DE ALUNOS
========================= */

$stmt = $db->query("
    SELECT COUNT(*)
    FROM usuarios
    WHERE tipo = 'aluno'
");
$totalAlunos = $stmt->fetchColumn();

/* =========================
   LISTA DE RESERVAS
========================= */

$sql = "
    SELECT
        r.id,
        r.aluno_email,
        u.nome,
        l.titulo,
        r.data,
        r.hora,
        r.criado_em
    FROM reservas r
    JOIN usuarios u ON r.aluno_id = u.id
    JOIN livros l ON r.livro_id = l.id
    ORDER BY r.data ASC, r.hora ASC
";

$stmt = $db->query($sql);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LISTA DE LIVROS
========================= */

$stmt = $db->query("
    SELECT id, titulo, autor, quantidade
    FROM livros
    ORDER BY titulo ASC
");

$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LISTA DE ALUNOS
========================= */

$stmt = $db->query("
    SELECT id, nome, email
    FROM usuarios
    WHERE tipo = 'aluno'
    ORDER BY nome ASC
");

$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MiConte+ | Professor</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5e8d3;
            min-height: 100vh;
            color: #333;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;

            background: #722f37;
            color: white;

            padding: 25px 15px;

            box-shadow: 3px 0 15px rgba(0,0,0,0.15);
        }

        .logo-area {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-area img {
            width: 120px;
            max-width: 100%;
            margin-bottom: 10px;
        }

        .logo-area h2 {
            font-size: 20px;
        }

        .logo-area p {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu button,
        .menu a {
            border: none;
            background: transparent;
            color: white;

            text-decoration: none;

            padding: 14px 15px;

            border-radius: 8px;

            text-align: left;

            cursor: pointer;

            font-size: 15px;
        }

        .menu button:hover,
        .menu a:hover,
        .menu button.active {
            background: rgba(255,255,255,0.15);
        }

        .logout {
            position: absolute;
            bottom: 25px;
            left: 15px;
            right: 15px;
        }

        .logout a {
            display: block;
            background: rgba(0,0,0,0.15);
        }

        /* =========================
           CONTEÚDO
        ========================= */

        .content {
            margin-left: 250px;
            padding: 35px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 30px;
        }

        .top h1 {
            color: #722f37;
            font-size: 30px;
        }

        .top p {
            margin-top: 5px;
            color: #666;
        }

        .professor-name {
            background: white;
            padding: 12px 18px;
            border-radius: 10px;

            box-shadow: 0 3px 12px rgba(0,0,0,0.08);

            color: #722f37;
            font-weight: bold;
        }

        /* =========================
           CARDS
        ========================= */

        .cards {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 20px;

            margin-bottom: 35px;
        }

        .card {
            background: white;

            padding: 25px;

            border-radius: 15px;

            box-shadow: 0 4px 15px rgba(0,0,0,0.08);

            border-left: 5px solid #722f37;
        }

        .card .number {
            font-size: 30px;
            font-weight: bold;
            color: #722f37;
        }

        .card .label {
            margin-top: 8px;
            color: #777;
            font-size: 14px;
        }

        /* =========================
           SEÇÕES
        ========================= */

        .section {
            display: none;

            background: white;

            padding: 25px;

            border-radius: 15px;

            box-shadow: 0 4px 15px rgba(0,0,0,0.08);

            margin-bottom: 25px;
        }

        .section.active {
            display: block;
        }

        .section h2 {
            color: #722f37;
            margin-bottom: 20px;
        }

        /* =========================
           TABELAS
        ========================= */

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #722f37;
            color: white;

            padding: 13px;

            text-align: left;
        }

        td {
            padding: 13px;

            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #faf6ef;
        }

        /* =========================
           BUSCA
        ========================= */

        .search {
            width: 100%;

            padding: 13px 15px;

            border: 1px solid #ddd;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 15px;

            outline: none;
        }

        .search:focus {
            border-color: #722f37;
        }

        /* =========================
           BADGES
        ========================= */

        .badge {
            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            background: #f5e8d3;

            color: #722f37;

            font-size: 12px;

            font-weight: bold;
        }

        /* =========================
           RESPONSIVO
        ========================= */

        @media (max-width: 1000px) {

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;
            }

            .logout {
                position: static;

                margin-top: 20px;
            }

            .content {
                margin-left: 0;

                padding: 20px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .top {
                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

        }

    </style>

</head>

<body>

<!-- =========================
     MENU LATERAL
========================= -->

<aside class="sidebar">

    <div class="logo-area">

        <img src="imagens/logo_miconte.png" alt="MiConte+">

        <h2>MiConte+</h2>

        <p>Painel do Professor</p>

    </div>

    <nav class="menu">

        <button class="active" onclick="mostrarSecao('dashboard', this)">
            📊 Dashboard
        </button>

        <button onclick="mostrarSecao('reservas', this)">
            📋 Reservas
        </button>

        <button onclick="mostrarSecao('livros', this)">
            📚 Livros
        </button>

        <button onclick="mostrarSecao('alunos', this)">
            👨‍🎓 Alunos
        </button>

    </nav>

    <div class="logout">

        <a href="logout.php">
            🚪 Sair
        </a>

    </div>

</aside>


<!-- =========================
     CONTEÚDO
========================= -->

<main class="content">

    <div class="top">

        <div>

            <h1>Painel do Professor</h1>

            <p>
                Gerencie os livros e acompanhe as atividades dos alunos.
            </p>

        </div>

        <div class="professor-name">

            👨‍🏫
            <?= htmlspecialchars($nomeProfessor) ?>

        </div>

    </div>


    <!-- =========================
         CARDS
    ========================= -->

    <div class="cards">

        <div class="card">

            <div class="number">
                <?= $totalLivros ?>
            </div>

            <div class="label">
                Livros cadastrados
            </div>

        </div>


        <div class="card">

            <div class="number">
                <?= $livrosDisponiveis ?>
            </div>

            <div class="label">
                Exemplares disponíveis
            </div>

        </div>


        <div class="card">

            <div class="number">
                <?= $totalReservas ?>
            </div>

            <div class="label">
                Reservas realizadas
            </div>

        </div>


        <div class="card">

            <div class="number">
                <?= $totalAlunos ?>
            </div>

            <div class="label">
                Alunos cadastrados
            </div>

        </div>

    </div>


    <!-- =========================
         DASHBOARD
    ========================= -->

    <section id="dashboard" class="section active">

        <h2>📊 Visão geral</h2>

        <p>
            Bem-vindo ao painel administrativo do MiConte+.
        </p>

        <br>

        <p>
            Utilize o menu lateral para consultar as reservas,
            visualizar os livros disponíveis e acompanhar os alunos.
        </p>

    </section>


    <!-- =========================
         RESERVAS
    ========================= -->

    <section id="reservas" class="section">

        <h2>📋 Reservas dos alunos</h2>

        <input
            type="text"
            class="search"
            placeholder="Pesquisar aluno ou livro..."
            onkeyup="buscarTabela(this, 'tabelaReservas')"
        >

        <div class="table-container">

            <table id="tabelaReservas">

                <thead>

                    <tr>

                        <th>Aluno</th>

                        <th>Email</th>

                        <th>Livro</th>

                        <th>Data</th>

                        <th>Hora</th>

                        <th>Registrado</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (count($reservas) === 0): ?>

                        <tr>

                            <td colspan="6">
                                Nenhuma reserva encontrada.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($reservas as $r): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($r['nome']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r['aluno_email']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r['titulo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r['data']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r['hora']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r['criado_em']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- =========================
         LIVROS
    ========================= -->

    <section id="livros" class="section">

        <h2>📚 Livros cadastrados</h2>

        <input
            type="text"
            class="search"
            placeholder="Pesquisar livro ou autor..."
            onkeyup="buscarTabela(this, 'tabelaLivros')"
        >

        <div class="table-container">

            <table id="tabelaLivros">

                <thead>

                    <tr>

                        <th>Livro</th>

                        <th>Autor</th>

                        <th>Quantidade</th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($livros as $livro): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($livro['titulo']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($livro['autor']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($livro['quantidade']) ?>
                            </td>

                            <td>

                                <?php if ($livro['quantidade'] > 0): ?>

                                    <span class="badge">
                                        Disponível
                                    </span>

                                <?php else: ?>

                                    <span class="badge">
                                        Indisponível
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- =========================
         ALUNOS
    ========================= -->

    <section id="alunos" class="section">

        <h2>👨‍🎓 Alunos cadastrados</h2>

        <input
            type="text"
            class="search"
            placeholder="Pesquisar aluno..."
            onkeyup="buscarTabela(this, 'tabelaAlunos')"
        >

        <div class="table-container">

            <table id="tabelaAlunos">

                <thead>

                    <tr>

                        <th>Nome</th>

                        <th>Email</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($alunos as $aluno): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($aluno['nome']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($aluno['email']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>


<script>

/* =========================
   TROCAR SEÇÃO
========================= */

function mostrarSecao(id, botao) {

    const secoes = document.querySelectorAll('.section');

    secoes.forEach(function(secao) {

        secao.classList.remove('active');

    });


    const selecionada = document.getElementById(id);

    if (selecionada) {

        selecionada.classList.add('active');

    }


    const botoes = document.querySelectorAll('.menu button');

    botoes.forEach(function(btn) {

        btn.classList.remove('active');

    });


    botao.classList.add('active');

}


/* =========================
   PESQUISA NAS TABELAS
========================= */

function buscarTabela(input, tabelaId) {

    const filtro = input.value.toLowerCase();

    const tabela = document.getElementById(tabelaId);

    const linhas = tabela
        .getElementsByTagName('tbody')[0]
        .getElementsByTagName('tr');


    for (let i = 0; i < linhas.length; i++) {

        const texto = linhas[i]
            .textContent
            .toLowerCase();

        if (texto.includes(filtro)) {

            linhas[i].style.display = '';

        } else {

            linhas[i].style.display = 'none';

        }

    }

}

</script>

</body>

</html>