<?php
include 'miconn.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'aluno') {
    header("Location: index.php");
    exit;
}

$livro = null;
if (isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $stmt = $db->prepare("SELECT id, titulo, autor, quantidade FROM livros WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $livro = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Disponibilidade - <?php echo $livro['titulo']; ?></title>
    <link rel="stylesheet" href="css/aluno.css">
    <style>
        body {
            background-color: #f5f5dc;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header, footer {
            background-color: #800000;
            color: white;
            text-align: center;
            padding: 10px;
        }
        .disponibilidade-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .disponibilidade-card {
            background: #fff;
            border: 1px solid #ccc;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            width: 90%; /* largo e retangular */
            min-height: 450px;
            text-align: center;
            position: relative;
        }
        .disponibilidade-card h2 {
            color: #800000;
            margin-bottom: 10px;
        }
        .disponibilidade-card p {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .disponibilidade-card label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
        }
        .disponibilidade-card input, 
        .disponibilidade-card select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
        }
        .disponibilidade-card button {
            padding: 10px 20px;
            background: #800000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .disponibilidade-card button:disabled {
            background: gray;
            cursor: not-allowed;
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
        /* Estilo do modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            text-align: center;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.3);
        }
        .modal-content h3 {
            color: #800000;
            margin-bottom: 15px;
        }
        .modal-buttons {
            margin-top: 20px;
        }
        .modal-buttons button {
            margin: 0 10px;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .modal-buttons button:first-child {
            background: #800000;
            color: white;
        }
        .modal-buttons button:last-child {
            background: gray;
            color: white;
        }
        #modalSucesso .modal-content {
            background: #fff;
            margin: 15% auto;
            padding: 30px;
            border-radius: 8px;
            max-width: 400px;
            text-align: center;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.3);
        }
        #modalSucesso h3 {
            color: #800000;
            margin-bottom: 15px;
        }
        #modalSucesso p {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!-- Conteúdo central -->
    <div class="disponibilidade-container">
        <div class="disponibilidade-card">
            <a href="aluno.php" class="voltar">←</a>
            <h2><?php echo strtoupper($livro['titulo']); ?></h2>
            <p><?php echo $livro['quantidade'] > 0 ? $livro['quantidade'].' disponíveis' : 'Indisponível'; ?></p>

            <?php if ($livro['quantidade'] > 0): ?>
            <form id="formReserva">
                <input type="hidden" name="id" value="<?php echo $livro['id']; ?>">

                <label for="data">Selecione a data:</label>
                <input type="date" id="data" name="data" required min="<?php echo date('Y-m-d'); ?>">

                <label for="hora">Selecione o horário:</label>
                <select id="hora" name="hora" required>
                    <?php
                    $inicio = strtotime("09:20");
                    $fim = strtotime("16:30");
                    for ($hora = $inicio; $hora <= $fim; $hora += 20*60) {
                        echo "<option value='".date("H:i",$hora)."'>".date("H:i",$hora)."</option>";
                    }
                    ?>
                </select>

                <button type="button" onclick="abrirModal()">Confirmar</button>
            </form>
            <?php else: ?>
                <button disabled>Reserva indisponível</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rodapé -->
    <footer>
        &copy; <?php echo date("Y"); ?> Biblioteca Escolar - Todos os direitos reservados
    </footer>

    <!-- Modal de confirmação -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content">
            <h3>Confirmação de Reserva</h3>
            <p id="mensagem"></p>
            <div class="modal-buttons">
                <button onclick="confirmarEnvio()">Sim, confirmar</button>
                <button onclick="fecharModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal de sucesso -->
    <div id="modalSucesso" class="modal">
        <div class="modal-content">
            <h3>Reserva concluída!</h3>
            <div style="font-size:60px; color:green;">✔</div>
            <p>Seu processo foi finalizado com sucesso.</p>
            <div class="modal-buttons">
                <button onclick="fecharModalSucesso()">OK</button>
            </div>
        </div>
    </div>

<script>
function abrirModal() {
    const data = document.getElementById('data').value;
    const hora = document.getElementById('hora').value;

    // validação de finais de semana
    const diaSemana = new Date(data).getDay();
    if (diaSemana === 0 || diaSemana === 6) {
        alert("Selecione apenas dias úteis!");
        return false;
    }

    // mensagem personalizada
    document.getElementById('mensagem').innerText =
        "Você confirma a retirada do livro em " + data + " às " + hora + "?";

    // exibe modal
    document.getElementById('modalConfirmacao').style.display = "block";
}

function fecharModal() {
    document.getElementById('modalConfirmacao').style.display = "none";
}

function confirmarEnvio() {
    const form = document.getElementById("formReserva");
    const dados = new FormData(form);

    fetch("confirmar.php", {
        method: "POST",
        body: dados
    })
    .then(response => response.text())
    .then(data => {
        fecharModal();
        if (data === "OK") {
            document.getElementById("modalSucesso").style.display = "block";
        } else {
            alert("Erro ao salvar reserva!");
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
</body>
</html>
