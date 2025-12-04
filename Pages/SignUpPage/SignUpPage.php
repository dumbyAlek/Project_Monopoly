<?php
session_start();
require_once __DIR__ . '/../../Database/db_connect.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pwd = trim($_POST['password'] ?? '');
    $pwd2 = trim($_POST['password_confirm'] ?? '');

    if ($uname === '' || $pwd === '' || $pwd !== $pwd2) {
        $message = "Fill fields and ensure passwords match.";
    } else {
        $stmt = $con->prepare("SELECT player_id FROM Player WHERE username = ?");
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Username already exists.";
            $stmt->close();
        } else {
            $stmt->close();
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            $ins = $con->prepare("INSERT INTO Player (username, password, money, position) VALUES (?, ?, ?, ?)");
            $defaultMoney = 1500; $defaultPos = 0;
            $ins->bind_param("ssii", $uname, $hash, $defaultMoney, $defaultPos);
            if ($ins->execute()) {
                $_SESSION['username'] = $uname;
                header('Location: ../GamePage/gamepage.php');
                exit;
            } else {
                $message = "Error creating account.";
            }
            $ins->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <?php if ($message) echo "<div class='message'>".htmlspecialchars($message)."</div>"; ?>
        <form method="post">
            <input name="username" placeholder="Username" required>
            <input name="password" type="password" placeholder="Password" required>
            <input name="password_confirm" type="password" placeholder="Confirm Password" required>
            <button type="submit">Create account</button>
        </form>
    </div>
</body>
</html>
