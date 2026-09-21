<?php
session_start();

// Remove all session variables
$_SESSION = [];

// If there's a session cookie, remove it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to index
header('Location: index.php');
exit;

?>
