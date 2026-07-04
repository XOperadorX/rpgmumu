<?php
include "db.php";

// Verifica conexão
if(!$conn){
    die("❌ Erro de conexão com o banco de dados.<br>" . print_r(sqlsrv_errors(), true));
}

// Lista de frutas fáceis de plantar
$frutas = [
    ['Morango', 2, 10, 15, 20],
    ['Tomate', 3, 12, 18, 25],
    ['Banana', 5, 15, 25, 35],
    ['Manga', 8, 20, 30, 45],
    ['Mamão', 6, 18, 28, 40],
    ['Abacaxi', 10, 25, 35, 55],
    ['Maracujá', 7, 22, 32, 48],
    ['Laranja', 9, 24, 36, 50],
    ['Uva', 4, 14, 22, 30],
    ['Melancia', 12, 28, 40, 60]
];

// Verifica se já existem frutas
$check = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM Frutas");
$row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
if($row['total'] > 0){
    echo "⚠️ Já existem frutas cadastradas na tabela.<br>";
    echo "Para evitar duplicação, o script não inseriu novamente.<br>";
    echo "Se quiser reinserir, esvazie a tabela primeiro com: TRUNCATE TABLE Frutas;<br>";
    exit;
}

// Inserção das frutas
$sql = "INSERT INTO Frutas (Nome, TempoCrescimento, PrecoSemente, PrecoCompra, PrecoVenda) VALUES (?, ?, ?, ?, ?)";
foreach($frutas as $f){
    $stmt = sqlsrv_query($conn, $sql, $f);
    if($stmt){
        echo "✅ Fruta inserida: {$f[0]}<br>";
    } else {
        echo "❌ Erro ao inserir {$f[0]}<br>";
        print_r(sqlsrv_errors());
    }
}

echo "<br>🍇 Inserção concluída com sucesso!";
?>
