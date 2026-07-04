<?php
include "db.php";

// Verifica conexão
if(!$conn){
    die("❌ Erro de conexão com o banco de dados.<br>" . print_r(sqlsrv_errors(), true));
}

// Nome da tabela
$tabela = "Frutas";

// Verifica se a tabela existe
$checkTable = sqlsrv_query($conn, "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?", [$tabela]);
if(!$checkTable){
    die("Erro ao verificar tabela.<br>" . print_r(sqlsrv_errors(), true));
}

if(!sqlsrv_fetch_array($checkTable)){
    echo "⚙️ Criando tabela $tabela...<br>";
    $create = "
        CREATE TABLE $tabela (
            FrutaID INT IDENTITY PRIMARY KEY,
            Nome NVARCHAR(50) NOT NULL,
            TempoCrescimento INT DEFAULT 5,
            PrecoSemente INT DEFAULT 10,
            PrecoCompra INT DEFAULT 15,
            PrecoVenda INT DEFAULT 20
        )
    ";
    $ok = sqlsrv_query($conn, $create);
    if(!$ok){
        die("Erro ao criar tabela.<br>" . print_r(sqlsrv_errors(), true));
    }
    echo "✅ Tabela $tabela criada com sucesso!<br>";
} else {
    echo "🔍 Tabela $tabela já existe.<br>";

    // Verifica colunas existentes
    $colunasExistentes = [];
    $cols = sqlsrv_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?", [$tabela]);
    while($c = sqlsrv_fetch_array($cols, SQLSRV_FETCH_ASSOC)){
        $colunasExistentes[] = $c['COLUMN_NAME'];
    }

    // Colunas que devem existir
    $colunasNecessarias = [
        'Nome NVARCHAR(50) NOT NULL',
        'TempoCrescimento INT DEFAULT 5',
        'PrecoSemente INT DEFAULT 10',
        'PrecoCompra INT DEFAULT 15',
        'PrecoVenda INT DEFAULT 20'
    ];

    foreach($colunasNecessarias as $definicao){
        $nome = explode(" ", $definicao)[0];
        if(!in_array($nome, $colunasExistentes)){
            echo "➕ Adicionando coluna: $nome...<br>";
            $alter = "ALTER TABLE $tabela ADD $definicao";
            $ok = sqlsrv_query($conn, $alter);
            if(!$ok){
                echo "❌ Erro ao adicionar coluna $nome.<br>" . print_r(sqlsrv_errors(), true);
            } else {
                echo "✅ Coluna $nome adicionada com sucesso!<br>";
            }
        }
    }
}

echo "<br>✅ Verificação concluída!";
?>
