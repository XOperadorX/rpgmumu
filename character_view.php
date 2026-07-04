<?php
// ==========================
// Inicialização segura
// ==========================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "db.php";
include "check_ban.php";
include "functions.php"; // aqui ficam levelUpClassStatsAdvanced e calcularNextLevelXP

// ==========================
// Proteção de acesso
// ==========================
if (!isset($_SESSION['PlayerID'])) {
    die("⛔ Acesso negado. Faça login primeiro.");
}

$playerID = $_SESSION['PlayerID'];
$charID = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensagem = "";

// ==========================
// Carrega o personagem
// ==========================
$stmt = sqlsrv_query($conn, "SELECT * FROM Characters WHERE PlayerID=? AND CharID=?", [$playerID, $charID]);
$char = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$char) {
    die("<p style='color:red;'>❌ Personagem não encontrado.</p>");
}

// ==========================
// Subir de nível
// ==========================
if (isset($_POST['levelup'])) {
    if ($char['Exp'] >= $char['NextLevelExp']) {
        $char['Exp'] -= $char['NextLevelExp'];
        $char['Level']++;
        $char['NextLevelExp'] = calcularNextLevelXP($char['Level']);

        // Atualiza atributos da classe e restaura HP/Mana
        levelUpClassStatsAdvanced($char, true);

        // Atualiza no banco
        $sql = "UPDATE Characters SET 
                Level=?, Exp=?, NextLevelExp=?, MaxHP=?, MaxMana=?, HP=?, Mana=?,
                Attack=?, Defense=?, Magic=?, Resistance=?, Dexterity=?, Initiative=?, CritChance=?
                WHERE CharID=?";
        $params = [
            $char['Level'], $char['Exp'], $char['NextLevelExp'],
            $char['MaxHP'], $char['MaxMana'], $char['HP'], $char['Mana'],
            $char['Attack'], $char['Defense'], $char['Magic'], $char['Resistance'],
            $char['Dexterity'], $char['Initiative'], $char['CritChance'],
            $charID
        ];
        sqlsrv_query($conn, $sql, $params);

        $mensagem = "🎉 Parabéns! Você subiu para o nível {$char['Level']}!";
    } else {
        $mensagem = "⚠️ Ainda falta experiência para o próximo nível!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Personagem - <?=htmlspecialchars($char['Name'])?></title>
<style>
body { background: radial-gradient(circle at top, #0a0a12 0%, #020205 100%); color: #ddd; font-family: 'Segoe UI', sans-serif; text-align: center; }
.container { display: inline-block; background: rgba(30,30,50,0.9); padding: 25px; border-radius: 15px; box-shadow: 0 0 20px #003366; margin-top: 30px; width: 420px; }
h2 { color: #9af; text-shadow: 0 0 10px #4af; }
.stats { text-align: left; margin-top: 15px; }
.bar { background: #222; border-radius: 10px; overflow: hidden; height: 15px; margin-bottom: 10px; }
.fill { background: linear-gradient(90deg, #4af, #09f); height: 100%; }
.btn { background: #2a8cff; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; margin-top: 10px; transition: 0.3s; }
.btn:hover { background: #4ad0ff; }
nav { margin-top: 20px; }
nav a { color: #fff; background: #333; padding: 8px 15px; border-radius: 8px; text-decoration: none; margin: 0 5px; }
nav a:hover { background: #555; }
</style>
</head>
<body>

<nav>
    <a href="dashboard.php">🏰 Voltar</a>
    <a href="personagens.php">🧙‍♂ Personagens</a>
</nav>

<div class="container">
    <h2><?=htmlspecialchars($char['Name'])?> - <?=htmlspecialchars($char['Class'])?></h2>
    <p>Nível: <b><?=$char['Level']?></b></p>
    <p>Experiência: <b><?=$char['Exp']?> / <?=$char['NextLevelExp']?></b></p>
    <div class="bar">
        <div class="fill" style="width: <?=min(100, ($char['Exp'] / max(1, $char['NextLevelExp'])) * 100)?>%;"></div>
    </div>

    <div class="stats">
        <p>❤️ HP: <?=$char['HP']?> / <?=$char['MaxHP']?></p>
        <p>🔮 Mana: <?=$char['Mana']?> / <?=$char['MaxMana']?></p>
        <p>⚔️ Ataque: <?=$char['Attack']?></p>
        <p>🛡 Defesa: <?=$char['Defense']?></p>
        <p>✨ Magia: <?=$char['Magic']?></p>
        <p>🔥 Resistência: <?=$char['Resistance']?></p>
        <p>🏹 Destreza: <?=$char['Dexterity']?></p>
        <p>⚡ Iniciativa: <?=$char['Initiative']?></p>
        <p>💥 Chance Crítica: <?=$char['CritChance']?>%</p>
    </div>

    <form method="post">
        <button type="submit" name="levelup" class="btn">⬆️ Subir de Nível</button>
    </form>

    <?php if(!empty($mensagem)) echo "<p style='color:#ff8080;margin-top:10px;'>".htmlspecialchars($mensagem)."</p>"; ?>
</div>

</body>
</html>
