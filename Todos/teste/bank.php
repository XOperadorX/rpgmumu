<?php
session_start();
include 'db.php';
if(!isset($_SESSION['PlayerID'])) header("Location: login.php");

$playerID = $_SESSION['PlayerID'];

// Puxar saldo
$sql = "SELECT MoedaMumu, Poupanca FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, array($playerID));
$player = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Transferência simples entre corrente e poupança
if(isset($_POST['deposit'])){
    $valor = intval($_POST['valor']);
    if($valor <= $player['MoedaMumu']){
        $newCorrente = $player['MoedaMumu'] - $valor;
        $newPoupanca = $player['Poupanca'] + $valor;
        $update = "UPDATE Players SET MoedaMumu=?, Poupanca=? WHERE PlayerID=?";
        sqlsrv_query($conn, $update, array($newCorrente, $newPoupanca, $playerID));
        header("Location: bank.php");
        exit;
    } else {
        $msg = "Saldo insuficiente!";
    }
}
?>

<h2>Banco</h2>
<p>Corrente: <?=$player['MoedaMumu']?> moedas</p>
<p>Poupança: <?=$player['Poupanca']?> moedas</p>

<form method="post">
    Valor: <input name="valor" type="number" min="1">
    <button name="deposit">Depositar na Poupança</button>
</form>
<?php if(isset($msg)) echo "<p>$msg</p>"; ?>


<p>Corrente: <span id="corrente"><?=$player['MoedaMumu']?></span></p>
<p>Poupança: <span id="poupanca"><?=$player['Poupanca']?></span></p>

<input type="number" id="valor" min="1">
<button onclick="depositar()">Depositar</button>

<script>
function depositar(){
    let valor = document.getElementById('valor').value;
    fetch('bank_ajax.php', {
        method: 'POST',
        body: new URLSearchParams({valor: valor})
    }).then(res => res.text())
      .then(data => {
          if(data=="ok") atualizarBanco();
          else alert("Saldo insuficiente!");
    });
}

function atualizarBanco(){
    fetch('bank.php') // ou criar endpoint específico para AJAX
    .then(res => res.text())
    .then(html => {
        let parser = new DOMParser();
        let doc = parser.parseFromString(html, 'text/html');
        document.getElementById('corrente').innerText = doc.getElementById('corrente').innerText;
        document.getElementById('poupanca').innerText = doc.getElementById('poupanca').innerText;
    });
}
</script>
