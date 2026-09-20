<?php
include 'miconn.php';
session_start();

// Verifica login
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'aluno') {
    header("Location: index.php");
    exit;
}

$tabAtual = $_POST['tab'] ?? $_GET['tab'] ?? 'home';

// Pega o id enviado pelo botão ou pela URL
$livro = null;
$livroId = $_POST['id'] ?? $_GET['id'] ?? null;
if ($livroId !== null) {
    $id = (int) $livroId;
    $stmt = $db->prepare("SELECT id, titulo, autor, capa, sinopse, quantidade FROM livros WHERE id = :id");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
            background: #746f5c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .sinopse-card button:hover {
            background: #8b836e;
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
        .modal {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            width: min(420px, 90vw);
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: -2px 0 12px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            border-left: 1px solid rgba(0, 0, 0, 0.08);
        }
        .modal-content {
            padding: 24px;
        }
        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: #333;
        }
        .modal-content h3 {
            margin-top: 8px;
            color: #2d2d2d;
            font-size: 1.4rem;
        }
        .disponibilidade-mensagem {
            display: none;
            margin: 10px 0 12px;
            font-weight: 700;
            color: #b00020;
            text-align: left;
            background: #fff1f3;
            border: 1px solid rgba(176, 0, 32, 0.2);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .disponibilidade-estoque {
            display: none;
            margin: 10px 0 12px;
            font-weight: 700;
            color: #1e7e34;
            text-align: left;
            background: #edf9f0;
            border: 1px solid rgba(30, 126, 52, 0.2);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .modal-content input,
        .modal-content select {
            width: 100%;
            margin: 12px 0;
            padding: 12px 10px;
            border-radius: 8px;
            border: 1px solid #d9d9d9;
            box-sizing: border-box;
        }
        .modal-content button {
            margin-top: 10px;
            width: 100%;
            border-radius: 8px;
        }

        .confirmation-box,
        .success-box {
            /* Faz o modal flutuar no centro exato da tela por conta própria */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            
            /* Configurações visuais que você já tinha */
            width: min(440px, calc(100% - 32px));
            background: #ffffff;
            border-radius: 12px;
            padding: 28px 22px 24px;
            box-shadow: 0 14px 36px rgba(0, 0, 0, 0.22);
            text-align: center;
            border: 1px solid rgba(0,0,0,0.08);
            
            /* Garante que ele fique por cima de tudo */
            z-index: 3000;
        }

        .confirmation-box h3,
        .success-box h3 {
            margin: 0 0 20px;
            color: #2d2d2d;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .confirmation-actions,
        .success-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 18px;
        }

        .confirmation-box button,
        .success-box button {
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 18px;
            line-height: 1.2;
        }

        .confirm-yes {
            background: #722f37;
            color: #fff;
        }

        .confirm-no {
            background: #d9d9d9;
            color: #333;
        }

        .success-icon {
            font-size: 52px;
            color: #2e8b57;
            margin: 8px 0 12px;
            line-height: 1;
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
                <a href="aluno.php?tab=<?php echo urlencode($tabAtual); ?>" class="voltar">←</a>
                <img src="imagens/<?php echo $livro['capa']; ?>" alt="Capa do livro">
                <h2><?php echo strtoupper($livro['titulo']); ?></h2>
                <div class="autor"><?php echo $livro['autor']; ?></div>
                <p><?php echo nl2br($livro['sinopse']); ?></p>
                <button type="button" onclick="abrirCalendario(<?php echo $livro['id']; ?>)">Verificar disponibilidade</button>
            </div>
        <?php else: ?>
            <p>Livro não encontrado.</p>
        <?php endif; ?>
    </div>

    <div id="calendarioModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="fecharCalendario()">&times;</span>
        <h3>Selecione a data e o horário</h3>

        <div id="disponibilidadeMensagem" class="disponibilidade-mensagem" aria-live="polite"></div>
        <div id="disponibilidadeEstoque" class="disponibilidade-estoque" aria-live="polite"></div>

        <input type="date" id="data" name="data" required>

        <select id="hora" name="hora" required>
          <?php
          $inicio = strtotime("09:20");
          $fim = strtotime("16:30");
          for ($hora = $inicio; $hora <= $fim; $hora += 20*60) {
              echo "<option value='".date("H:i",$hora)."'>".date("H:i",$hora)."</option>";
          }
          ?>
        </select>

        <button type="button" onclick="abrirModalConfirmacao()">Confirmar reserva</button>
      </div>
    </div>
    <div id="modalConfirmacao" class="confirmation-box" style="display:none;">
        <h3>Deseja confirmar a reserva?</h3>
        <div class="confirmation-actions">
          <button class="confirm-yes" onclick="confirmarReserva()">Sim</button>
          <button class="confirm-no" onclick="fecharModalConfirmacao()">Não</button>
        </div>
      </div>
    </div>

    <div id="modalSucesso" class="success-box" style="display:none;">
        <h3>Reserva concluída!</h3>
        <div class="success-icon">✔</div>
        <p>Seu processo foi finalizado com sucesso.</p>
        <div class="success-actions">
          <button class="confirm-yes" onclick="fecharModalSucesso()">OK</button>
        </div>
      </div>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Biblioteca Escolar - Todos os direitos reservados
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        window.livroSelecionado = <?php echo (int) ($livro['id'] ?? 0); ?>;
        const quantidadeDisponivel = Number(<?php echo (int) ($livro['quantidade'] ?? 0); ?>);

        let dataPicker = null;

        function inicializarCalendarioSinopse() {
            const inputData = document.getElementById('data');
            if (!inputData) return null;

            if (dataPicker) return dataPicker;

            dataPicker = flatpickr(inputData, {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                disable: [
                    function(date) {
                        return (date.getDay() === 0 || date.getDay() === 6);
                    }
                ],
                locale: 'pt'
            });

            return dataPicker;
        }

        function atualizarStatusDisponibilidade() {
            const mensagem = document.getElementById('disponibilidadeMensagem');
            const estoque = document.getElementById('disponibilidadeEstoque');
            const inputData = document.getElementById('data');
            const inputHora = document.getElementById('hora');
            const confirmarBtn = document.querySelector('#calendarioModal button');

            if (quantidadeDisponivel <= 0) {
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
            estoque.textContent = 'Disponíveis: ' + quantidadeDisponivel + ' exemplar(es)';
            estoque.style.display = 'block';
            estoque.style.color = '#1e7e34';
            if (inputData) inputData.disabled = false;
            if (inputHora) inputHora.disabled = false;
            if (confirmarBtn) confirmarBtn.disabled = false;
        }

        window.onload = function () {
            inicializarCalendarioSinopse();
            atualizarStatusDisponibilidade();
        };

        function abrirCalendario(livroId) {
            window.livroSelecionado = livroId;
            document.getElementById('calendarioModal').style.display = 'block';

            const picker = inicializarCalendarioSinopse();
            setTimeout(() => {
                if (picker && typeof picker.open === 'function') {
                    picker.open();
                }
            }, 60);
            atualizarStatusDisponibilidade();
        }

        function fecharCalendario() {
            document.getElementById('calendarioModal').style.display = 'none';
        }

        function abrirModalConfirmacao() {
            const data = document.getElementById('data').value;
            const hora = document.getElementById('hora').value;

            if (quantidadeDisponivel <= 0) {
                atualizarStatusDisponibilidade();
                return;
            }

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
                fecharCalendario();
                fecharModalConfirmacao();

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
</html>
