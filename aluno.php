<?php
include 'miconn.php';
session_start();

// Verifica se o usuário está logado e é aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'aluno') {
    header("Location: index.php");
    exit;
}

// Puxa todos os livros
$stmt = $db->query("SELECT * FROM livros");
$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/aluno.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            width: 40%;
            height: 100%;
            background-color: #fff;
            box-shadow: -2px 0 8px rgba(0,0,0,0.3);
            z-index: 1000;
        }
        .modal-content {
            padding: 20px;
        }
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Banner topo -->
    <div class="banner-topo">
        <h2>Conheça alguns dos nossos bons livros disponíveis abaixo! </h2>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Meu Perfil</h3>
        <ul>
            <li><span class="icon">🏠</span> <a href="#">Home</a></li>
            <li><span class="icon">📚</span> <a href="#">Livros alocados</a></li>
            <li><span class="icon">💡</span> <a href="#">Favoritos</a></li>
            <li><span class="icon">⚙️</span> <a href="#">Configurações</a></li>
            <li><span class="icon">🚪</span> <a href="#">Sair</a></li>
        </ul>
    </div>

    <!-- Barra de busca -->
    <div class="search-container">
        <form method="GET" action="buscar.php" class="search-box">
            <input type="text" name="q" placeholder="Digite título ou autor..." required>
            <button type="submit" class="search-btn">
                <span class="lupa">&#128269;</span>
            </button>
        </form>
    </div>

    <!-- Conteúdo principal -->
    <div class="conteudo">
        <div class="livro-container">
            <?php foreach ($livros as $livro): ?>
                <div class="livro">
                    <img src="imagens/<?php echo $livro['capa']; ?>" alt="Capa do livro">
                    <h3><?php echo $livro['titulo']; ?></h3>
                    <p><?php echo $livro['autor']; ?></p>

                    <form method="POST" action="sinopse.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $livro['id']; ?>">
                        <button type="submit">Sinopse</button>
                    </form>

                    <button type="button" 
                        onclick="abrirCalendario(<?php echo $livro['id']; ?>)" 
                        <?php if ($livro['quantidade'] <= 0) echo "disabled"; ?>>
                        Verificar disponibilidade
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal lateral -->
    <div id="calendarioModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="fecharCalendario()">&times;</span>
        <h3>Selecione a data e horário</h3>

        <!-- Campo de data com Flatpickr -->
        <input type="text" id="data" name="data" required>

        <!-- Campo de horário -->
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

    <!-- Modal de confirmação -->
<div id="modalConfirmacao">
  <h3>Deseja confirmar a reserva?</h3>
  <button onclick="confirmarReserva()">Sim</button>
  <button onclick="fecharModalConfirmacao()">Não</button>
</div>

<!-- Modal de sucesso -->
<div id="modalSucesso">
  <h3>Reserva concluída!</h3>
  <div style="font-size:50px; color:green;">✔</div>
  <p>Seu processo foi finalizado com sucesso.</p>
  <button onclick="fecharModalSucesso()">OK</button>
</div>

<script>
window.onload = function() {
  flatpickr("#data", {
    dateFormat: "Y-m-d",
    minDate: "today",
    disable: [
      function(date) {
        return (date.getDay() === 0 || date.getDay() === 6);
      }
    ],
    locale: "pt"
  });
};

function abrirCalendario(livroId) {
  // guarda o ID do livro selecionado para usar no backend
  window.livroSelecionado = livroId;
  document.getElementById("calendarioModal").style.display = "block";
}

function fecharCalendario() {
  document.getElementById("calendarioModal").style.display = "none";
}

function abrirModalConfirmacao() {
  const data = document.getElementById("data").value;
  const hora = document.getElementById("hora").value;

  // validações
  if (!data) {
    alert("Por favor, selecione uma data antes de confirmar!");
    return;
  }
  if (!hora) {
    alert("Por favor, selecione um horário antes de confirmar!");
    return;
  }

  // abre modal de confirmação
  document.getElementById("modalConfirmacao").style.display = "block";
}

function fecharModalConfirmacao() {
  document.getElementById("modalConfirmacao").style.display = "none";
}

function confirmarReserva() {
  const data = document.getElementById("data").value;
  const hora = document.getElementById("hora").value;
  const livroId = window.livroSelecionado;

   // mostra loading
  const btn = document.querySelector("#calendarioModal button");
  btn.disabled = true;
  btn.textContent = "Processando...";
  
  // envia para o backend
  fetch("confirmar.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "livro_id=" + livroId + "&data=" + data + "&hora=" + hora
  })
  .then(response => response.text())
  .then(result => {
    fecharModalConfirmacao();
    if (result === "OK") {
      document.getElementById("modalSucesso").style.display = "block";
    } else {
      alert("Erro ao salvar reserva: " + result);
    }
  })
  .catch(error => {
    alert("Erro de conexão!");
  });
}

function fecharModalSucesso() {
  document.getElementById("modalSucesso").style.display = "none";
}

</script>

    <!-- Rodapé -->
    <footer>
        &copy; <?php echo date("Y"); ?> Biblioteca Escolar - Todos os direitos reservados
    </footer>
</body>
</html>
