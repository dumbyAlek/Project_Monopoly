<?php
require_once __DIR__ . '/../Database/Database.php';

function setUserSession(string $username) {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT user_id FROM User WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id);
    if ($stmt->fetch()) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
    } else {
        // Optional: handle user not found (should not happen if login passed)
        $_SESSION['user_id'] = null;
    }
    $stmt->close();
}
