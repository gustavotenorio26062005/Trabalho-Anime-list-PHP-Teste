<?php
session_start(); // Start the session
unset($_SESSION['iduser']); // Unset the session variable for user ID
header("Location: login.php"); // Redirect to the login page
exit; // Ensure no further code is executed after the redirect
?>