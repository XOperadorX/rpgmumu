<?php
if (!isset($conn)) {
    include "db.php"; // Garante conexão
}

if (!isset($_SESSION)) {
    session_start();
}

$playerID = $_SESSION['PlayerID'] ?? null;
if (!$playerID) {
    die("⛔ Faça login primeiro.");
}

// ==========================
// Checa ban
// ==========================
$stmt = sqlsrv_query($conn, "SELECT Banido FROM Players WHERE PlayerID = ?", [$playerID]);
if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (!empty($row['Banido']) && $row['Banido'] == 1) {
        die("⛔ Você está banido e não pode acessar o jogo.");
    }
}

// ==========================
// Puxa personagem
// ==========================
$stmtChar = sqlsrv_query($conn, "SELECT TOP 1 CharID, Name FROM dbo.Characters WHERE PlayerID=?", [$playerID]);
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
$charID = $char['CharID'] ?? null;

// ==========================
// Puxa equipamentos do banco
// ==========================
$eqBanco = [];
if($charID){
    $stmtEq = sqlsrv_query($conn, "SELECT * FROM dbo.Equipamento WHERE CharID=?", [$charID]);
    $eqBanco = sqlsrv_fetch_array($stmtEq, SQLSRV_FETCH_ASSOC) ?: [];
}

// ==========================
// Equipamentos da sessão
// ==========================
$eqSessao = $_SESSION['equipamentos'] ?? [];

// ==========================
// Slots e cores
// ==========================
$slots = ['Arma','Escudo','Capacete','Armadura','Luva','Calca'];
$cores = [
    'comum' => 'gray',
    'incomum' => 'green',
    'raro' => 'blue',
    'epico' => 'purple',
    'lendario' => 'orange'
];
?>

<h3>Personagem: <?=htmlspecialchars($char['Name'] ?? 'Desconhecido')?></h3>
<div style="display:flex; flex-wrap: wrap; width:650px;">
<?php foreach($slots as $slot): 
    // Prioridade: sessão > banco
    if(isset($eqSessao[$slot])){
        $nome = $eqSessao[$slot]['nome'] ?? '-';
        $raridade = $eqSessao[$slot]['dados']['raridade'] ?? 'comum';
    } else {
        $nome = $eqBanco[$slot] ?? '-';
        $raridade = 'comum'; // Se não tem raridade no banco, default
    }
    $cor = $cores[strtolower($raridade)] ?? 'gray';
?>
    <div style="border:3px solid <?=$cor?>; width:100px; height:100px; margin:5px; text-align:center; line-height:100px;" 
         title="<?=$slot?>: <?=$nome?> (<?=ucfirst($raridade)?>)">
        <?=htmlspecialchars($nome)?>
    </div>
<?php endforeach; ?>
</div>

<?php
// Lista detalhada da sessão (opcional)
if(!empty($eqSessao)){
    echo "<h4>Equipamentos da sessão:</h4><ul>";
    foreach($eqSessao as $slot=>$eq){
        $nome = htmlspecialchars($eq['nome'] ?? 'Desconhecido');
        $raridade = $eq['dados']['raridade'] ?? 'comum';
        echo "<li><strong>$slot:</strong> $nome (".ucfirst($raridade).")</li>";
    }
    echo "</ul>";
}
?>
