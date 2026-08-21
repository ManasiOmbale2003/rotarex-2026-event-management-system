<?php
session_start();

/* 1. Unset all session variables */
$_SESSION = array();

/* 2. Delete the session cookie from the browser */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

/* 3. Destroy the session on server */
session_destroy();

/* 4. Prevent browser back button after logout */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Past date to force expiration

/* 5. Redirect to login page */
header("Location: login.php");
exit();
?>