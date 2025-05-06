<?php
if (isset($_SESSION['user_id'])) {
    header("Location: ../Controllers/login.php"); 
    exit();
}