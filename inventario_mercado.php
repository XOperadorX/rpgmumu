<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        padding: 20px;
    }
    h2 {
        background-color: #3b82f6;
        color: white;
        padding: 10px;
        border-radius: 5px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
        background-color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    table th, table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }
    table th {
        background-color: #2563eb;
        color: white;
    }
    form input[type="number"] {
        width: 60px;
        padding: 3px;
        margin-right: 3px;
        text-align: center;
    }
    form button {
        padding: 5px 10px;
        border: none;
        color: white;
        cursor: pointer;
        border-radius: 3px;
        transition: 0.2s;
    }
    .vender button { background-color: #10b981; }
    .vender button:hover { background-color: #059669; }
    .vender-direto button { background-color: #f59e0b; }
    .vender-direto button:hover { background-color: #b45309; }
    .compra button { background-color: #ef4444; }
    .compra button:hover { background-color: #b91c1c; }
	/* Links estilo botão */
a.botao { 
    color:#fff; 
    text-decoration:none; 
    background: linear-gradient(90deg, #3498db, #9b59b6); 
    padding:10px 15px; 
    border-radius:5px; 
    margin:5px; 
    display:inline-block; 
    transition: all 0.2s ease-in-out;
}
a.botao:hover { 
    background: linear-gradient(90deg, #2980b9, #8e44ad); 
    transform: scale(1.05);
}
.info { margin-bottom:20px; font-size:18px; }
.saldo { color:#00ff88; font-weight:bold; }
a.botao, button.botao {
    color:#fff; text-decoration:none; background:#3498db;
    padding:10px 15px; border-radius:5px; margin:5px;
    display:inline-block; border:none; cursor:pointer; font-size:15px;
}
</style>
<nav>
    <div style="margin-top:25px;">

	    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
    </div>
</nav>

<script>
function validarVenda(form, inventarioQtd) {
    let qtd = parseInt(form.quantidade.value);
    let preco = parseFloat(form.preco.value);

    if (isNaN(qtd) || qtd <= 0) { alert("Quantidade inválida."); return false; }
    if (qtd > inventarioQtd) { alert("Quantidade maior que seu inventário."); return false; }
    if (isNaN(preco) || preco <= 0) { alert("Preço inválido."); return false; }

    if(form.destino_id) { // venda direta
        let pid = parseInt(form.destino_id.value);
        if(isNaN(pid) || pid <= 0) { alert("PlayerID inválido."); return false; }
    }

    return true;
}
</script>

<h2>Seu Inventário</h2>
<table>
    <tr>
        <th>Item</th>
        <th>Quantidade</th>
        <th>Vender no Mercado</th>
        <th>Vender Direto</th>
    </tr>
    <?php if(!empty($carteira) && is_array($carteira)): ?>
        <?php foreach($carteira as $itemID => $qtd): ?>
            <tr>
                <td><?= htmlspecialchars($itensNome[$itemID] ?? 'Desconhecido') ?></td>
                <td><?= $qtd ?></td>

                <!-- Vender no Mercado -->
                <td class="vender">
                    <form method="POST" onsubmit="return validarVenda(this, <?= $qtd ?>)">
                        <input type="hidden" name="item_id" value="<?= $itemID ?>">
                        <input type="number" name="quantidade" value="1" min="1" max="<?= $qtd ?>">
                        <input type="number" step="0.01" name="preco" placeholder="Preço">
                        <button type="submit" name="vender">Vender</button>
                    </form>
                </td>

                <!-- Vender Direto -->
                <td class="vender-direto">
                    <form method="POST" onsubmit="return validarVenda(this, <?= $qtd ?>)">
                        <input type="hidden" name="item_id" value="<?= $itemID ?>">
                        <input type="number" name="quantidade" value="1" min="1" max="<?= $qtd ?>">
                        <input type="number" step="0.01" name="preco" placeholder="Preço">
                        <input type="number" name="destino_id" placeholder="PlayerID">
                        <button type="submit" name="vender_para">Vender Direto</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="4">Inventário vazio</td></tr>
    <?php endif; ?>
</table>

<h2>Mercado</h2>
<table>
    <tr>
        <th>Item</th>
        <th>Quantidade</th>
        <th>Preço</th>
        <th>Vendedor</th>
        <th>Comprar</th>
    </tr>
    <?php if(isset($stmtMercado) && $stmtMercado): ?>
        <?php while($row = sqlsrv_fetch_array($stmtMercado, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($row['ItemNome']) ?></td>
                <td><?= $row['Quantidade'] ?></td>
                <td><?= $row['PrecoMoedaMumu'] ?></td>
                <td><?= htmlspecialchars($row['Vendedor']) ?></td>
                <td class="compra">
                    <form method="POST">
                        <input type="hidden" name="mercado_id" value="<?= $row['MercadoID'] ?>">
                        <button type="submit" name="comprar">Comprar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5">Mercado vazio</td></tr>
    <?php endif; ?>
</table>

<h2>Histórico de Transações</h2>
<table>
    <tr>
        <th>Tipo</th>
        <th>Item</th>
        <th>Quantidade</th>
        <th>Preço</th>
        <th>Comprador</th>
        <th>Vendedor</th>
        <th>Data</th>
    </tr>
    <?php if(isset($stmtHistorico) && $stmtHistorico): ?>
        <?php while($h = sqlsrv_fetch_array($stmtHistorico, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= $h['Tipo'] ?></td>
                <td><?= htmlspecialchars($h['ItemNome']) ?></td>
                <td><?= $h['Quantidade'] ?></td>
                <td><?= $h['PrecoMoedaMumu'] ?></td>
                <td><?= htmlspecialchars($h['Comprador'] ?? '-') ?></td>
                <td><?= htmlspecialchars($h['Vendedor'] ?? '-') ?></td>
                <td>
                    <?php 
                    if ($h['DataTransacao'] instanceof DateTime) {
                        echo $h['DataTransacao']->format('d/m/Y H:i:s');
                    } else {
                        echo htmlspecialchars($h['DataTransacao']);
                    }
                    ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7">Nenhuma transação</td></tr>
    <?php endif; ?>
</table>
