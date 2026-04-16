<?php
session_start();

// Load configuration and dependencies
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/core/helpers.php';
require_once dirname(__DIR__) . '/core/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user = new User($pdo);
    
    if ($action === 'register') {
        $result = $user->register($_POST);
        
        if ($result['success']) {
            header("Location: index.php?registered=1");
            exit();
        } else {
            $errors = $result['errors'];
            header("Location: index.php?error=" . urlencode(implode(', ', $errors)));
            exit();
        }
    } elseif ($action === 'login') {
        $result = $user->login($_POST['email'] ?? '', $_POST['password'] ?? '');
        
        if ($result['success']) {
            header("Location: " . BASE_URL . "views/dashboard.php");
            exit();
        } else {
            $errors = $result['errors'];
            header("Location: index.php?error=" . urlencode(implode(', ', $errors)));
            exit();
        }
    }
}

// Redirect to login if not authenticated
redirect(BASE_URL . 'public/index.php');
?>
