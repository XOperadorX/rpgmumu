<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])) exit('⛔ Acesso negado');

$adminID = $_SESSION['PlayerID'];
$stmt = sqlsrv_query($conn,"SELECT Role FROM Players WHERE PlayerID=?",[$adminID]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role']!=='admin') exit('⛔ Acesso negado');

$sqlUsers = "SELECT TOP 1000 PlayerID, Username, Email, MoedaMumu, LastLoginTime, IsBanned, Role, CodigoUsado 
             FROM Players ORDER BY PlayerID ASC";
$stmtUsers = sqlsrv_query($conn, $sqlUsers);

echo '<table>
<tr>
<th>ID</th>
<th>Usuário</th>
<th>Email</th>
<th>MoedaMumu</th>
<th>Último Login</th>
<th>Status</th>
<th>Role</th>
<th>Código Usado</th>
<th>Ações</th>
</tr>';

while($user = sqlsrv_fetch_array($stmtUsers, SQLSRV_FETCH_ASSOC)){
    $lastLogin = ($user['LastLoginTime'] instanceof DateTime) ? $user['LastLoginTime']->format('d/m/Y H:i:s') : '—';
    $status = $user['IsBanned'] ? '<span class="status-block">Bloqueado</span>' : '<span class="status-ok">Ativo</span>';
    $btnText = $user['IsBanned'] ? '🔓 Desbloquear' : '🔒 Bloquear';
    $moedaValue = isset($user['MoedaMumu']) ? number_format((float)$user['MoedaMumu'],2,'.','') : '0.00';

    echo '<tr>
        <td>'.htmlspecialchars($user['PlayerID']).'</td>
        <td>'.htmlspecialchars($user['Username']).'</td>
        <td>'.htmlspecialchars($user['Email']).'</td>
        <td><input type="number" class="edit-coins" data-playerid="'.htmlspecialchars($user['PlayerID']).'" value="'.$moedaValue.'" step="0.01"></td>
        <td>'.$lastLogin.'</td>
        <td>'.$status.'</td>
        <td>'.htmlspecialchars($user['Role']).'</td>
        <td>'.htmlspecialchars($user['CodigoUsado']).'</td>
        <td><button class="btn-toggle" data-playerid="'.htmlspecialchars($user['PlayerID']).'">'.$btnText.'</button></td>
    </tr>';
}
echo '</table>';
