<?php
session_start();
session_unset();   // limpa todas as variáveis de sessão
session_destroy(); // destrói a sessão
include "check_ban.php"; // protege a página


header("Location: index.php"); // redireciona para a tela de login
//header("Location: login.php"); // redireciona para a tela de login
exit;
