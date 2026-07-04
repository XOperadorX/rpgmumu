<?php
session_start();
include "db.php";

// Verifica se o jogador está logado
if (!isset($_SESSION['PlayerID'])) {
    die("Acesso negado. Faça login.");
}

$playerID = $_SESSION['PlayerID'];

// Buscar informações do jogador
$sql = "SELECT Username, MoedaMumu, CreatedAt, UpdatedAt, LastLoginIP, LastLoginTime 
        FROM Players WHERE PlayerID=?";
$stmt = sqlsrv_query($conn, $sql, [$playerID]);

if ($stmt === false) {
    die("Erro ao buscar informações da conta.");
}

$dados = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Buscar histórico de transações (últimos 10 registros)
$sqlHistory = "SELECT TOP 10 Tipo, Valor, Data 
               FROM AccountHistory 
               WHERE PlayerID=? 
               ORDER BY Data DESC";
$stmtHistory = sqlsrv_query($conn, $sqlHistory, [$playerID]);
$history = [];
if ($stmtHistory !== false) {
    while ($row = sqlsrv_fetch_array($stmtHistory, SQLSRV_FETCH_ASSOC)) {
        $history[] = $row;
    }
}

// Buscar histórico de logins (últimos 10)
$sqlLogins = "SELECT TOP 10 LoginTime, LoginIP 
              FROM LoginHistory 
              WHERE PlayerID=? 
              ORDER BY LoginTime DESC";
$stmtLogins = sqlsrv_query($conn, $sqlLogins, [$playerID]);
$logins = [];
if ($stmtLogins !== false) {
    while ($row = sqlsrv_fetch_array($stmtLogins, SQLSRV_FETCH_ASSOC)) {
        $logins[] = $row;
    }
}


$health = 0; // valor inicial da barra

if (!empty($logins)) {
    $now = new DateTime();
    foreach ($logins as $l) {
        if (!empty($l['LoginTime'])) {
            $loginTime = $l['LoginTime'];
            if (!($loginTime instanceof DateTime)) {
                $loginTime = new DateTime($loginTime);
            }
            $diffDays = (int)$loginTime->diff($now)->format('%a');
            if ($diffDays <= 7) { // login nos últimos 7 dias
                $health += 10; // 10% por login recente
            }
        }
    }
}

// Limitar entre 0 e 100
$health = min(max($health, 0), 100);



?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>🩺 Saúde da Conta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f7;
            margin: 0;
            padding: 20px;
        }
        .painel {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #2c3e50;
        }
        .info {
            margin: 15px 0;
            padding: 12px;
            border-left: 5px solid #3498db;
            background: #ecf6fc;
        }
        .ok {
            border-left: 5px solid #2ecc71;
            background: #eafaf1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        table th {
            background: #2c3e50;
            color: #fff;
        }
        a.voltar {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background: #2c3e50;
            color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            transition: 0.3s;
        }
        a.voltar:hover {
            background: #34495e;
        }
        .subtitulo {
            margin-top: 25px;
            font-size: 18px;
            color: #2c3e50;
        }
		
		.progress-bar {
    width: 100%;
    background: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    height: 25px;
    margin-top: 8px;
}

.progress-fill {
    height: 100%;
    background: #2ecc71;
    width: 0%;
    border-radius: 12px;
    text-align: center;
    color: white;
    line-height: 25px;
    font-weight: bold;
    animation: fillProgress 1.5s forwards;
}

@keyframes fillProgress {
    from { width: 0%; }
    to { width: 100%; }
}
a.voltar {
    text-decoration: none;
    background: #2c3e50;
    color: #fff;
    padding: 5px 10px;       /* menor tamanho */
    border-radius: 5px;      /* mais compacto */
    font-size: 14px;         /* menor texto */
    transition: 0.3s;
}
a.voltar:hover {
    background: #34495e;
}



    </style>
</head>
<body>

<div class="painel">
    <h1>🩺 Saúde da Conta</h1>

 
	<div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
		<a href="index.php" class="voltar">⬅ Voltar</a>
	</div>

	

    <div class="info"><strong>Usuário:</strong> <?= htmlspecialchars($dados['Username']) ?></div>
    <div class="info"><strong>Moedas Mumu:</strong> <?= (int)$dados['MoedaMumu'] ?></div>
    <div class="info"><strong>Conta criada em:</strong> <?= $dados['CreatedAt'] ? $dados['CreatedAt']->format('d/m/Y H:i') : "Desconhecido" ?></div>
    <div class="info"><strong>Última atualização:</strong> <?= $dados['UpdatedAt'] ? $dados['UpdatedAt']->format('d/m/Y H:i') : "Nunca" ?></div>

    <div class="info">
        <strong>Último IP:</strong> <?= !empty($dados['LastLoginIP']) ? htmlspecialchars($dados['LastLoginIP']) : "Nunca logou" ?>
    </div>

    <div class="info">
        <strong>Último login:</strong>
        <?php
        if (!empty($dados['LastLoginTime'])) {
            if ($dados['LastLoginTime'] instanceof DateTime) {
                echo $dados['LastLoginTime']->format('d/m/Y H:i');
            } else {
                echo htmlspecialchars(date('d/m/Y H:i', strtotime($dados['LastLoginTime'])));
            }
        } else {
            echo "Nunca";
        }
        ?>
    </div>

<div class="info">
    <strong>Saúde da Conta:</strong>
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?= $health ?>%;"><?= $health ?>%</div>
    </div>
</div>



 
    <!-- Histórico de Logins -->
    <h2 class="subtitulo">🔑 Últimos Logins (até 10)</h2>
    <table>
        <tr><th>Data e Hora</th><th>IP</th></tr>
        <?php if (!empty($logins)): ?>
            <?php foreach ($logins as $l): ?>
            <tr>
                <td>
                <?php
                if (isset($l['LoginTime']) && $l['LoginTime'] instanceof DateTime) {
                    echo $l['LoginTime']->format('d/m/Y H:i');
                } elseif (!empty($l['LoginTime'])) {
                    echo htmlspecialchars(date('d/m/Y H:i', strtotime($l['LoginTime'])));
                } else {
                    echo '-';
                }
                ?>
                </td>
                <td><?= htmlspecialchars($l['LoginIP']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2">Nenhum login encontrado.</td></tr>
        <?php endif; ?>
    </table>

</div>

</body>
</html>
