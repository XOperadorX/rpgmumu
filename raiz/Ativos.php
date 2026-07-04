<?php
// Inicia a sessão (caso precise de autenticação mais tarde)
session_start();

// Inclui o arquivo de conexão (ajuste o nome se necessário)
include "db.php"; // este arquivo deve conter a variável $conn

// Verifica se a conexão foi estabelecida
if (!$conn) {
    die("❌ Falha na conexão com o banco de dados.");
}

// Monta o SQL
$sql = "SELECT TOP 1000 AtivoID, Nome, PrecoAtual, PrecoMedio, Preco, UltimaVariacao
        FROM Ativos
        ORDER BY Nome ASC";

// Executa a consulta
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Exibe os resultados
echo "<h2>📈 Lista de Ativos</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>ID</th><th>Nome</th><th>Preço Atual</th><th>Preço Médio</th><th>Preço</th><th>Última Variação</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['AtivoID']}</td>";
    echo "<td>{$row['Nome']}</td>";
    echo "<td>{$row['PrecoAtual']}</td>";
    echo "<td>{$row['PrecoMedio']}</td>";
    echo "<td>{$row['Preco']}</td>";
    echo "<td>{$row['UltimaVariacao']}</td>";
    echo "</tr>";
}

echo "</table>";

// Libera os recursos
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
