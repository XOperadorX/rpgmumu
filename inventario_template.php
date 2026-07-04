<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>🪙 Inventário & Mercado - MoedaMumu</title>
<style>
body { background:#1e1e2f; color:#fff; font-family:Arial; text-align:center; margin:0; padding:0; }
h1 { color:#ffcc00; margin:25px 0 10px 0; text-shadow:0 0 10px rgba(255,204,0,0.8); }
h2 { color:#00ccff; margin:20px 0 10px 0; text-shadow:0 0 8px rgba(0,204,255,0.6); }

table { width:90%; margin:15px auto; border-collapse:collapse; }
th, td { padding:8px; border:1px solid #555; text-align:center; }
th { background:#333; }
tr:nth-child(even) { background:#2a2a3a; }
tr:nth-child(odd) { background:#1f1f2e; }

form { margin:0; }
input[type=number] { width:80px; padding:5px; border-radius:5px; border:1px solid #555; background:#1c1c1c; color:#fff; text-align:center; }
input[type=number]:focus { border-color:#00ccff; outline:none; box-shadow:0 0 10px #00ccff55; }

button { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; background:linear-gradient(145deg,#ffcc00,#ff8800); color:#000; transition:0.3s; }
button:hover { background:linear-gradient(145deg,#ffee55,#ffaa00); transform:scale(1.05); }

nav { display:flex; justify-content:center; gap:15px; background:rgba(30,30,30,0.9); padding:15px; box-shadow:0 0 15px rgba(255,204,0,0.2); border-bottom:2px solid #ffcc00; }
nav a, nav button { text-decoration:none; color:#ffcc00; font-weight:bold; cursor:pointer; background:none; border:none; font-size:1rem; transition:0.3s; }
nav a:hover, nav button:hover { color:#00ccff; transform:scale(1.1); }

.card { background: linear-gradient(160deg,#242424,#181818); padding:25px; border-radius:15px; margin:25px auto; width:90%; max-width:480px; box-shadow:0 4px 20px rgba(0,0,0,0.6); border:1px solid rgba(255,255,255,0.05); }
</style>
</head>
<body>

<nav>
    <form method="post" style="display:flex; gap:10px; align-items:center;">
        <button type="submit" name="refresh">🔄 Atualizar</button>
    </form>
    <a href="trade.php">📈 Trade</a>
    <a href="dashboard.php">⬅️ Voltar</a>
</nav>

<h1>🪙 Mercado & Inventário - MoedaMumu</h1>
<p>Jogador: <b><?=htmlspecialchars($username)?></b> | Saldo: <b><?=$moedas?></b> 💰</p>

<!-- INVENTÁRIO -->
<h2>🎒 Meu Inventário</h2>
<table>
<tr><th>Item</th><th>Quantidade</th><th>Vender</th></tr>
<?php
if(count($carteira) == 0){
    echo "<tr><td colspan='3'>Inventário vazio</td></tr>";
} else {
    foreach($carteira as $id => $qtd){
        $nome = $itensNome[$id] ?? "Item #$id";
        echo "<tr>
                <td>$nome</td>
                <td>$qtd</td>
                <td>
                    <form method='post'>
                        <input type='hidden' name='item_id' value='$id'>
                        <input type='number' name='quantidade' min='1' max='$qtd' required placeholder='Qtd'>
                        <input type='number' step='0.01' name='preco' required placeholder='Preço'>
                        <button type='submit' name='vender'>Vender</button>
                    </form>
                </td>
              </tr>";
    }
}
?>
</table>

<!-- MERCADO -->
<h2>🏦 Itens à Venda</h2>
<table>
<tr><th>Item</th><th>Quantidade</th><th>Preço</th><th>Vendedor</th><th>Ação</th></tr>
<?php
while($row = sqlsrv_fetch_array($stmtMercado, SQLSRV_FETCH_ASSOC)){
    echo "<tr>
            <td>{$row['ItemNome']}</td>
            <td>{$row['Quantidade']}</td>
            <td>{$row['PrecoMoedaMumu']}</td>
            <td>{$row['Vendedor']}</td>
            <td>
                <form method='post'>
                    <input type='hidden' name='mercado_id' value='{$row['MercadoID']}'>
                    <button type='submit' name='comprar'>Comprar</button>
                </form>
            </td>
          </tr>";
}
?>
</table>

<!-- HISTÓRICO -->
<h2>📜 Histórico de Transações</h2>
<table>
<tr><th>Tipo</th><th>Item</th><th>Qtd</th><th>Preço</th><th>Comprador</th><th>Vendedor</th><th>Data</th></tr>
<?php
while($row = sqlsrv_fetch_array($stmtHistorico, SQLSRV_FETCH_ASSOC)){
    $tipo = $row['Tipo'] == 'COMPRA' ? '🟢' : '🔴';
    $data = $row['DataTransacao'] instanceof DateTime ? $row['DataTransacao']->format('d/m H:i') : $row['DataTransacao'];
    echo "<tr>
            <td>$tipo {$row['Tipo']}</td>
            <td>{$row['ItemNome']}</td>
            <td>{$row['Quantidade']}</td>
            <td>{$row['PrecoMoedaMumu']}</td>
            <td>".($row['Comprador'] ?? '-')."</td>
            <td>".($row['Vendedor'] ?? '-')."</td>
            <td>$data</td>
          </tr>";
}
?>
</table>

</body>
</html>
