<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) header("Location: login.php");

$playerID = $_SESSION['PlayerID'];

// Puxar inventário e equipamentos
$invSQL = "SELECT ItemID, Nome, Tipo FROM Inventory WHERE PlayerID=?";
$invStmt = sqlsrv_query($conn, $invSQL, array($playerID));

$eqSQL = "SELECT Slot, ItemID FROM Equipment WHERE PlayerID=?";
$eqStmt = sqlsrv_query($conn, $eqSQL, array($playerID));

echo "<h2>Equipamentos</h2><ul>";
while($eq = sqlsrv_fetch_array($eqStmt, SQLSRV_FETCH_ASSOC)){
    echo "<li>Slot {$eq['Slot']}: Item {$eq['ItemID']}</li>";
}
echo "</ul>";

echo "<h3>Inventário</h3><ul>";
while($item = sqlsrv_fetch_array($invStmt, SQLSRV_FETCH_ASSOC)){
    echo "<li>{$item['Nome']} ({$item['Tipo']}) 
          <form method='post' style='display:inline'>
              <input type='hidden' name='equipar_item' value='{$item['ItemID']}'>
              <button>Equipar</button>
          </form>
          </li>";
}
echo "</ul>";

// Equipar item
if(isset($_POST['equipar_item'])){
    $itemID = intval($_POST['equipar_item']);
    // Puxar tipo do item
    $itemSQL = "SELECT Tipo FROM Inventory WHERE PlayerID=? AND ItemID=?";
    $itemStmt = sqlsrv_query($conn, $itemSQL, array($playerID, $itemID));
    $item = sqlsrv_fetch_array($itemStmt, SQLSRV_FETCH_ASSOC);
    $slot = $item['Tipo']; // assume Tipo = slot
    
    // Atualiza Equipment
    $updateSQL = "UPDATE Equipment SET ItemID=? WHERE PlayerID=? AND Slot=?";
    sqlsrv_query($conn, $updateSQL, array($itemID, $playerID, $slot));
    header("Location: equipar.php");
    exit;
}




<h3>Inventário</h3>
<ul id="inventario">
<?php
while($item = sqlsrv_fetch_array($invStmt, SQLSRV_FETCH_ASSOC)){
    echo "<li>{$item['Nome']} 
        <button onclick='equiparItem({$item['ItemID']})'>Equipar</button></li>";
}
?>
</ul>

<script>
function equiparItem(itemID){
    fetch('equip_item_ajax.php', {
        method: 'POST',
        body: new URLSearchParams({itemID: itemID})
    }).then(res => res.text())
      .then(data => {
        if(data=="ok") location.reload(); // recarrega só a seção ou toda página
    });
}
</script>
