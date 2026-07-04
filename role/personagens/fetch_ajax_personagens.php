<?php
session_start();
include "db.php";

if(!isset($_SESSION['PlayerID'])){
    die("⛔ Acesso negado.");
}

$stmt = sqlsrv_query($conn, "SELECT Role FROM Players WHERE PlayerID=?", [$_SESSION['PlayerID']]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if(!$row || $row['Role'] !== 'admin') die("⛔ Acesso negado.");

$sqlChars = "SELECT C.CharID, C.PlayerID, P.Username, C.Name, C.Class, C.Level, C.Reset, C.MReset
             FROM Characters C
             JOIN Players P ON C.PlayerID = P.PlayerID
             ORDER BY C.CharID ASC";
$stmtChars = sqlsrv_query($conn, $sqlChars);

echo '<table>
<tr>
<th>CharID</th>
<th>Jogador</th>
<th>Nome</th>
<th>Classe</th>
<th>Nível</th>
<th>Reset</th>
<th>MReset</th>
<th>Ações</th>
</tr>';

while($char = sqlsrv_fetch_array($stmtChars, SQLSRV_FETCH_ASSOC)){
    echo '<tr>
    <td>'.$char['CharID'].'</td>
    <td>'.htmlspecialchars($char['Username']).' (ID: '.$char['PlayerID'].')</td>
    <td>'.htmlspecialchars($char['Name']).'</td>
    <td>'.htmlspecialchars($char['Class']).'</td>
    <td>'.$char['Level'].'</td>
    <td>'.$char['Reset'].'</td>
    <td>'.$char['MReset'].'</td>
    <td>
        <form method="post" action="admin_personagem_edit.php" style="display:inline;">
        <input type="hidden" name="CharID" value="'.$char['CharID'].'">
        <button type="submit" class="btn-edit">✏ Editar</button>
        </form>
        <button class="btn-del" data-charid="'.$char['CharID'].'">🗑 Excluir</button>
    </td>
    </tr>';
}
echo '</table>';
?>
