<?php
session_start();
include 'miconn.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$adminNome = $_SESSION['usuario_nome'] ?? 'Administrador';
$mensagem = '';
$tipoMensagem = '';

$usuariosInternos = [];
$config = [
    'prazo_emprestimo' => 7,
    'dias_bloqueio' => 3,
    'mensagem_sistema' => 'Sistema em funcionamento.'
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'toggle_status') {
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);

        if ($usuarioId > 0 && $usuarioId !== (int)($_SESSION['usuario_id'] ?? 0)) {
            $stmt = $db->prepare("SELECT id, status FROM usuarios WHERE id = ? AND tipo IN ('admin', 'professor') LIMIT 1");
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $novoStatus = ($usuario['status'] ?? 'ativo') === 'ativo' ? 'inativo' : 'ativo';

                $update = $db->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
                $update->execute([$novoStatus, $usuarioId]);

                $mensagem = 'Status do usuário atualizado com sucesso.';
                $tipoMensagem = 'sucesso';
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_config') {
        $prazo = (int)($_POST['prazo_emprestimo'] ?? 7);
        $diasBloqueio = (int)($_POST['dias_bloqueio'] ?? 3);
        $mensagemSistema = trim((string)($_POST['mensagem_sistema'] ?? 'Sistema em funcionamento.'));

        if ($mensagemSistema === '') {
            $mensagemSistema = 'Sistema em funcionamento.';
        }

        try {
            $db->query('SELECT 1 FROM configuracoes LIMIT 1');
        } catch (Exception $e) {
            $db->exec(
                "CREATE TABLE IF NOT EXISTS configuracoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    prazo_emprestimo INT NOT NULL DEFAULT 7,
                    dias_bloqueio INT NOT NULL DEFAULT 3,
                    mensagem_sistema VARCHAR(255) NOT NULL DEFAULT 'Sistema em funcionamento.',
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )"
            );
        }

        $verifica = $db->query('SELECT id FROM configuracoes LIMIT 1')->fetch(PDO::FETCH_ASSOC);

        if ($verifica) {
            $stmt = $db->prepare(
                'UPDATE configuracoes SET prazo_emprestimo = ?, dias_bloqueio = ?, mensagem_sistema = ?, atualizado_em = NOW() WHERE id = ?'
            );
            $stmt->execute([$prazo, $diasBloqueio, $mensagemSistema, $verifica['id']]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO configuracoes (prazo_emprestimo, dias_bloqueio, mensagem_sistema) VALUES (?, ?, ?)'
            );
            $stmt->execute([$prazo, $diasBloqueio, $mensagemSistema]);
        }

        $config['prazo_emprestimo'] = $prazo;
        $config['dias_bloqueio'] = $diasBloqueio;
        $config['mensagem_sistema'] = $mensagemSistema;

        $mensagem = 'Configurações salvas com sucesso.';
        $tipoMensagem = 'sucesso';
    }

    try {
        $stmt = $db->query("SELECT id, nome, email, tipo, status FROM usuarios WHERE tipo IN ('admin', 'professor') ORDER BY nome ASC");
        $usuariosInternos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stmt = $db->query("SELECT id, nome, email, tipo FROM usuarios WHERE tipo IN ('admin', 'professor') ORDER BY nome ASC");
        $usuariosInternos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    try {
        $stmt = $db->query('SELECT prazo_emprestimo, dias_bloqueio, mensagem_sistema FROM configuracoes ORDER BY id DESC LIMIT 1');
        $configDb = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($configDb) {
            $config['prazo_emprestimo'] = (int)$configDb['prazo_emprestimo'];
            $config['dias_bloqueio'] = (int)$configDb['dias_bloqueio'];
            $config['mensagem_sistema'] = $configDb['mensagem_sistema'];
        }
    } catch (Exception $e) {
        $config = [
            'prazo_emprestimo' => 7,
            'dias_bloqueio' => 3,
            'mensagem_sistema' => 'Sistema em funcionamento.'
        ];
    }
} catch (Exception $e) {
    $mensagem = 'Ocorreu um erro ao processar a operação.';
    $tipoMensagem = 'erro';
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
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
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
                <a href="#usuarios">Usuários internos</a>
                <a href="#configuracoes">Configurações</a>
                <a href="#seguranca">Segurança</a>
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
                        <div class="label">Sistema</div>
                        <div class="valor">Online</div>
                        <div class="sub">Plataforma disponível para uso.</div>
                    </div>

                    <div class="card">
                        <div class="label">Acesso administrativo</div>
                        <div class="valor">Ativo</div>
                        <div class="sub">Conta principal liberada.</div>
                    </div>

                    <div class="card">
                        <div class="label">Prazo de empréstimo</div>
                        <div class="valor"><?php echo (int)$config['prazo_emprestimo']; ?> dias</div>
                        <div class="sub">Configuração atual do sistema.</div>
                    </div>

                    <div class="card">
                        <div class="label">Mensagem do sistema</div>
                        <div class="valor" style="font-size:1.1rem;"><?php echo htmlspecialchars($config['mensagem_sistema']); ?></div>
                        <div class="sub">Mensagem exibida aos usuários.</div>
                    </div>
                </div>
            </section>

            <section id="usuarios" class="panel">
                <h3>Usuários internos</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuariosInternos)): ?>
                            <?php foreach ($usuariosInternos as $usuario): ?>
                                <?php
                                    $status = $usuario['status'] ?? 'ativo';
                                    $statusTexto = $status === 'inativo' ? 'Inativo' : 'Ativo';
                                    $statusClass = $status === 'inativo' ? 'inativo' : 'ativo';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($usuario['tipo'])); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusTexto; ?></span></td>
                                    <td>
                                        <?php if ((int)($usuario['id'] ?? 0) !== (int)($_SESSION['usuario_id'] ?? 0)): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="acao" value="toggle_status">
                                                <input type="hidden" name="usuario_id" value="<?php echo (int)$usuario['id']; ?>">
                                                <button type="submit" class="btn <?php echo $status === 'ativo' ? 'warning' : 'secondary'; ?>">
                                                    <?php echo $status === 'ativo' ? 'Desativar' : 'Ativar'; ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#666; font-weight:700;">Você</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#666;">Nenhum usuário interno encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section id="configuracoes" class="panel">
                <h3>Configurações gerais</h3>

                <form method="POST">
                    <input type="hidden" name="acao" value="salvar_config">

                    <div class="form-grid">
                        <div class="campo">
                            <label for="prazo_emprestimo">Prazo de empréstimo (dias)</label>
                            <input type="number" id="prazo_emprestimo" name="prazo_emprestimo" min="1" max="30" value="<?php echo (int)$config['prazo_emprestimo']; ?>">
                        </div>

                        <div class="campo">
                            <label for="dias_bloqueio">Dias de bloqueio</label>
                            <input type="number" id="dias_bloqueio" name="dias_bloqueio" min="1" max="30" value="<?php echo (int)$config['dias_bloqueio']; ?>">
                        </div>
                    </div>

                    <div class="campo" style="margin-top:20px;">
                        <label for="mensagem_sistema">Mensagem do sistema</label>
                        <textarea id="mensagem_sistema" name="mensagem_sistema"><?php echo htmlspecialchars($config['mensagem_sistema']); ?></textarea>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn primary">Salvar configurações</button>
                    </div>
                </form>
            </section>

            <section id="seguranca" class="panel">
                <h3>Segurança</h3>

                <div class="seguranca-list">
                    <div class="seguranca-item">
                        <h4>Contas internas</h4>
                        <p>Gerencie a ativação ou bloqueio de contas administrativas e de bibliotecários.</p>
                    </div>

                    <div class="seguranca-item">
                        <h4>Acesso administrativo</h4>
                        <p>Restrinja ações sensíveis ao nível do administrador do sistema.</p>
                    </div>

                    <div class="seguranca-item">
                        <h4>Manutenção</h4>
                        <p>Use esta área para manter a plataforma estável e segura.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
