<?php
session_start();
session_destroy();
unset($_SESSION['role']);

header("Location: ../../Frontend/auth/login.html");
exit();
?>