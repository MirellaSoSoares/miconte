<?php
include 'miconn.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'aluno') {
    header("Location: index.php");
    exit;
}

$usuarioNome = $_SESSION['nome'] ?? 'Aluno';
$usuarioEmail = $_SESSION['usuario_email'] ?? 'email@exemplo.com';

$stmt = $db->query("SELECT * FROM livros ORDER BY titulo");
$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$livrosAlugados = [];
try {
    $sql = "
        SELECT r.*, l.titulo, l.autor, l.capa
        FROM reservas r
        LEFT JOIN livros l ON l.id = r.livro_id
        WHERE r.aluno_id = :aluno_id
        ORDER BY r.criado_em DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':aluno_id' => $_SESSION['usuario_id']]);
    $livrosAlugados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $livrosAlugados = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * { box-sizing: border-box; }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5dc;
            color: #2d2d2d;
            display: flex;
            flex-direction: column;
        }

        .app-shell {
            display: flex;
            min-height: calc(100vh - 52px);
            flex: 1;
        }

        .sidebar {
            width: 240px;
            background: #722f37;
            border-right: 2px solid #e5a7a1;
            padding: 0;
        }

        .sidebar-header {
            padding: 14px 16px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.25);
        }

        .sidebar-header h3 {
            margin: 0;
            color: #fff;
            font-size: 1.8rem;
            text-align: center;
            font-weight: 700;
        }

        .nav {
            display: flex;
            flex-direction: column;
            padding-top: 6px;
        }

        .nav-item {
            background: transparent;
            border: none;
            color: #fff;
            width: 100%;
            text-align: left;
            padding: 12px 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s ease;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255,255,255,0.08);
            border-left: 4px solid #f3cbc0;
            padding-left: 12px;
        }

        .nav-item.outline {
            margin-top: 8px;
        }

        .page-header {
            background: #722f37;
            color: white;
            text-align: center;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .content {
            flex: 1;
            padding: 16px 24px 20px;
            background: #f5f5dc;
            display: flex;
            justify-content: center;
        }

        .panel {
            display: none;
            width: 100%;
            max-width: 820px;
        }

        .panel.active {
            display: block;
        }

        .title-bar {
            margin: 0 0 14px;
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d2d2d;
        }

        .book-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 18px auto 0;
            width: 100%;
            max-width: 820px;
            align-items: center;
        }

        .book-card {
            display: flex;
            align-items: center;
            gap: 16px;
            width: 100%;
            max-width: 720px;
            background: rgba(145, 138, 112, 0.92);
            border-radius: 8px;
            padding: 14px 16px 14px 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }

        .book-cover {
            width: 90px;
            height: 118px;
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            background: #ddd;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .book-info {
            flex: 1;
            min-width: 0;
            padding-right: 42px;
        }

        .book-info h3 {
            margin: 0 0 6px;
            color: #fff;
            font-size: 1.6rem;
            line-height: 1.1;
        }

        .book-info p {
            margin: 0 0 10px;
            color: #111;
            font-size: 0.95rem;
        }

        .book-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            width: 100%;
        }

        .btn-details {
            margin-left: auto;
            align-self: flex-end;
        }

        .favorites-actions {
            justify-content: flex-end;
            margin-top: 8px;
        }

        .btn {
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-primary {
            background: #746f5c;
            color: white;
        }

        .btn-secondary {
            background: #d9d9d9;
            color: #333;
        }

        .btn:hover {
            opacity: 0.96;
        }

        .favorite-toggle {
            position: absolute;
            top: 12px;
            right: 12px;
            border: none;
            background: transparent;
            color: #f3d55f;
            font-size: 1.9rem;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            margin: 0;
            z-index: 2;
        }

        .favorite-toggle.is-favorited {
            color: #ffd749;
            text-shadow: 0 0 8px rgba(255, 215, 73, 0.8);
        }

        .empty-state {
            max-width: 700px;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 18px;
            color: #444;
            font-size: 1rem;
        }

        .profile-box {
            max-width: 700px;
            background: rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 20px;
            color: #2b2b2b;
        }

        .profile-box .row {
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .profile-box strong {
            color: #111;
        }

        footer {
            background: #722f37;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 0.9rem;
        }

        @media (max-width: 900px) {
            .app-shell {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .content {
                padding: 18px 20px;
            }

            .book-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<header class="page-header">
    <h2>Conheça alguns dos nossos bons livros disponíveis abaixo!</h2>
</header>

<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>Meu Perfil</h3>
        </div>

        <nav class="nav">
            <button class="nav-item active" data-panel="home">
                <span>🏠</span>
                <span>Home</span>
            </button>

            <button class="nav-item" data-panel="alugados">
                <span>📚</span>
                <span>Livros alocados</span>
            </button>

            <button class="nav-item" data-panel="favoritos">
                <span>⭐</span>
                <span>Favoritos</span>
            </button>

            <button class="nav-item" data-panel="configuracoes">
                <span>⚙️</span>
                <span>Configurações</span>
            </button>

            <button class="nav-item outline" id="logoutBtn">
                <span>🚪</span>
                <span>Sair</span>
            </button>
        </nav>
    </aside>

    <main class="content">
        <section class="panel active" id="home-panel">
            <div class="book-list">
                <?php foreach ($livros as $livro): ?>
                    <div class="book-card" data-book-id="<?= $livro['id'] ?>">
                        <div class="book-cover">
                            <img src="imagens/<?= htmlspecialchars($livro['capa']) ?>" alt="<?= htmlspecialchars($livro['titulo']) ?>">
                        </div>

                        <div class="book-info">
                            <h3><?= htmlspecialchars($livro['titulo']) ?></h3>
                            <p><?= htmlspecialchars($livro['autor']) ?></p>

                            <div class="book-actions">
                                <button type="button" class="favorite-toggle" data-id="<?= $livro['id'] ?>" aria-label="Adicionar aos favoritos">☆</button>

                                <form method="POST" action="sinopse.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $livro['id'] ?>">
                                    <button type="submit" class="btn btn-secondary">Sinopse</button>
                                </form>

                                <button type="button" class="btn btn-primary" onclick="abrirCalendario(<?= $livro['id'] ?>)">Verificar disponibilidade</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel" id="alugados-panel">
            <h2 class="title-bar">Livros alocados</h2>

            <?php if (empty($livrosAlugados)): ?>
                <div class="empty-state">Você ainda não reservou nenhum livro.</div>
            <?php else: ?>
                <div class="book-list">
                    <?php foreach ($livrosAlugados as $livro): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <img src="imagens/<?= htmlspecialchars($livro['capa'] ?? 'default.png') ?>" alt="<?= htmlspecialchars($livro['titulo'] ?? 'Livro') ?>">
                            </div>

                            <div class="book-info">
                                <h3><?= htmlspecialchars($livro['titulo'] ?? 'Livro') ?></h3>
                                <p><?= htmlspecialchars($livro['autor'] ?? '') ?></p>

                                <div class="book-actions">
                                    <form method="POST" action="sinopse.php" style="display:inline; margin-left:auto;">
                                        <input type="hidden" name="id" value="<?= $livro['id'] ?? 0 ?>">
                                        <button type="submit" class="btn btn-primary btn-details">Detalhes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel" id="favoritos-panel">
            <h2 class="title-bar">Favoritos</h2>
            <div id="favoritosList" class="book-list"></div>
        </section>

        <section class="panel" id="configuracoes-panel">
            <h2 class="title-bar">Configurações</h2>

            <div class="profile-box">
                <div class="row"><strong>Nome:</strong> <?= htmlspecialchars($usuarioNome) ?></div>
                <div class="row"><strong>Email:</strong> <?= htmlspecialchars($usuarioEmail) ?></div>
                <div class="row"><strong>Tipo:</strong> Aluno</div>
            </div>
        </section>
    </main>
</div>

<div id="calendarioModal" style="display:none; position:fixed; top:0; right:0; width:38%; height:100%; background:#fff; box-shadow:-2px 0 8px rgba(0,0,0,0.3); z-index:1000;">
    <div style="padding:24px;">
        <span class="close" onclick="fecharCalendario()" style="float:right; font-size:24px; cursor:pointer;">&times;</span>
        <h3>Selecione a data e horário</h3>

        <input type="text" id="data" name="data" required style="width:100%; margin:12px 0; padding:10px;">

        <select id="hora" name="hora" required style="width:100%; margin-bottom:12px; padding:10px;">
            <?php
            $inicio = strtotime("09:20");
            $fim = strtotime("16:30");
            for ($hora = $inicio; $hora <= $fim; $hora += 20 * 60) {
                echo "<option value='" . date("H:i", $hora) . "'>" . date("H:i", $hora) . "</option>";
            }
            ?>
        </select>

        <button type="button" class="btn btn-primary" onclick="abrirModalConfirmacao()">Confirmar</button>
    </div>
</div>

<div id="modalConfirmacao" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:2000;">
    <div style="width:360px; margin:150px auto; background:#fff; border-radius:10px; padding:24px; text-align:center;">
        <h3>Deseja confirmar a reserva?</h3>
        <div style="display:flex; justify-content:center; gap:12px; margin-top:20px;">
            <button class="btn btn-primary" onclick="confirmarReserva()">Sim</button>
            <button class="btn btn-secondary" onclick="fecharModalConfirmacao()">Não</button>
        </div>
    </div>
</div>

<div id="modalSucesso" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:2000;">
    <div style="width:340px; margin:150px auto; background:#fff; border-radius:10px; padding:24px; text-align:center;">
        <h3>Reserva concluída!</h3>
        <div style="font-size:50px; color:green; margin:12px 0;">✔</div>
        <p>Seu processo foi finalizado com sucesso.</p>
        <button class="btn btn-primary" onclick="fecharModalSucesso()">OK</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    const FAVORITES_KEY = 'miconte_favoritos';
    const livrosData = <?php echo json_encode($livros, JSON_UNESCAPED_UNICODE); ?>;

    function getFavoritos() {
        try {
            return JSON.parse(localStorage.getItem(FAVORITES_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function setFavoritos(lista) {
        localStorage.setItem(FAVORITES_KEY, JSON.stringify(lista));
    }

    function atualizarEstrelas() {
        const favoritos = getFavoritos();
        document.querySelectorAll('.favorite-toggle').forEach(btn => {
            const id = Number(btn.dataset.id);
            const ativo = favoritos.includes(id);
            btn.classList.toggle('is-favorited', ativo);
            btn.textContent = ativo ? '★' : '☆';
            btn.setAttribute('aria-label', ativo ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
        });
    }

    function renderFavoritos() {
        const favoritos = getFavoritos();
        const lista = document.getElementById('favoritosList');
        if (!lista) return;

        const livrosFavoritos = livrosData.filter(livro => favoritos.includes(Number(livro.id)));

        if (!livrosFavoritos.length) {
            lista.innerHTML = '<div class="empty-state">Você ainda não marcou nenhum livro como favorito.</div>';
            return;
        }

        lista.innerHTML = livrosFavoritos.map(livro => `
            <div class="book-card">
                <div class="book-cover">
                    <img src="imagens/${livro.capa || 'default.png'}" alt="${livro.titulo}">
                </div>

                <div class="book-info">
                    <h3>${livro.titulo}</h3>
                    <p>${livro.autor}</p>

                    <div class="book-actions favorites-actions">
                        <button type="button" class="favorite-toggle is-favorited" data-id="${livro.id}" aria-label="Remover dos favoritos">★</button>
                        <form method="POST" action="sinopse.php" style="display:inline; margin-left:0;">
                            <input type="hidden" name="id" value="${livro.id}">
                            <button type="submit" class="btn btn-primary btn-details">Detalhes</button>
                        </form>
                    </div>
                </div>
            </div>
        `).join('');
    }

    document.addEventListener('click', function (event) {
        const navItem = event.target.closest('.nav-item');
        if (navItem) {
            const panelName = navItem.dataset.panel;
            if (!panelName) return;

            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            navItem.classList.add('active');

            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.toggle('active', panel.id === panelName + '-panel');
            });

            if (panelName === 'favoritos') {
                renderFavoritos();
            }
        }

        if (event.target.closest('.favorite-toggle')) {
            const btn = event.target.closest('.favorite-toggle');
            const id = Number(btn.dataset.id);
            const favoritos = getFavoritos();
            const jaExiste = favoritos.includes(id);

            const novaLista = jaExiste
                ? favoritos.filter(item => item !== id)
                : [...favoritos, id];

            setFavoritos(novaLista);
            atualizarEstrelas();
            renderFavoritos();
        }

        if (event.target.closest('#logoutBtn')) {
            window.location.href = 'index.php';
        }
    });

    let calendarioPicker = null;

    function inicializarCalendario() {
        if (calendarioPicker) return calendarioPicker;

        calendarioPicker = flatpickr('#data', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            disable: [
                function(date) {
                    return (date.getDay() === 0 || date.getDay() === 6);
                }
            ],
            locale: 'pt'
        });

        return calendarioPicker;
    }

    window.onload = function () {
        inicializarCalendario();
        atualizarEstrelas();
        renderFavoritos();
    };

    window.livroSelecionado = null;

    function abrirCalendario(livroId) {
        window.livroSelecionado = livroId;
        document.getElementById('calendarioModal').style.display = 'block';

        const picker = inicializarCalendario();
        setTimeout(() => {
            if (picker && typeof picker.open === 'function') {
                picker.open();
            }
        }, 80);
    }

    function fecharCalendario() {
        document.getElementById('calendarioModal').style.display = 'none';
    }

    function abrirModalConfirmacao() {
        const data = document.getElementById('data').value;
        const hora = document.getElementById('hora').value;

        if (!data) {
            alert('Por favor, selecione uma data antes de confirmar!');
            return;
        }

        if (!hora) {
            alert('Por favor, selecione um horário antes de confirmar!');
            return;
        }

        document.getElementById('modalConfirmacao').style.display = 'block';
    }

    function fecharModalConfirmacao() {
        document.getElementById('modalConfirmacao').style.display = 'none';
    }

    function confirmarReserva() {
        const data = document.getElementById('data').value;
        const hora = document.getElementById('hora').value;
        const livroId = window.livroSelecionado;

        fetch('confirmar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'livro_id=' + livroId + '&data=' + data + '&hora=' + hora
        })
        .then(response => response.text())
        .then(result => {
            fecharModalConfirmacao();
            fecharCalendario();

            if (result.trim() === 'OK') {
                document.getElementById('modalSucesso').style.display = 'block';
            } else {
                alert('Erro ao salvar reserva: ' + result);
            }
        })
        .catch(() => {
            alert('Erro de conexão!');
        });
    }

    function fecharModalSucesso() {
        document.getElementById('modalSucesso').style.display = 'none';
    }
</script>

</body>

<footer>
    &copy; <?php echo date("Y"); ?> Biblioteca Escolar - Todos os direitos reservados
</footer>
</html>
