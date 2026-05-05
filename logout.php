<?php
session_start();

// VULNERABLE: Session not properly destroyed
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>
