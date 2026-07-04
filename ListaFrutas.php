<?php
session_start();
include "db.php";

// Verifica conexão
if(!$conn){
    die(json_encode(['error'=>'Erro na conexão com o banco de dados', 'details'=>sqlsrv_errors()]));
}

// Consulta frutas
$sql = "SELECT FrutaID, Nome, TempoCrescimento, PrecoSemente FROM Frutas";
$stmt = sqlsrv_query($conn, $sql);

// ⚠️ Adiciona este trecho para mostrar erros SQL detalhados:
if($stmt === false){
    die(print_r(sqlsrv_errors(), true)); // Mostra o erro real do SQL Server
}

// Armazena resultados
$frutas = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $frutas[] = [
        'FrutaID' => $row['FrutaID'],
        'Nome' => $row['Nome'],
        'TempoCrescimento' => (int)$row['TempoCrescimento'], // minutos
        'PrecoSemente' => (int)$row['PrecoSemente']
    ];
}

// Retorna JSON
header('Content-Type: application/json');
echo json_encode($frutas, JSON_UNESCAPED_UNICODE);
?>
