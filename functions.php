<?php
session_start();
include "db.php";

/**
 * Executa uma query de forma segura e retorna resultados.
 */
function safeQuery($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo "<pre>❌ Erro na query:\n$sql\n";
        print_r(sqlsrv_errors());
        echo "</pre>";
        return [];
    }
    $result = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $result[] = $row;
    }
    return $result;
}

/**
 * Registra uma ação no histórico da fazenda.
 * @param resource $conn Conexão SQL Server
 * @param int $playerID ID do jogador
 * @param string $acao Nome da ação ("Plantar", "Colher", "Comprar Semente", etc)
 * @param string $nomeFruta Nome da fruta ou semente
 * @param int $quantidade Quantidade da ação
 */
function registrarHistorico($conn, $playerID, $acao, $nomeFruta, $quantidade){
    $sql = "INSERT INTO dbo.HistoricoFazenda (PlayerID, Acao, NomeFruta, Quantidade, DataRegistro) 
            VALUES (?, ?, ?, ?, GETDATE())";
    sqlsrv_query($conn, $sql, [$playerID, $acao, $nomeFruta, $quantidade]);
}

/**
 * Retorna o ID do jogador logado
 */
function getPlayerID() {
    if (!isset($_SESSION['PlayerID'])) {
        die("⛔ Faça login primeiro.");
    }
    return $_SESSION['PlayerID'];
}

/**
 * Formata moeda brasileira
 */
function formatMoney($valor) {
    return number_format($valor, 2, ',', '.');
}
