<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';
require_once __DIR__ . '/../../Backend/User.php'; // same helper used by login

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pwd = trim($_POST['password'] ?? '');
    $pwd2 = trim($_POST['password_confirm'] ?? '');

    if ($uname === '' || $pwd === '' || $pwd !== $pwd2) {
        $message = "Fill fields and ensure passwords match.";
    } elseif (strlen($uname) > 100 || strlen($pwd) > 256) {
        $message = "Input too long.";
    } else {
        $db = Database::getInstance();
        $con = $db->getConnection();

        // Check username exists in the same table used by login (User)
        $stmt = $con->prepare("SELECT user_id FROM User WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $uname);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $message = "Username already exists.";
            } else {
                // Ready to insert into User table
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                $created = date('Y-m-d'); // user_created DATE column
                $useremail = ''; // no email field in form; keep empty string (or change if you add an email input)

                $ins = $con->prepare("INSERT INTO User (username, useremail, password, user_created) VALUES (?, ?, ?, ?)");
                if ($ins) {
                    $ins->bind_param("ssss", $uname, $useremail, $hash, $created);
                    if ($ins->execute()) {
                        // Set session the same way login does
                        setUserSession($uname);

                        // Redirect to homepage
                        header('Location: ../HomePage/HomePage.php');
                        exit;
                    } else {
                        $message = "Error creating account. Please try again.";
                    }
                    $ins->close();
                } else {
                    $message = "Database error: failed to prepare insert.";
                }
            }
            $stmt->close();
        } else {
            $message = "Database error: failed to prepare statement.";
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
<link rel="stylesheet" href="../../../Assets/css/style.css">
</head>
<body>
    <div class="container">
        <h3>Sign Up</h3>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input name="username" placeholder="Username" required maxlength="100" value="<?= isset($uname) ? htmlspecialchars($uname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '' ?>">
            <input name="password" type="password" placeholder="Password" required maxlength="256" autocomplete="new-password">
            <input name="password_confirm" type="password" placeholder="Confirm Password" required maxlength="256" autocomplete="new-password">
            <button type="submit" class="primary">Create Account</button>
        </form>
        <a class="login-link" href="../LoginPage/LoginPage.php">Already have an account? Login</a>
    </div>
</body>
</html>
