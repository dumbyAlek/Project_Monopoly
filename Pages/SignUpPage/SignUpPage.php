<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';

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

        $stmt = $con->prepare("SELECT player_id FROM Player WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $uname);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $message = "Username already exists.";
            } else {
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                $ins = $con->prepare("INSERT INTO Player (username, password, money, position) VALUES (?, ?, ?, ?)");
                $defaultMoney = 1500;
                $defaultPos = 0;
                $ins->bind_param("ssii", $uname, $hash, $defaultMoney, $defaultPos);
                if ($ins->execute()) {
                    $_SESSION['username'] = $uname;
                    header('Location: ../HomePage/HomePage.php');
                    exit;
                } else {
                    $message = "Error creating account.";
                }
                $ins->close();
            }
            $stmt->close();
        } else {
            $message = "Database error.";
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
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input name="username" placeholder="Username" required maxlength="100">
            <input name="password" type="password" placeholder="Password" required maxlength="256" autocomplete="new-password">
            <input name="password_confirm" type="password" placeholder="Confirm Password" required maxlength="256" autocomplete="new-password">
            <button type="submit" class="primary">Create Account</button>
        </form>
        <a class="login-link" href="../LoginPage/LoginPage.php">Already have an account? Login</a>
    </div>
</body>
</html>
