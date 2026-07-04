<?php
session_start();
include "db.php";

if (!isset($_SESSION['PlayerID'])) exit('⛔ Acesso negado');

$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?",[$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role']!=='admin') exit('⛔ Acesso negado');

$sqlPlayers = "SELECT PlayerID, Username, Email, MoedaMumu, LastLoginTime, IsBanned FROM Players ORDER BY PlayerID ASC";
$stmtPlayers = sqlsrv_query($conn, $sqlPlayers);

echo '<table>
<tr>
<th>ID</th>
<th>Usuário</th>
<th>Email</th>
<th>MoedaMumu</th>
<th>Último Login</th>
<th>Status</th>
<th>Ações</th>
</tr>';

if($stmtPlayers){
    while($player = sqlsrv_fetch_array($stmtPlayers, SQLSRV_FETCH_ASSOC)){
        $lastLogin = ($player['LastLoginTime'] instanceof DateTime) ? $player['LastLoginTime']->format("d/m/Y H:i:s") : '—';
        $status = $player['IsBanned'] ? '<span class="status-block">🚫 Bloqueado</span>' : '<span class="status-ok">✅ Ativo</span>';
        $btnText = $player['IsBanned'] ? '🔓 Desbloquear' : '🔒 Bloquear';
        echo '<tr>
        <td>'.$player['PlayerID'].'</td>
        <td>'.htmlspecialchars($player['Username']).'</td>
        <td>'.htmlspecialchars($player['Email']).'</td>
        <td>'.number_format($player['MoedaMumu'],2,'.',',').'</td>
        <td>'.$lastLogin.'</td>
        <td>'.$status.'</td>
        <td><button class="btn-toggle" data-playerid="'.$player['PlayerID'].'">'.$btnText.'</button></td>
        </tr>';
    }
}else{
    echo '<tr><td colspan="7">❌ Nenhum jogador encontrado.</td></tr>';
}
echo '</table>';
