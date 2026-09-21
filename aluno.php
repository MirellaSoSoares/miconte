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

$livros = array_map(function ($livro) {
    $livro['genero'] = trim((string) ($livro['generos'] ?? ''));
    if ($livro['genero'] === '') {
        $livro['genero'] = 'Romance';
    }
    return $livro;
}, $livros);

$generosDisponiveis = ['Todos', 'Romance', 'Fantasia', 'Terror'];

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

if (isset($_GET['ajax']) && $_GET['ajax'] === 'alugados') {
    $html = '<h2 class="title-bar">Livros alocados</h2>';

    if (empty($livrosAlugados)) {
        $html .= '<div class="empty-state">Você ainda não reservou nenhum livro.</div>';
    } else {
        $html .= '<div class="book-list">';
        foreach ($livrosAlugados as $livro) {
            $html .= '<div class="book-card">';
            $html .= '  <div class="book-cover">';
            $html .= '    <img src="imagens/' . htmlspecialchars($livro['capa'] ?? 'default.png') . '" alt="' . htmlspecialchars($livro['titulo'] ?? 'Livro') . '">';
            $html .= '  </div>';
            $html .= '  <div class="book-info">';
            $html .= '    <h3>' . htmlspecialchars($livro['titulo'] ?? 'Livro') . '</h3>';
            $html .= '    <p>' . htmlspecialchars($livro['autor'] ?? '') . '</p>';
            $html .= '    <div class="book-actions">';
            $html .= '      <form method="POST" action="sinopse.php?tab=alugados" class="inline-form align-end">';
            $html .= '        <input type="hidden" name="id" value="' . ($livro['livro_id'] ?? $livro['id'] ?? 0) . '">';
            $html .= '        <button type="submit" class="btn btn-primary btn-details">Detalhes</button>';
            $html .= '      </form>';
            $html .= '    </div>';
            $html .= '  </div>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    echo $html;
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/aluno.css">
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

            <a href="#" class="nav-item outline" id="logoutBtn" style="text-decoration:none;color:#ffffff;display:flex;align-items:center;gap:10px;" onclick="if(confirm('Deseja sair da sua sessão?')){ window.location.href='logout.php'; } return false;">
                <span>🚪</span>
                <span>Sair</span>
            </a>
        </nav>
    </aside>

    <main class="content">
        <section class="panel active" id="home-panel">
            <div class="book-filters">
                <div class="search-field search-wide">
                    <label for="searchLivro">Buscar</label>
                    <input type="text" id="searchLivro" placeholder="Nome do livro ou autor">
                </div>
            </div>

            <div class="genre-filter-bar" id="genreFilterBar">
                <?php foreach ($generosDisponiveis as $genero): ?>
                    <button type="button" class="genre-tag <?= $genero === 'Todos' ? 'active' : '' ?>" data-genre="<?= htmlspecialchars($genero, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($genero) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="book-list" id="homeBookList">
                <?php foreach ($livros as $livro): ?>
                    <div class="book-card"
                         data-book-id="<?= $livro['id'] ?>"
                         data-titulo="<?= htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                         data-autor="<?= htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8') ?>"
                         data-genero="<?= htmlspecialchars($livro['genero'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="book-cover">
                            <img src="imagens/<?= htmlspecialchars($livro['capa']) ?>" alt="<?= htmlspecialchars($livro['titulo']) ?>">
                        </div>

                        <div class="book-info">
                            <h3><?= htmlspecialchars($livro['titulo']) ?></h3>
                            <p><?= htmlspecialchars($livro['autor']) ?></p>
                            <span class="book-genre-tag"><?= htmlspecialchars($livro['genero']) ?></span>

                            <div class="book-actions">
                                <button type="button" class="favorite-toggle" data-id="<?= $livro['id'] ?>" aria-label="Adicionar aos favoritos">☆</button>

                                <form method="POST" action="sinopse.php?tab=home" class="inline-form">
                                    <input type="hidden" name="id" value="<?= $livro['id'] ?>">
                                    <input type="hidden" name="tab" value="home">
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
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-details"
                                        data-reserva-id="<?= (int) ($livro['id'] ?? 0) ?>"
                                        data-livro-id="<?= (int) ($livro['livro_id'] ?? 0) ?>"
                                        data-livro-titulo="<?= htmlspecialchars($livro['titulo'] ?? 'Livro', ENT_QUOTES, 'UTF-8') ?>"
                                        data-data="<?= htmlspecialchars($livro['data'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-hora="<?= htmlspecialchars($livro['hora'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        onclick="abrirDetalhesReserva(this)">
                                        Detalhes
                                    </button>
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
                <div class="profile-header">
                    <div class="profile-avatar"><?= strtoupper(substr($usuarioNome, 0, 1)) ?></div>
                    <div>
                        <h3 class="profile-name"><?= htmlspecialchars($usuarioNome) ?></h3>
                        <p class="profile-role">Aluno</p>
                    </div>
                </div>

                <div class="profile-grid">
                    <div class="profile-card">
                        <span class="profile-label">Nome</span>
                        <p class="profile-value"><?= htmlspecialchars($usuarioNome) ?></p>
                    </div>

                    <div class="profile-card">
                        <span class="profile-label">Email</span>
                        <p class="profile-value"><?= htmlspecialchars($usuarioEmail) ?></p>
                    </div>

                    <div class="profile-card">
                        <span class="profile-label">Tipo de conta</span>
                        <p class="profile-value">Aluno</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<div id="calendarioModal" class="calendario-modal">
    <div class="calendario-modal-body">
        <span class="close calendario-close" onclick="fecharCalendario()">&times;</span>
        <h3>Selecione a data e horário</h3>

        <div id="disponibilidadeMensagem" class="disponibilidade-mensagem" aria-live="polite"></div>
        <div id="disponibilidadeEstoque" class="disponibilidade-estoque" aria-live="polite"></div>

        <input type="text" id="data" name="data" required class="campo-data-hora">

        <select id="hora" name="hora" required class="campo-data-hora select-hora">
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

<div id="modalConfirmacao" class="confirmation-overlay">
    <div class="confirmation-box">
        <h3>Deseja confirmar a reserva?</h3>
        <div class="confirmation-actions">
            <button class="confirm-yes" onclick="confirmarReserva()">Sim</button>
            <button class="confirm-no" onclick="fecharModalConfirmacao()">Não</button>
        </div>
    </div>
</div>

<div id="detalhesReservaModal" class="modal-details">
    <div class="modal-details-box">
        <button type="button" class="modal-details-close" onclick="fecharDetalhesReserva()" aria-label="Fechar detalhes">←</button>
        <h3>Reserva do livro</h3>
        <div class="modal-details-content" id="detalhesReservaConteudo"></div>
        <div class="modal-details-actions">
            <button type="button" class="cancel-btn" onclick="abrirConfirmacaoCancelamento()">Cancelar reserva</button>
        </div>
    </div>
</div>

<div id="cancelarReservaModal" class="confirmation-overlay">
    <div class="confirmation-box">
        <h3>Deseja confirmar o cancelamento?</h3>
        <div class="confirmation-actions">
            <button class="confirm-yes" onclick="cancelarReserva()">Sim</button>
            <button class="confirm-no" onclick="fecharModalCancelamento()">Não</button>
        </div>
    </div>
</div>

<div id="modalSucesso" class="success-overlay">
    <div class="success-box">
        <h3>Reserva concluída!</h3>
        <div class="success-icon">✔</div>
        <p>Seu processo foi finalizado com sucesso.</p>
        <div class="success-actions">
            <button class="confirm-yes" onclick="fecharModalSucesso()">OK</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    const FAVORITES_KEY = 'miconte_favoritos';
    const livrosData = <?php echo json_encode($livros, JSON_UNESCAPED_UNICODE); ?>;

    const filtrosLivros = {
        texto: '',
        genero: 'Todos'
    };

    function normalizarTexto(texto) {
        return (texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function aplicarFiltrosLivros() {
        const termo = normalizarTexto(filtrosLivros.texto);
        const generoSelecionado = normalizarTexto(filtrosLivros.genero);

        document.querySelectorAll('#home-panel .book-card').forEach(card => {
            const titulo = normalizarTexto(card.dataset.titulo);
            const autor = normalizarTexto(card.dataset.autor);
            const genero = normalizarTexto(card.dataset.genero || 'Romance');

            const matchesTexto = !termo || titulo.includes(termo) || autor.includes(termo);
            const matchesGenero = generoSelecionado === 'todos' || genero === generoSelecionado;

            card.style.display = matchesTexto && matchesGenero ? 'flex' : 'none';
        });
    }

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
                        <form method="POST" action="sinopse.php?tab=favoritos" class="inline-form no-margin-left">
                            <input type="hidden" name="id" value="${livro.id}">
                            <input type="hidden" name="tab" value="favoritos">
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
            ativarAba(panelName);
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

        // logout handled by link's onclick confirmation; no automatic redirect here
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

    function atualizarStatusDisponibilidade(livro) {
        const mensagem = document.getElementById('disponibilidadeMensagem');
        const estoque = document.getElementById('disponibilidadeEstoque');
        const inputData = document.getElementById('data');
        const inputHora = document.getElementById('hora');
        const confirmarBtn = document.querySelector('#calendarioModal button');

        if (!livro) {
            mensagem.textContent = 'Não foi possível identificar esse livro no momento.';
            mensagem.style.display = 'block';
            mensagem.style.color = '#b00020';
            estoque.style.display = 'none';
            if (inputData) inputData.disabled = true;
            if (inputHora) inputHora.disabled = true;
            if (confirmarBtn) confirmarBtn.disabled = true;
            return;
        }

        const quantidade = Number(livro.quantidade || 0);

        if (quantidade <= 0) {
            mensagem.textContent = 'Que pena! Este exemplar acabou. Tente outro livro ou volte mais tarde.';
            mensagem.style.display = 'block';
            mensagem.style.color = '#b00020';
            estoque.textContent = 'Quantidade disponível: 0';
            estoque.style.display = 'block';
            estoque.style.color = '#b00020';
            if (inputData) inputData.disabled = true;
            if (inputHora) inputHora.disabled = true;
            if (confirmarBtn) confirmarBtn.disabled = true;
            return;
        }

        mensagem.style.display = 'none';
        estoque.textContent = 'Disponíveis: ' + quantidade + ' exemplar(es)';
        estoque.style.display = 'block';
        estoque.style.color = '#1e7e34';
        if (inputData) inputData.disabled = false;
        if (inputHora) inputHora.disabled = false;
        if (confirmarBtn) confirmarBtn.disabled = false;
    }

    async function recarregarLivrosAlugados() {
        const painel = document.getElementById('alugados-panel');
        if (!painel) return;

        try {
            const resposta = await fetch('aluno.php?ajax=alugados', { cache: 'no-store' });
            const html = await resposta.text();
            painel.innerHTML = html;
        } catch (error) {
            console.error('Erro ao recarregar livros alocados:', error);
        }
    }

    function abrirDetalhesReserva(botao) {
        const reservaId = Number(botao.dataset.reservaId || 0);
        const livroId = Number(botao.dataset.livroId || 0);
        const titulo = botao.dataset.livroTitulo || 'Livro';
        const data = botao.dataset.data || '';
        const hora = botao.dataset.hora || '';

        const conteudo = document.getElementById('detalhesReservaConteudo');
        conteudo.innerHTML = '<strong>Livro:</strong> ' + titulo + '<br>' +
            '<strong>Data:</strong> ' + data + '<br>' +
            '<strong>Horário:</strong> ' + hora;

        document.getElementById('detalhesReservaModal').dataset.reservaId = reservaId;
        document.getElementById('detalhesReservaModal').dataset.livroId = livroId;
        document.getElementById('detalhesReservaModal').style.display = 'flex';
    }

    function fecharDetalhesReserva() {
        document.getElementById('detalhesReservaModal').style.display = 'none';
    }

    function abrirConfirmacaoCancelamento() {
        document.getElementById('cancelarReservaModal').style.display = 'flex';
    }

    function fecharModalCancelamento() {
        document.getElementById('cancelarReservaModal').style.display = 'none';
    }

    function ativarAba(tabName) {
        const navItem = document.querySelector('.nav-item[data-panel="' + tabName + '"]');
        if (!navItem) return;

        document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
        navItem.classList.add('active');

        document.querySelectorAll('.panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === tabName + '-panel');
        });

        if (tabName === 'favoritos') {
            renderFavoritos();
        }

        fecharCalendario();
        fecharModalConfirmacao();
        fecharModalSucesso();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const inputBusca = document.getElementById('searchLivro');
        const generoTags = document.querySelectorAll('.genre-tag');

        if (inputBusca) {
            inputBusca.addEventListener('input', function () {
                filtrosLivros.texto = this.value;
                aplicarFiltrosLivros();
            });
        }

        generoTags.forEach(tag => {
            tag.addEventListener('click', function () {
                const genero = this.dataset.genre || 'Todos';
                filtrosLivros.genero = genero;
                generoTags.forEach(item => item.classList.toggle('active', item === this));
                aplicarFiltrosLivros();
            });
        });

        inicializarCalendario();
        atualizarEstrelas();
        renderFavoritos();

        const tabInicial = new URLSearchParams(window.location.search).get('tab');
        if (tabInicial) {
            ativarAba(tabInicial);
        }
    });

    window.livroSelecionado = null;

    function abrirCalendario(livroId) {
        window.livroSelecionado = livroId;
        const livro = livrosData.find(item => Number(item.id) === Number(livroId));
        atualizarStatusDisponibilidade(livro);
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
        const livro = livrosData.find(item => Number(item.id) === Number(window.livroSelecionado));

        if (livro && Number(livro.quantidade || 0) <= 0) {
            atualizarStatusDisponibilidade(livro);
            return;
        }

        if (!data) {
            alert('Por favor, selecione uma data antes de confirmar.');
            return;
        }

        if (!hora) {
            alert('Por favor, selecione um horário antes de confirmar.');
            return;
        }

        document.getElementById('calendarioModal').style.display = 'none';
        document.getElementById('modalConfirmacao').style.display = 'flex';
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
            const mensagem = result.trim();
            fecharModalConfirmacao();
            fecharCalendario();

            if (mensagem === 'OK') {
                const livroAtual = livrosData.find(item => Number(item.id) === Number(livroId));
                if (livroAtual) {
                    livroAtual.quantidade = Math.max(0, Number(livroAtual.quantidade || 0) - 1);
                }
                recarregarLivrosAlugados();
                document.getElementById('modalSucesso').style.display = 'flex';
                return;
            }

            if (mensagem === 'Você já reservou este livro') {
                alert('Você já reservou este livro. Não é possível alocar o mesmo exemplar novamente.');
                return;
            }

            if (mensagem === 'Você já reservou este livro para essa data e horário') {
                alert('Essa data e horário já estão reservados para você neste livro.');
                return;
            }

            if (mensagem === 'Limite de 3 livros por usuário atingido') {
                alert('Você já atingiu o limite de 3 livros reservados.');
                return;
            }

            if (mensagem === 'Livro indisponível') {
                alert('Este livro não está mais disponível para locação no momento.');
                return;
            }

            alert('Não foi possível concluir a reserva. Tente novamente.');
        })
        .catch(() => {
            alert('Erro de conexão. Tente novamente em alguns instantes.');
        });
    }

    function cancelarReserva() {
        const modal = document.getElementById('detalhesReservaModal');
        const reservaId = Number(modal.dataset.reservaId || 0);
        const livroId = Number(modal.dataset.livroId || 0);

        if (!reservaId) {
            alert('Não foi possível identificar a reserva para cancelar.');
            return;
        }

        fetch('confirmar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=cancel&reserva_id=' + reservaId + '&livro_id=' + livroId
        })
        .then(response => response.text())
        .then(result => {
            const mensagem = result.trim();
            fecharModalCancelamento();
            fecharDetalhesReserva();

            if (mensagem === 'OK') {
                const livroAtual = livrosData.find(item => Number(item.id) === livroId);
                if (livroAtual) {
                    livroAtual.quantidade = Number(livroAtual.quantidade || 0) + 1;
                }
                recarregarLivrosAlugados();
                document.getElementById('modalSucesso').querySelector('h3').textContent = 'Reserva cancelada!';
                document.getElementById('modalSucesso').querySelector('p').textContent = 'Seu livro voltou ao estoque e a reserva foi removida.';
                document.getElementById('modalSucesso').style.display = 'flex';
                return;
            }

            alert(mensagem || 'Não foi possível cancelar esta reserva.');
        })
        .catch(() => {
            alert('Erro de conexão. Tente novamente em alguns instantes.');
        });
    }

    function fecharModalSucesso() {
        const modalSucesso = document.getElementById('modalSucesso');
        modalSucesso.querySelector('h3').textContent = 'Reserva concluída!';
        modalSucesso.querySelector('p').textContent = 'Seu processo foi finalizado com sucesso.';
        modalSucesso.style.display = 'none';
    }
</script>

</body>

<footer>
    &copy; <?php echo date("Y"); ?> Biblioteca Escolar - Todos os direitos reservados
</footer>
</html>
