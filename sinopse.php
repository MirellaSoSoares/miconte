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
    <div id="modalConfirmacao" class="confirmation-box hidden-box">
        <h3>Deseja confirmar a reserva?</h3>
        <div class="confirmation-actions">
          <button class="confirm-yes" onclick="confirmarReserva()">Sim</button>
          <button class="confirm-no" onclick="fecharModalConfirmacao()">Não</button>
        </div>
      </div>
    </div>

    <div id="modalSucesso" class="success-box hidden-box">
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
