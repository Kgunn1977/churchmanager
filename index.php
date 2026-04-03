<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . url('/dashboard.php'));
} else {
    header('Location: ' . url('/login.php'));
}
exit;
