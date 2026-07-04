<?php
if (!isset($conn)) {
    include "db.php"; // Garante que a conexão está disponível
}

if (!isset($_SESSION)) {
    session_start();
}

$playerID = $_SESSION['PlayerID'] ?? null;

if ($playerID) {
    $stmt = sqlsrv_query($conn, "SELECT Banido FROM Players WHERE PlayerID = ?", [$playerID]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (!empty($row['Banido']) && $row['Banido'] == 1) {
            die("⛔ Você está banido e não pode acessar o jogo.");
        }
    }
}


$playerID = $_SESSION['PlayerID'];
$itemID   = $_POST['ItemID'] ?? null;

if(!$itemID){
    die("Item inválido.");
}

// 🔹 Exemplo: remover item (ajuste para sua tabela real de inventário)
$sqlDelete = "DELETE FROM Inventory WHERE ItemID=? AND PlayerID=?";
$params = array($itemID, $playerID);
$stmt = sqlsrv_query($conn, $sqlDelete, $params);

if($stmt === false){
    die("Erro ao refinar o item.");
}

// 🔹 Dar moedas
$sqlMoeda = "UPDATE Players SET MoedaMumu = MoedaMumu + 10 WHERE PlayerID=?";
$stmt2 = sqlsrv_query($conn, $sqlMoeda, [$playerID]);

if($stmt2 === false){
    die("Erro ao adicionar moedas.");
}

// 🔹 Resposta para o AJAX
echo "✅ Item refinado! Você ganhou +10 Moedas Mumu!";


<script>
function refinarItem(itemID) {
    if(confirm("Deseja realmente refinar/excluir este item?")) {
        $.post('refinar_item_ajax.php', { ItemID: itemID }, function(res){
            alert(res);        // mostra mensagem do PHP
            loadDungeonLog();  // recarrega os logs ou inventário
        });
    }
}
</script>



<script>
function loadMoedas() {
    $.get('get_moedas.php', function(res){
        document.getElementById("moedas").innerText = res;
    });
}

function refinarItem(itemID) {
    if(confirm("Deseja realmente refinar/excluir este item?")) {
        $.post('refinar_item_ajax.php', { ItemID: itemID }, function(res){
            alert(res);        // mostra mensagem do PHP
            loadMoedas();      // 🔹 Atualiza moedas
            loadDungeonLog();  // se quiser recarregar lista/inventário
        });
    }
}

// 🔹 Carregar moedas assim que abrir a página
window.onload = loadMoedas;
</script>



echo "<table border='1' cellspacing='0' cellpadding='8' style='margin:20px auto; border-collapse:collapse; text-align:center;'>";
echo "<tr style='background:#333; color:#fff;'>
        <th>Item</th>
        <th>Tipo</th>
        <th>Ação</th>
      </tr>";

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['ItemNome']}</td>
            <td>{$row['ItemTipo']}</td>
            <td><button onclick='refinarItem({$row['ItemID']})'>⚒️ Refiner</button></td>
          </tr>";
}
echo "</table>";


