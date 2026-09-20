<?php 
include 'header.php'; 
include 'miconn.php';
?>

<div class="container">
    <form method="POST" action="cadastro.php">
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="email" name="email" placeholder="E-mail institucional" required>
        <input type="password" name="senha" placeholder="Senha" required>
        
        <!-- Campo único para escolher tipo -->
        <select name="tipo">
            <option value="aluno">Aluno</option>
            <option value="professor">Professor</option>
        </select>
        
        <button type="submit">Cadastrar</button>
    </form>
</div>

<?php include 'footer.php'; ?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo'];

    // Validação do domínio institucional
    if (!str_ends_with($email, "@aluno.sp.gov.br")) {
        die("<p class='mensagem-erro'>É necessário usar o e-mail institucional para login</p>");
    }

    try {
        $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");

        $maxAttempts = 6;
        $attempt = 0;
        while (true) {
            try {
                $stmt->execute([$nome, $email, $senha, $tipo]);
                echo "<p class='mensagem-sucesso'>Usuário cadastrado com sucesso!</p>";
                break;
            } catch (PDOException $e) {
                $attempt++;
                $logLine = date('c') . " | cadastro.php | attempt={$attempt} | " . $e->getMessage() . PHP_EOL;
                @file_put_contents(__DIR__ . '/db_errors.log', $logLine, FILE_APPEND);
                if ($attempt >= $maxAttempts || stripos($e->getMessage(), 'database is locked') === false) {
                    echo "<p class='mensagem-erro'>Erro ao cadastrar: " . htmlspecialchars($e->getMessage()) . "</p>";
                    break;
                }
                usleep(200000 * $attempt);
            }
        }
    } catch (Exception $e) {
        $logLine = date('c') . " | cadastro.php | exception | " . $e->getMessage() . PHP_EOL;
        @file_put_contents(__DIR__ . '/db_errors.log', $logLine, FILE_APPEND);
        echo "<p class='mensagem-erro'>Erro ao cadastrar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
