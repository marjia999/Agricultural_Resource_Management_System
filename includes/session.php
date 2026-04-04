<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'Admin';
}

function isClient() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'Client';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectIfNotClient() {
    redirectIfNotLoggedIn();
    if (!isClient()) {
        header("Location: ../index.php");
        exit();
    }
}
?>