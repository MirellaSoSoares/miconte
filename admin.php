<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/miconn.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}

$adminNome = (string)($_SESSION['usuario_nome'] ?? 'Administrador');
$mensagem = '';
$tipoMensagem = '';
$usuariosInternos = [];
$livros = [];
$reservas = [];
$totalLivros = 0;
$totalReservas = 0;
$config = [
    'prazo_emprestimo' => 7,
    'dias_bloqueio' => 3,
    'mensagem_sistema' => 'Sistema em funcionamento.'
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!hash_equals((string)$_SESSION['csrf_admin'], $token)) {
            throw new RuntimeException('Sessão de segurança inválida. Atualize a página e tente novamente.');
        }

        $acao = (string)($_POST['acao'] ?? '');
        if ($acao === 'cadastrar_livro') {
            $titulo = trim((string)($_POST['titulo'] ?? ''));
            $autor = trim((string)($_POST['autor'] ?? ''));
            $capa = trim((string)($_POST['capa'] ?? ''));
            $sinopse = trim((string)($_POST['sinopse'] ?? ''));
            $quantidade = filter_var($_POST['quantidade'] ?? null, FILTER_VALIDATE_INT);

            if ($titulo === '' || $autor === '' || $quantidade === false || $quantidade < 0 || mb_strlen($titulo) > 255 || mb_strlen($autor) > 255 || mb_strlen($capa) > 255) {
                throw new InvalidArgumentException('Confira os campos do livro. Título, autor e quantidade válida são obrigatórios.');
            }
            $stmt = $db->prepare('INSERT INTO livros (titulo, autor, capa, sinopse, quantidade) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$titulo, $autor, $capa, $sinopse, $quantidade]);
            $mensagem = 'Livro cadastrado com sucesso.';
            $tipoMensagem = 'sucesso';
        } elseif ($acao === 'editar_livro') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $titulo = trim((string)($_POST['titulo'] ?? ''));
            $autor = trim((string)($_POST['autor'] ?? ''));
            $capa = trim((string)($_POST['capa'] ?? ''));
            $sinopse = trim((string)($_POST['sinopse'] ?? ''));
            $quantidade = filter_var($_POST['quantidade'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $titulo === '' || $autor === '' || $quantidade === false || $quantidade < 0 || mb_strlen($titulo) > 255 || mb_strlen($autor) > 255 || mb_strlen($capa) > 255) {
                throw new InvalidArgumentException('Confira os dados informados para editar o livro.');
            }
            $stmt = $db->prepare('UPDATE livros SET titulo = ?, autor = ?, capa = ?, sinopse = ?, quantidade = ? WHERE id = ?');
            $stmt->execute([$titulo, $autor, $capa, $sinopse, $quantidade, $id]);
            $mensagem = $stmt->rowCount() ? 'Livro atualizado com sucesso.' : 'Nenhuma alteração foi necessária ou o livro não foi encontrado.';
            $tipoMensagem = 'sucesso';
        } elseif ($acao === 'excluir_livro') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException('Identificador de livro inválido.');
            }
            // Não exclui livros associados a reservas: preserva o histórico e evita perda de integridade.
            $check = $db->prepare('SELECT COUNT(*) FROM reservas WHERE livro_id = ?');
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) {
                throw new RuntimeException('Este livro possui reservas vinculadas e não pode ser excluído.');
            }
            $stmt = $db->prepare('DELETE FROM livros WHERE id = ?');
            $stmt->execute([$id]);
            $mensagem = $stmt->rowCount() ? 'Livro excluído.' : 'Livro não encontrado.';
            $tipoMensagem = 'sucesso';
        } elseif ($acao === 'salvar_config' || $acao === 'toggle_status') {
            // Desativado intencionalmente: a tabela/coluna necessária não existe no banco atual.
            throw new RuntimeException('Esta operação está temporariamente desativada para não alterar a estrutura do banco de dados.');
        }
    }

    $usuariosInternos = $db->query("SELECT id, nome, email, tipo FROM usuarios WHERE tipo IN ('admin', 'professor') ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $livros = $db->query('SELECT id, titulo, autor, capa, sinopse, quantidade FROM livros ORDER BY titulo ASC')->fetchAll(PDO::FETCH_ASSOC);
    $totalLivros = (int)$db->query('SELECT COUNT(*) FROM livros')->fetchColumn();
    $totalReservas = (int)$db->query('SELECT COUNT(*) FROM reservas')->fetchColumn();

    // Leitura opcional. Não cria tabela nem grava configuração.
    try {
        $configDb = $db->query('SELECT prazo_emprestimo, dias_bloqueio, mensagem_sistema FROM configuracoes ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if ($configDb) {
            $config = [
                'prazo_emprestimo' => (int)$configDb['prazo_emprestimo'],
                'dias_bloqueio' => (int)$configDb['dias_bloqueio'],
                'mensagem_sistema' => (string)$configDb['mensagem_sistema']
            ];
        }
    } catch (Throwable $ignored) {
        // A tabela não existe: mantém valores de exibição padrão sem alterar o banco.
    }

    // Consulta compatível com as colunas já verificadas em usuarios, livros e reservas.
    try {
        $reservas = $db->query(
            "SELECT r.id, r.aluno_email, u.nome, l.titulo, r.data, r.hora, r.criado_em
             FROM reservas r
             LEFT JOIN usuarios u ON r.aluno_id = u.id
             LEFT JOIN livros l ON r.livro_id = l.id
             ORDER BY r.data ASC, r.hora ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $ignored) {
        $reservas = [];
    }
} catch (Throwable $e) {
    $mensagem = $e instanceof InvalidArgumentException || $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Não foi possível concluir a operação. Verifique a conexão e os dados.';
    $tipoMensagem = 'erro';
    // Recarrega somente dados de leitura para a página não ficar vazia após erro de formulário.
    try {
        $usuariosInternos = $db->query("SELECT id, nome, email, tipo FROM usuarios WHERE tipo IN ('admin', 'professor') ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $livros = $db->query('SELECT id, titulo, autor, capa, sinopse, quantidade FROM livros ORDER BY titulo ASC')->fetchAll(PDO::FETCH_ASSOC);
        $totalLivros = (int)$db->query('SELECT COUNT(*) FROM livros')->fetchColumn();
        $totalReservas = (int)$db->query('SELECT COUNT(*) FROM reservas')->fetchColumn();
    } catch (Throwable $ignored) {}
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiConte+ | Admin</title>
    <style>
        :root {
            --vinho: #722f37;
            --vinho-escuro: #4f2028;
            --vinho-claro: #8d4d56;
            --bege: #f5f5dc;
            --bege-claro: #fffaf3;
            --bege-card: #e9e0d0;
            --areia: #918a70;
            --salmao: #f3cbc0;
            --dourado: #f3d55f;
            --texto: #2d2d2d;
            --texto-suave: #5e564d;
            --branco: #ffffff;
            --cinza: #f3f3f3;
            --verde: #2d7d46;
            --vermelho: #b33a3a;
            --amarelo: #cf9a2a;
            --sombra: 0 12px 28px rgba(36, 22, 18, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #f4ead8 0%, #f8f2ea 100%);
            color: var(--texto);
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.22), transparent 20%),
                radial-gradient(circle at 80% 30%, rgba(122,113,92,0.08), transparent 18%),
                linear-gradient(90deg, rgba(255,255,255,0.08), rgba(0,0,0,0.02)),
                repeating-linear-gradient(
                    0deg,
                    rgba(120, 105, 82, 0.02) 0,
                    rgba(120, 105, 82, 0.02) 1px,
                    transparent 1px,
                    transparent 4px
                );
            pointer-events: none;
            opacity: 0.95;
            z-index: 0;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        .sidebar {
            position: sticky;
            top: 0;
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--vinho) 0%, var(--vinho-escuro) 100%);
            color: var(--branco);
            padding: 24px 18px;
            box-shadow: 6px 0 18px rgba(0, 0, 0, 0.12);
        }

        .logo-box {
            text-align: center;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .logo-box img {
            width: 110px;
            margin-bottom: 8px;
        }

        .logo-box h2 {
            font-size: 1.2rem;
            letter-spacing: 0.05em;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav a {
            display: block;
            padding: 12px 14px;
            border-radius: 10px;
            color: var(--branco);
            font-weight: 700;
            transition: 0.2s ease;
        }

        .nav a:hover,
        .nav a.active {
            background: rgba(255,255,255,0.12);
        }

        .logout-box {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .logout-box a {
            display: block;
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 12px 14px;
            text-align: center;
            font-weight: 700;
        }

        .content {
            flex: 1;
            padding: 32px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 26px;
            padding: 18px 20px;
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(114, 47, 55, 0.08);
            border-radius: 16px;
            box-shadow: var(--sombra);
        }

        .topbar h1 {
            font-size: 1.8rem;
            color: var(--vinho-escuro);
        }

        .user-badge {
            background: var(--vinho);
            color: var(--branco);
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 700;
        }

        .alerta {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            font-weight: 700;
        }

        .alerta.sucesso {
            background: rgba(45, 125, 70, 0.12);
            border: 1px solid rgba(45, 125, 70, 0.25);
            color: var(--verde);
        }

        .alerta.erro {
            background: rgba(179, 58, 58, 0.12);
            border: 1px solid rgba(179, 58, 58, 0.22);
            color: var(--vermelho);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .card {
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(114, 47, 55, 0.08);
            border-radius: 16px;
            padding: 22px 18px;
            box-shadow: var(--sombra);
        }

        .card .label {
            color: var(--texto-suave);
            font-size: 0.82rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .card .valor {
            font-size: 2rem;
            font-weight: 800;
            color: var(--vinho-escuro);
        }

        .card .sub {
            margin-top: 8px;
            color: var(--texto-suave);
            font-size: 0.9rem;
        }

        .panel {
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(114, 47, 55, 0.08);
            border-radius: 16px;
            box-shadow: var(--sombra);
            padding: 22px;
            margin-top: 24px;
        }

        .panel h3 {
            margin-bottom: 18px;
            color: var(--vinho-escuro);
            font-size: 1.25rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
        }

        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(114, 47, 55, 0.08);
            vertical-align: middle;
        }

        th {
            background: rgba(114, 47, 55, 0.04);
            color: var(--vinho-escuro);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            font-size: 0.95rem;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge.ativo {
            background: rgba(45, 125, 70, 0.12);
            color: var(--verde);
        }

        .badge.inativo {
            background: rgba(179, 58, 58, 0.12);
            color: var(--vermelho);
        }

        .btn {
            padding: 9px 13px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .btn.primary {
            background: var(--vinho);
            color: var(--branco);
        }

        .btn.secondary {
            background: rgba(114, 47, 55, 0.08);
            color: var(--vinho-escuro);
        }

        .btn.warning {
            background: rgba(207, 154, 42, 0.18);
            color: #7d5705;
        }

        .btn:hover {
            filter: brightness(0.98);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo label {
            font-weight: 700;
            color: var(--vinho-escuro);
            font-size: 0.9rem;
        }

        .campo input,
        .campo textarea {
            padding: 11px 12px;
            border: 1px solid rgba(114, 47, 55, 0.15);
            border-radius: 10px;
            font-size: 0.96rem;
            background: #fff;
        }

        .campo textarea {
            min-height: 90px;
            resize: vertical;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .seguranca-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .seguranca-item {
            border: 1px solid rgba(114, 47, 55, 0.08);
            border-radius: 12px;
            padding: 18px;
            background: rgba(114, 47, 55, 0.02);
        }

        .seguranca-item h4 {
            margin-bottom: 8px;
            color: var(--vinho-escuro);
        }

        .seguranca-item p {
            color: var(--texto-suave);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 900px) {
            .admin-layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }

            .content {
                padding: 20px 16px 36px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="logo-box">
                <img src="imagens/logo_miconte.png" alt="MiConte+">
                <h2>Painel Admin</h2>
            </div>

            <nav class="nav">
                <a href="#dashboard" class="active">Dashboard</a>
                <a href="#livros">Livros</a>
                <a href="#reservas">Reservas</a>
                <a href="#usuarios">Usu├írios internos</a>
                <a href="#configuracoes">Configura├º├Áes</a>
                <a href="#seguranca">Seguran├ºa</a>
            </nav>

            <div class="logout-box">
                <a href="logout.php">Sair</a>
            </div>
        </aside>

        <main class="content">
            <div class="topbar">
                <h1>Bem-vindo, <?php echo htmlspecialchars($adminNome); ?></h1>
                <div class="user-badge">Administrador</div>
            </div>

            <?php if ($mensagem !== ''): ?>
                <div class="alerta <?php echo $tipoMensagem === 'sucesso' ? 'sucesso' : 'erro'; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <section id="dashboard">
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="label">Livros cadastrados</div>
                        <div class="valor"><?php echo (int)$totalLivros; ?></div>
                        <div class="sub">Registros na biblioteca.</div>
                    </div>
                    <div class="card">
                        <div class="label">Sistema</div>
                        <div class="valor">Online</div>
                        <div class="sub">Plataforma dispon├¡vel para uso.</div>
                    </div>

                    <div class="card">
                        <div class="label">Reservas</div>
                        <div class="valor"><?php echo (int)$totalReservas; ?></div>
                        <div class="sub">Reservas registradas.</div>
                    </div>
                    <div class="card">
                        <div class="label">Acesso administrativo</div>
                        <div class="valor">Ativo</div>
                        <div class="sub">Conta principal liberada.</div>
                    </div>

                    <div class="card">
                        <div class="label">Prazo de empr├®stimo</div>
                        <div class="valor"><?php echo (int)$config['prazo_emprestimo']; ?> dias</div>
                        <div class="sub">Configura├º├úo atual do sistema.</div>
                    </div>

                    <div class="card">
                        <div class="label">Mensagem do sistema</div>
                        <div class="valor" style="font-size:1.1rem;"><?php echo htmlspecialchars($config['mensagem_sistema']); ?></div>
                        <div class="sub">Mensagem exibida aos usu├írios.</div>
                    </div>
                </div>
            </section>

            <section id="livros" class="panel">
                <h3>Gerenciamento de livros</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_admin'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="acao" value="cadastrar_livro">
                    <div class="form-grid">
                        <div class="campo"><label for="titulo">Título</label><input id="titulo" name="titulo" maxlength="255" required></div>
                        <div class="campo"><label for="autor">Autor</label><input id="autor" name="autor" maxlength="255" required></div>
                        <div class="campo"><label for="capa">Caminho da capa (opcional)</label><input id="capa" name="capa" maxlength="255"></div>
                        <div class="campo"><label for="quantidade">Quantidade</label><input id="quantidade" name="quantidade" type="number" min="0" max="100000" value="1" required></div>
                    </div>
                    <div class="campo" style="margin-top:16px;"><label for="sinopse">Sinopse</label><textarea id="sinopse" name="sinopse"></textarea></div>
                    <div class="actions"><button class="btn primary" type="submit">Cadastrar livro</button></div>
                </form>

                <h3 style="margin-top:28px;">Livros cadastrados</h3>
                <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>ID</th><th>Título</th><th>Autor</th><th>Quantidade</th><th>Editar</th><th>Excluir</th></tr></thead>
                    <tbody>
                    <?php if ($livros): foreach ($livros as $livro): ?>
                        <tr>
                            <td><?php echo (int)$livro['id']; ?></td>
                            <td><?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$livro['quantidade']; ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_admin'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="acao" value="editar_livro">
                                    <input type="hidden" name="id" value="<?php echo (int)$livro['id']; ?>">
                                    <input name="titulo" aria-label="Título" value="<?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" required>
                                    <input name="autor" aria-label="Autor" value="<?php echo htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" required>
                                    <input name="capa" aria-label="Capa" value="<?php echo htmlspecialchars($livro['capa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="255">
                                    <input name="quantidade" aria-label="Quantidade" type="number" min="0" max="100000" value="<?php echo (int)$livro['quantidade']; ?>" required>
                                    <textarea name="sinopse" aria-label="Sinopse"><?php echo htmlspecialchars($livro['sinopse'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <button class="btn secondary" type="submit">Salvar edição</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Confirma a exclusão deste livro?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_admin'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="acao" value="excluir_livro">
                                    <input type="hidden" name="id" value="<?php echo (int)$livro['id']; ?>">
                                    <button class="btn warning" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center;">Nenhum livro cadastrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </section>

            <section id="reservas" class="panel">
                <h3>Reservas</h3>
                <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Aluno</th><th>E-mail</th><th>Livro</th><th>Data</th><th>Horário</th><th>Criada em</th></tr></thead>
                    <tbody>
                    <?php if ($reservas): foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reserva['nome'] ?? 'Aluno', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($reserva['aluno_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($reserva['titulo'] ?? 'Livro', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($reserva['data'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($reserva['hora'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($reserva['criado_em'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center;">Nenhuma reserva encontrada.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </section>

            <section id="usuarios" class="panel">
                <h3>Usu├írios internos</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuariosInternos)): ?>
                            <?php foreach ($usuariosInternos as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($usuario['tipo'])); ?></td>
                                    <td><span class="badge">Não gerenciado</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:#666;">Nenhum usuário interno encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section id="configuracoes" class="panel">
                <h3>Configurações gerais</h3>
                <div class="alerta erro">
                    As configurações estão em modo de consulta. A tabela <code>configuracoes</code>
                    não existe no banco atual; por segurança, este painel não a cria nem altera o banco.
                    A ativação do salvamento exige uma migração autorizada.
                </div>
                <div class="form-grid">
                    <div class="campo">
                        <label>Prazo de empréstimo (dias)</label>
                        <input type="number" value="<?php echo (int)$config['prazo_emprestimo']; ?>" disabled>
                    </div>
                    <div class="campo">
                        <label>Dias de bloqueio</label>
                        <input type="number" value="<?php echo (int)$config['dias_bloqueio']; ?>" disabled>
                    </div>
                </div>
                <div class="campo" style="margin-top:20px;">
                    <label>Mensagem do sistema</label>
                    <textarea disabled><?php echo htmlspecialchars($config['mensagem_sistema'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </section>

            <section id="seguranca" class="panel">
                <h3>Seguran├ºa</h3>

                <div class="seguranca-list">
                    <div class="seguranca-item">
                        <h4>Contas internas</h4>
                        <p>Gerencie a ativa├º├úo ou bloqueio de contas administrativas e de bibliotec├írios.</p>
                    </div>

                    <div class="seguranca-item">
                        <h4>Acesso administrativo</h4>
                        <p>Restrinja a├º├Áes sens├¡veis ao n├¡vel do administrador do sistema.</p>
                    </div>

                    <div class="seguranca-item">
                        <h4>Manuten├º├úo</h4>
                        <p>Use esta ├írea para manter a plataforma est├ível e segura.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
