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
            width: 38%;
            height: 100%;
            background: #fff;
            box-shadow: -2px 0 8px rgba(0,0,0,0.25);
            z-index: 1000;
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
        }
        .modal-content input,
        .modal-content select {
            width: 100%;
            margin: 12px 0;
            padding: 10px;
        }
        .modal-content button {
            margin-top: 10px;
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
                <a href="aluno.php" class="voltar">←</a>
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
        <h3>Selecione a data e horário</h3>

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

        <button type="button" onclick="abrirModalConfirmacao()">Confirmar</button>
      </div>
    </div>
a
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

        window.onload = function () {
            inicializarCalendarioSinopse();
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
