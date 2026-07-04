<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Inventário Interativo</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
    h2 { background: #4CAF50; color: white; padding: 10px; border-radius: 5px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: white; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even){ background-color: #f2f2f2; }
    button { padding: 5px 10px; margin: 2px; cursor: pointer; border-radius: 3px; border: none; }
    .usar { background-color: #4CAF50; color: white; }
    .soltar { background-color: #f44336; color: white; }
    .enviar { background-color: #2196F3; color: white; }
</style>
</head>
<body>

<h1>Inventário Interativo</h1>
<div id="inventario"></div>

<script>
async function carregarInventario() {
    try {
        const response = await fetch('itens.php');
        const data = await response.json();
        const container = document.getElementById('inventario');
        container.innerHTML = '';

        for (const [charName, itens] of Object.entries(data)) {
            const titulo = document.createElement('h2');
            titulo.textContent = charName;
            container.appendChild(titulo);

            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>ItemID</th>
                        <th>Nome</th>
                        <th>Quantidade</th>
                        <th>Descrição</th>
                        <th>Última Aquisição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            `;
            const tbody = document.createElement('tbody');
            itens.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.ItemID}</td>
                    <td>${item.Nome}</td>
                    <td>${item.Quantidade}</td>
                    <td>${item.Descricao}</td>
                    <td>${item.UltimaData || ''}</td>
                    <td>
                        <button class="usar" onclick="usarItem(${item.ItemID})" ${!item.PodeUsar ? 'disabled' : ''}>Usar</button>
                        <button class="soltar" onclick="soltarItem(${item.ItemID})" ${!item.PodeSoltar ? 'disabled' : ''}>Soltar</button>
                        <button class="enviar" onclick="enviarItem(${item.ItemID})" ${!item.PodeEnviarArmazem ? 'disabled' : ''}>Enviar</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            container.appendChild(table);
        }
    } catch (error) {
        console.error('Erro ao carregar inventário:', error);
    }
}

// Funções de ação (usar, soltar, enviar)
async function realizarAcao(itemID, acao) {
    try {
        const response = await fetch('acao_item.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ ItemID: itemID, Acao: acao })
        });
        const result = await response.json();
        if(result.success){
            alert(`✅ Item ${acao} com sucesso!`);
            carregarInventario();
        } else {
            alert(`⚠️ Falha ao ${acao} item: ${result.message}`);
        }
    } catch (error) {
        console.error(`Erro ao ${acao} item:`, error);
    }
}

function usarItem(itemID) { realizarAcao(itemID, 'usar'); }
function soltarItem(itemID) { realizarAcao(itemID, 'soltar'); }
function enviarItem(itemID) { realizarAcao(itemID, 'enviar'); }

carregarInventario();
</script>
</body>
</html>
