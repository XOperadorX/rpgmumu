<?php
if (!isset($conn)) include "db.php";
if (!isset($_SESSION)) session_start();

$playerID = $_SESSION['PlayerID'] ?? null;
if (!$playerID) { 
    echo json_encode([]); 
    exit; 
}

// Busca banimento e recarga
$stmt = sqlsrv_query($conn, "SELECT IsBanned, RecargaExpiraEm FROM Players WHERE PlayerID = ?", [$playerID]);
$rowP = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Verifica banimento
if (!empty($rowP['IsBanned']) && $rowP['IsBanned'] == 1) {
    die(json_encode(['error'=>"⛔ Você está banido e não pode acessar o jogo."]));
}

// Verifica recarga
$recargaAtiva = false;
if ($rowP['RecargaExpiraEm'] !== null) {
    $agora = new DateTime();
    $expira = new DateTime($rowP['RecargaExpiraEm']);
    if ($agora < $expira) $recargaAtiva = true;
}

// Função para cores das barras
function getBarColor($percent) {
    if ($percent >= 70) return 'green';
    if ($percent >= 30) return 'orange';
    return 'red';
}

// Busca personagens
$sql = "SELECT TOP 1000 CharID, Name, Class, Level, Exp, HP, MaxHP, Mana, MaxMana, Power, MaxPower
        FROM Characters WHERE PlayerID = ?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);

if ($stmt === false) {
    echo json_encode(['error' => 'Erro ao consultar personagens.']);
    exit;
}

$chars = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if ($recargaAtiva) {
        $row['HP'] = $row['MaxHP'];
        $row['Mana'] = $row['MaxMana'];
        $row['Power'] = $row['MaxPower'];

        // Atualiza no banco para manter restaurado
        $sqlUp = "UPDATE Characters SET HP = ?, Mana = ?, Power = ? WHERE CharID = ?";
        sqlsrv_query($conn, $sqlUp, [$row['MaxHP'], $row['MaxMana'], $row['MaxPower'], $row['CharID']]);
    }

    // Calcula porcentagens
    $hpPercent    = $row['MaxHP'] > 0 ? round(($row['HP'] / $row['MaxHP']) * 100) : 0;
    $manaPercent  = $row['MaxMana'] > 0 ? round(($row['Mana'] / $row['MaxMana']) * 100) : 0;
    $powerPercent = $row['MaxPower'] > 0 ? round(($row['Power'] / $row['MaxPower']) * 100) : 0;

    $chars[] = [
        'CharID'      => $row['CharID'],
        'Name'        => $row['Name'],
        'Class'       => $row['Class'],
        'Level'       => $row['Level'],
        'Exp'         => $row['Exp'],
        'HP'          => $row['HP'],
        'MaxHP'       => $row['MaxHP'],
        'HPPercent'   => $hpPercent,
        'HPColor'     => getBarColor($hpPercent),
        'Mana'        => $row['Mana'],
        'MaxMana'     => $row['MaxMana'],
        'ManaPercent' => $manaPercent,
        'ManaColor'   => getBarColor($manaPercent),
        'Power'       => $row['Power'],
        'MaxPower'    => $row['MaxPower'],
        'PowerPercent'=> $powerPercent,
        'PowerColor'  => getBarColor($powerPercent),
        'RecargaAtiva'=> $recargaAtiva
    ];
}

echo json_encode($chars);
?>
