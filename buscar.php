<?php
include 'miconn.php';

$q = $_GET['q'] ?? '';

$stmt = $db->prepare("SELECT * FROM livros WHERE titulo LIKE :q OR autor LIKE :q");
$stmt->execute([':q' => "%$q%"]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Resultados da busca</title>
</head>
<body>
  <h2>Resultados para "<?php echo htmlspecialchars($q); ?>"</h2>
  <?php if ($resultados): ?>
    <ul>
      <?php foreach ($resultados as $livro): ?>
        <li><?php echo $livro['titulo'] . " - " . $livro['autor']; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>Nenhum livro encontrado.</p>
  <?php endif; ?>
</body>
</html>
