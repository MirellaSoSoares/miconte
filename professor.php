<?php
include 'miconn.php';

$sql = "SELECT r.aluno_email, u.nome, l.titulo, r.data, r.hora, r.criado_em
        FROM reservas r
        JOIN usuarios u ON r.aluno_id = u.id
        JOIN livros l ON r.livro_id = l.id
        ORDER BY r.data, r.hora";

$stmt = $db->query($sql);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Aluno</th><th>Email</th><th>Livro</th><th>Data</th><th>Hora</th><th>Registrado em</th></tr>";

foreach ($reservas as $r) {
    echo "<tr>
            <td>{$r['nome']}</td>
            <td>{$r['aluno_email']}</td>
            <td>{$r['titulo']}</td>
            <td>{$r['data']}</td>
            <td>{$r['hora']}</td>
            <td>{$r['criado_em']}</td>
          </tr>";
}
echo "</table>";
?>

?>