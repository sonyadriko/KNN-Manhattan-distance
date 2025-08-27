<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>