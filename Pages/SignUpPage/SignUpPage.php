<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php'; // <-- singleton

use function htmlspecialchars;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pwd = trim($_POST['password'] ?? '');
    $pwd2 = trim($_POST['password_confirm'] ?? '');

    // Basic validations
    if ($uname === '' || $pwd === '' || $pwd !== $pwd2) {
        $message = "Fill fields and ensure passwords match.";
    } elseif (strlen($uname) > 100 || strlen($pwd) > 256) {
        // limit lengths to avoid very large values
        $message = "Input too long.";
    } else {
        // Get the singleton DB connection
        $db   = Database::getInstance();
        $con  = $db->getConnection();

        // Check username availability using a prepared statement
        $stmt = $con->prepare("SELECT player_id FROM Player WHERE username = ?");
        if ($stmt === false) {
            error_log('Prepare failed (select username): ' . $con->error);
            $message = "An error occurred. Try again later.";
        } else {
            $stmt->bind_param("s", $uname);
            if (!$stmt->execute()) {
                error_log('Execute failed (select username): ' . $stmt->error);
                $message = "An error occurred. Try again later.";
                $stmt->close();
            } else {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $message = "Username already exists.";
                    $stmt->close();
                } else {
                    $stmt->close();

                    // Hash the password using PHP default (currently bcrypt or better)
                    $hash = password_hash($pwd, PASSWORD_DEFAULT);
                    if ($hash === false) {
                        error_log('password_hash failed for user: ' . $uname);
                        $message = "An error occurred. Try again later.";
                    } else {
                        // Insert new player
                        $ins = $con->prepare("INSERT INTO Player (username, password, money, position) VALUES (?, ?, ?, ?)");
                        if ($ins === false) {
                            error_log('Prepare failed (insert player): ' . $con->error);
                            $message = "An error occurred. Try again later.";
                        } else {
                            $defaultMoney = 1500;
                            $defaultPos = 0;
                            $ins->bind_param("ssii", $uname, $hash, $defaultMoney, $defaultPos);
                            if ($ins->execute()) {
                                // Success: log user in
                                $_SESSION['username'] = $uname;
                                $ins->close();
                                header('Location: ../HomePage/HomePage.php');
                                exit;
                            } else {
                                // If duplicate username slipped in due to race, handle gracefully
                                if ($con->errno === 1062) { // duplicate entry error code in MySQL
                                    $message = "Username already exists.";
                                } else {
                                    error_log('Insert failed: ' . $ins->error);
                                    $message = "Error creating account.";
                                }
                                $ins->close();
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sign Up</title>
<style>
    body {
        margin: 0; padding: 0; height: 100vh;
        display: flex; justify-content: center; align-items: center;
        background: url("../../Assets/bg.jpg") no-repeat center center/cover;
        font-family: Arial, sans-serif;
    }
    .container {
        text-align: center;
        background: rgba(100, 177, 255, 0.54);
        padding: 40px 60px;
        border-radius: 20px;
        backdrop-filter: blur(3px);
    }
    h3 { margin-bottom: 20px; color: #fff; }
    input {
        width: 100%; padding: 12px; margin: 10px 0;
        border-radius: 10px; border: none; font-size: 16px;
    }
    button {
        width: 100%; padding: 12px; margin-top: 15px;
        font-size: 18px; border: none; border-radius: 10px;
        background: #4caf50; color: white; cursor: pointer;
        transition: 0.2s ease;
    }
    button:hover { opacity: 0.8; color: rgba(207, 47, 236, 1); }
    .message { color: red; margin-bottom: 10px; }
</style>
</head>
<body>
    <div class="container">
        <h3>Sign Up</h3>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input name="username" placeholder="Username" required maxlength="100">
            <input name="password" type="password" placeholder="Password" required maxlength="256" autocomplete="new-password">
            <input name="password_confirm" type="password" placeholder="Confirm Password" required maxlength="256" autocomplete="new-password">
            <button type="submit">Create account</button>
        </form>
    </div>
</body>
</html>
