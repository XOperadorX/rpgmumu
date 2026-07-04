<?php
session_start();
include 'db.php';

if(isset($_POST['login'])){
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "SELECT PlayerID, Username FROM Players WHERE Username=? AND Password=?";
    $stmt = sqlsrv_query($conn, $sql, array($user, $pass));
    if($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $_SESSION['PlayerID'] = $row['PlayerID'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Login inválido";
    }
}
?>
<form method="post">
    Usuário: <input name="username"><br>
    Senha: <input name="password" type="password"><br>
    <button name="login">Login</button>
</form>
<?php if(isset($error)) echo $error; ?>
