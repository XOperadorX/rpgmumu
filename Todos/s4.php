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

// Calcular saúde da conta baseada em logins diários e perda por dia sem login
$health = 0;
$loggedDays = [];
$now = new DateTime();

if (!empty($logins)) {
    foreach ($logins as $l) {
        if (!empty($l['LoginTime'])) {
            $loginTime = $l['LoginTime'];
            if (!($loginTime instanceof DateTime)) {
                $loginTime = new DateTime($loginTime);
            }
            $day = $loginTime->format('Y-m-d'); // contar apenas um login por dia
            if (!in_array($day, $loggedDays)) {
                $loggedDays[] = $day;
            }
        }
    }
}

// Verifica últimos 7 dias e calcula saúde
for ($i = 0; $i < 7; $i++) {
    $checkDay = (clone $now)->modify("-$i days")->format('Y-m-d');
    if (in_array($checkDay, $loggedDays)) {
        $health += 10; // login presente
    } else {
        $health -= 10; // dia sem login
    }
}

// Limitar entre 0 e 100
$health = min(max($health, 0), 100);

// Definir cor da barra baseada na saúde
if ($health >= 70) {
    $barColor = "#2ecc71"; // verde
} elseif ($health >= 40) {
    $barColor = "#f1c40f"; // amarelo
} else {
    $barColor = "#e74c3c"; // vermelho
}

// Exclusão automática se saúde <= 10%
if ($health <= 10) {
    // Deletar conta
    sqlsrv_query($conn, "DELETE FROM Players WHERE PlayerID=?", [$playerID]);
    sqlsrv_query($conn, "DELETE FROM LoginHistory WHERE PlayerID=?", [$playerID]);
    sqlsrv_query($conn, "DELETE FROM AccountHistory WHERE PlayerID=?", [$playerID]);
    
    session_destroy();
    header("Location: index.php"); // redireciona para página inicial
    exit;
}
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
            text-decoration: none;
            background: #2c3e50;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
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
    </style>
</head>
<body>

<div class="painel">
    <!-- Botão Voltar ao topo direito -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
        <a href="index.php" class="voltar">⬅ Voltar</a>
    </div>

    <h1>🩺 Saúde da Conta</h1>

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

    <!-- Barra de saúde dinâmica com cores -->
    <div class="info">
        <strong>Saúde da Conta:</strong>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $health ?>%; background: <?= $barColor ?>;"><?= $health ?>%</div>
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
