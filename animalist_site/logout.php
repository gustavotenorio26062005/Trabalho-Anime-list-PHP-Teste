<?php
session_start(); // Inicia a sessão
session_unset();   // Remove todas as variáveis de sessão
session_destroy(); 
header("Location: index.php"); 
exit();
?>