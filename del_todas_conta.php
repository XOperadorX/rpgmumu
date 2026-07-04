<?php
session_start();
include "db.php";
include "check_ban.php"; // protege a página


try {
    // Inicia transação
    sqlsrv_begin_transaction($conn);

    // Lista de tabelas e colunas IDENTITY
    $tables = [
        'Items' => 'id',
        'DungeonLog' => 'id',
        'Characters' => 'id',
        'Players' => 'PlayerID'
    ];

    foreach($tables as $table => $idCol){
        // Deleta todos os registros
        $sqlDelete = "DELETE FROM $table";
        sqlsrv_query($conn, $sqlDelete);

        // Reseta IDENTITY
        $sqlReset = "DBCC CHECKIDENT ('$table', RESEED, 0)";
        $stmt = sqlsrv_query($conn, $sqlReset);
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);

        // Ignora avisos 01000 do DBCC
        if($stmt === false && $errors){
            foreach($errors as $err){
                if($err['SQLSTATE'] !== '01000'){
                    throw new Exception("Erro ao resetar IDENTITY da tabela $table: " . print_r($errors, true));
                }
            }
        }
    }

    // Confirma transação
    sqlsrv_commit($conn);

    echo "✅ Todas as tabelas foram limpas e IDs resetados com sucesso!";

} catch(Exception $e){
    sqlsrv_rollback($conn);
    die("❌ Erro: " . $e->getMessage());
}
?>
