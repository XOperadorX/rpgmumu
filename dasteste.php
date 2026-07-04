<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>RPG Futurista</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
<style>
body { background: #000; color: #fff; font-family: 'Orbitron', sans-serif; }
.status-panel {
    width: 400px;
    padding: 25px;
    background: linear-gradient(135deg, #111, #222);
    border-radius: 20px;
    box-shadow: 0 0 30px #0ff;
    margin: 50px auto;
}
.status-bar { display: flex; align-items: center; margin: 15px 0; }
.bar-container { flex: 1; height: 25px; background: #333; border-radius: 12px; margin: 0 10px; overflow: hidden; }
.bar { height: 100%; width: 0%; border-radius: 12px; transition: width 1s ease-in-out; }
.hp { background: linear-gradient(90deg, #f00, #ff5555); }
.mana { background: linear-gradient(90deg, #00f, #55f); }
.power { background: linear-gradient(90deg, #0f0, #5f5); }
.btn-futurista {
    margin-top: 20px;
    padding: 12px 25px;
    background: linear-gradient(135deg, #0ff, #00f);
    border: none;
    border-radius: 15px;
    font-weight: bold;
    color: #000;
    cursor: pointer;
    box-shadow: 0 0 25px #0ff;
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-futurista:hover {
    transform: scale(1.05);
    box-shadow: 0 0 35px #0ff;
}
.popup-msg {
    position: fixed; top: 20px; right: 20px; padding: 15px 25px;
    border-radius: 15px; font-weight: bold; color: #fff;
    background: linear-gradient(135deg, #00f, #0ff);
    box-shadow: 0 0 25px rgba(0,255,255,0.6); z-index: 9999;
    animation: fadeInOut 3s forwards; display: none;
}
@keyframes fadeInOut {
    0% { opacity:0; transform:translateY(-20px); }
    10%, 90% { opacity:1; transform:translateY(0); }
    100% { opacity:0; transform:translateY(-20px); }
}
</style>
</head>
<body>

<div class="status-panel">
    <h2>⚡ Status do Personagem</h2>
    
    <div class="status-bar"><span>HP:</span>
        <div class="bar-container"><div id="hpBar" class="bar hp"></div></div>
        <span id="hpVal">0</span>
    </div>
    <div class="status-bar"><span>Mana:</span>
        <div class="bar-container"><div id="manaBar" class="bar mana"></div></div>
        <span id="manaVal">0</span>
    </div>
    <div class="status-bar"><span>Power:</span>
        <div class="bar-container"><div id="powerBar" class="bar power"></div></div>
        <span id="powerVal">0</span>
    </div>
    <div class="status-bar"><span>MoedaMumu:</span> <span id="moedaVal">0</span></div>

    <button id="restaurarBtn" class="btn-futurista">Restaurar Atributos (500 MoedaMumu)</button>
</div>

<div id="popupMsg" class="popup-msg"></div>

<script>
const char = {
    HP: parseInt(<?php echo $_SESSION['char']['HP'] ?? 100; ?>),
    MaxHP: parseInt(<?php echo $_SESSION['char']['MaxHP'] ?? 100; ?>),
    Mana: parseInt(<?php echo $_SESSION['char']['Mana'] ?? 50; ?>),
    MaxMana: parseInt(<?php echo $_SESSION['char']['MaxMana'] ?? 50; ?>),
    Power: parseInt(<?php echo $_SESSION['char']['Power'] ?? 30; ?>),
    MaxPower: parseInt(<?php echo $_SESSION['char']['MaxPower'] ?? 30; ?>),
    MoedaMumu: parseInt(<?php 
        $stmt = sqlsrv_query($conn, "SELECT MoedaMumu FROM Players WHERE PlayerID = ?", [$_SESSION['PlayerID']]);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        echo $row['MoedaMumu'] ?? 1000;
    ?>)
};

const updateBar = (barId, value, maxValue) => {
    const bar = document.getElementById(barId);
    bar.style.width = ((value / maxValue) * 100) + '%';
};

// Inicializa barras
updateBar('hpBar', char.HP, char.MaxHP);
updateBar('manaBar', char.Mana, char.MaxMana);
updateBar('powerBar', char.Power, char.MaxPower);
document.getElementById('hpVal').innerText = char.HP;
document.getElementById('manaVal').innerText = char.Mana;
document.getElementById('powerVal').innerText = char.Power;
document.getElementById('moedaVal').innerText = char.MoedaMumu;

// Restaurar via Ajax
document.getElementById('restaurarBtn').addEventListener('click', function() {
    fetch('ajax_restaurar.php', { method: 'POST' })
    .then(res => res.json())
    .then(data => {
        const popup = document.getElementById('popupMsg');
        popup.innerText = data.message;
        popup.style.display = 'block';
        setTimeout(() => { popup.style.display = 'none'; }, 3000);

        if(data.success){
            char.HP = data.HP;
            char.MaxHP = data.MaxHP;
            char.Mana = data.Mana;
            char.MaxMana = data.MaxMana;
            char.Power = data.Power;
            char.MaxPower = data.MaxPower;
            char.MoedaMumu = data.MoedaMumu;

            updateBar('hpBar', char.HP, char.MaxHP);
            updateBar('manaBar', char.Mana, char.MaxMana);
            updateBar('powerBar', char.Power, char.MaxPower);

            document.getElementById('hpVal').innerText = char.HP;
            document.getElementById('manaVal').innerText = char.Mana;
            document.getElementById('powerVal').innerText = char.Power;
            document.getElementById('moedaVal').innerText = char.MoedaMumu;
        }
    })
    .catch(err => alert("Erro de conexão!"));
});

</script>

</body>
</html>
