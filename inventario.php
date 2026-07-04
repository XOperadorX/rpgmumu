

<?php
session_start();
include "db.php";
include "check_ban.php";

if(!isset($_SESSION['PlayerID'])){
    die("⛔ Acesso negado. Faça login primeiro.");
}

$playerID = $_SESSION['PlayerID'];

// ==========================
// Nome do personagem
// ==========================
$stmtChar = sqlsrv_query($conn, "SELECT Name FROM dbo.Characters WHERE PlayerID = ?", [$playerID]);
if($stmtChar === false){
    die("Erro ao buscar personagem: " . print_r(sqlsrv_errors(), true));
}
$char = sqlsrv_fetch_array($stmtChar, SQLSRV_FETCH_ASSOC);
$nomeJogador = htmlspecialchars($char['Name'] ?? 'Desconhecido');

// ==========================
// Saldo de moedas
// ==========================
$stmtMoedas = sqlsrv_query($conn, "SELECT MoedaMumu FROM dbo.Players WHERE PlayerID = ?", [$playerID]);
if($stmtMoedas === false){
    die("Erro ao buscar saldo: " . print_r(sqlsrv_errors(), true));
}
$player = sqlsrv_fetch_array($stmtMoedas, SQLSRV_FETCH_ASSOC);
$saldo = intval($player['MoedaMumu'] ?? 0);

// ==========================
// Inventário direto do banco
// ==========================
$inventario = [];
$stmtItens = sqlsrv_query($conn, "
    SELECT ItemID, Nome, Quantidade, Descricao, DataAdquirido, PodeUsar, PodeMarcarLixo,
           PodeEnviarArmazem, PodeSoltar, Raridade, Valor, Categoria
    FROM dbo.Items
    WHERE PlayerID = ?
", [$playerID]);

if($stmtItens === false){
    die("Erro ao buscar inventário: " . print_r(sqlsrv_errors(), true));
}

while($row = sqlsrv_fetch_array($stmtItens, SQLSRV_FETCH_ASSOC)){
    if(!isset($row['ItemID'])) continue;

    $raridade = strtolower(trim($row['Raridade'] ?? 'comum'));
    $inventario[$row['ItemID']] = [
        'nome' => htmlspecialchars($row['Nome'] ?? 'Item Desconhecido'),
        'qtd' => intval($row['Quantidade'] ?? 0),
        'descricao' => htmlspecialchars($row['Descricao'] ?? ''),
        'data' => isset($row['DataAdquirido']) && $row['DataAdquirido'] instanceof DateTime ? 
                    $row['DataAdquirido']->format('d/m/Y H:i') : '',
        'pode_usar' => !empty($row['PodeUsar']),
        'pode_marcar_lixo' => !empty($row['PodeMarcarLixo']),
        'pode_enviar_armazem' => !empty($row['PodeEnviarArmazem']),
        'pode_soltar' => !empty($row['PodeSoltar']),
        'raridade' => $raridade,
        'valor' => intval($row['Valor'] ?? 0),
        'categoria' => $row['Categoria'] ?? 'geral'
    ];
}

// ==========================
// Cores por raridade
// ==========================
$cores = [
    'comum' => 'gray',
    'incomum' => 'green',
    'raro' => 'blue',
    'épico' => 'purple',
    'lendário' => 'orange'
];
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>🎒 Inventário</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #222;
    color: #eee;
    margin: 0;
    padding: 20px;
}
h1 {
    text-align: center;
    color: #ffc107;
}
#inventario {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
    margin-top: 20px;
}
.item {
    background: #333;
    border: 2px solid #555;
    border-radius: 10px;
    padding: 10px;
    transition: 0.2s;
}
.item:hover {
    border-color: #ffc107;
}
.item h3 {
    margin: 0;
    color: #00bcd4;
}
.item small {
    color: #aaa;
}
.botoes {
    margin-top: 10px;
}
button {
    background-color: #444;
    border: none;
    color: #fff;
    padding: 6px 10px;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 5px;
}
button:hover {
    background-color: #666;
}
.raro { color: #4fc3f7; }
.epico { color: #ab47bc; }
.lendario { color: #ff9800; }
</style>
</head>
<body>

 <!--  ======================================================================= -->

<nav style="display:flex; justify-content:center; align-items:center; margin:20px;">
    <a href="dashboard.php" class="botao">⬅ 🏰 Inicio</a>
</nav>
 <!--  ======================================================================= -->



<h1>🎒 Inventário do Jogador</h1>
<p>Jogador: <strong><?= $nomeJogador ?></strong> | Saldo: <span id="saldo"><?= $saldo ?> 💰</span></p>

<div id="inventario">Carregando...</div>



 <!--  ======================================================================= -->


<h1>📦 Inventário de Itens</h1>

<div id="inventario-container">
<table>

<tr><th>Item</th>
<tr><th>Descricao</th>
<th>Quantidade</th>
<th>Valor</th>
<th>Ação</th></tr>

<?php foreach($inventario as $itemID => $dados):
    if(!is_array($dados)) continue;
    if(intval($dados['qtd']) <= 0) continue;
    $cor = $cores[$dados['raridade']] ?? 'white';
?>
<tr style="border-left:5px solid <?= $cor ?>" 
    data-itemid="<?= $itemID ?>" 
    data-nome="<?= htmlspecialchars($dados['nome']) ?>" 
    data-valor="<?= intval($dados['valor']) ?>" 
    data-pode-soltar="<?= intval($dados['pode_soltar']) ?>">
    
    <td class="item-box" onclick="equiparItem('<?= $itemID ?>')">
        <strong style="color:<?= $cor ?>"><?= htmlspecialchars($dados['nome']) ?></strong>
        <?php if(!empty($dados['descricao'])): ?>
            <div class="item-descricao"><?= nl2br($dados['descricao']) ?></div>
        <?php endif; ?>
    </td>
    <td class="qtd"><?= intval($dados['qtd']) ?></td>
    <td>💰 <?= intval($dados['valor']) ?></td>
    <td>
        <button class="vender-btn" onclick="venderItem(this)" <?= empty($dados['pode_soltar']) ? 'disabled' : '' ?>>Vender</button>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div id="mensagem"></div>
<div id="equipamentos" style="margin-top:20px;"></div>

 <!--  ======================================================================= -->
 <!--  ======================================================================= -->

<script>
// ============================
// Carrega itens via AJAX
// ============================
async function carregarInventario() {
    const res = await fetch('listar_itens.php');
    const data = await res.json();
    const container = document.getElementById('inventario');
    container.innerHTML = '';

    if (!data.sucesso || data.itens.length === 0) {
        container.innerHTML = '<p>Nenhum item encontrado.</p>';
        return;
    }

    data.itens.forEach(item => {
        const div = document.createElement('div');
        div.className = 'item';

        let raridadeClass = '';
        if (item.Raridade === 'Raro') raridadeClass = 'raro';
        else if (item.Raridade === 'Épico') raridadeClass = 'epico';
        else if (item.Raridade === 'Lendário') raridadeClass = 'lendario';

        div.innerHTML = `
            <h3 class="${raridadeClass}">${item.Nome}</h3>
            <small>${item.Categoria}</small><br>
            <b>Quantidade:</b> ${item.Quantidade}<br>
            <b>Valor:</b> ${item.Valor} 🪙<br>
            <b>Raridade:</b> ${item.Raridade}<br>
            <div class="botoes">
                <button onclick="usarItem(${item.ItemID})">Usar</button>
                <button onclick="venderItem(${item.ItemID}, '${item.Nome}', ${item.Valor})">Vender</button>
            </div>
        `;
        container.appendChild(div);
    });
}

// ============================
// Vender item
// ============================
async function venderItem(id, nome, valor) {
    if (!confirm(`Vender ${nome} por ${valor} moedas?`)) return;

    const formData = new FormData();
    formData.append('itemID', id);
    formData.append('valor', valor);
    formData.append('nome', nome);

    const res = await fetch('vender_item.php', { method: 'POST', body: formData });
    const data = await res.json();

    alert(data.mensagem);
    if (data.sucesso) carregarInventario();
}



// ============================
// Usar item de verdade
// ============================
async function usarItem(id) {
    const formData = new FormData();
    formData.append('itemID', id);

    const res = await fetch('usar_item.php', { method: 'POST', body: formData });
    const data = await res.json();

    alert(data.mensagem);
    if (data.sucesso) carregarInventario(); // atualiza inventário
}


// Carrega ao abrir a página
carregarInventario();
</script>

 <!--  ======================================================================= -->

 <!--  ======================================================================= -->

<script>
function venderItem(btn){
    const row = btn.closest('tr');
    const itemID = row.dataset.itemid;
    const valor = row.dataset.valor;
    const nome = row.dataset.nome;

    fetch('vender_item.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'itemID='+itemID+'&valor='+valor+'&nome='+encodeURIComponent(nome)
    }).then(r=>r.json()).then(res=>{
        const msg = document.getElementById('mensagem');
        if(res.sucesso){
            const qtdEl = row.querySelector('.qtd');
            if(res.nova_qtd <= 0){
                row.remove();
            } else {
                qtdEl.innerText = res.nova_qtd;
            }
            document.getElementById('saldo').innerText = res.novo_saldo + " 💰";
            msg.style.color = '#0f0';
            msg.innerText = 'Item vendido! +' + res.moedas + ' moedas';
        } else {
            msg.style.color = '#f00';
            msg.innerText = res.mensagem;
        }
    });
}

function equiparItem(itemID){
    fetch('equipamento.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'ItemID='+itemID
    }).then(r=>r.json()).then(data=>{
        const msg = document.getElementById('mensagem');
        if(data.success){
            msg.style.color='#0f0';
            msg.innerText='Item equipado: '+data.nome;
            atualizarEquipamentos();
        } else {
            msg.style.color='#f00';
            msg.innerText='Erro: '+data.error;
        }
    });
}

function atualizarEquipamentos(){
    fetch('mostrar_equipamentos.php')
    .then(r=>r.text())
    .then(html=>{ document.getElementById('equipamentos').innerHTML = html; });
}

atualizarEquipamentos();
</script>

 <!--  ======================================================================= -->


</body>
</html>
