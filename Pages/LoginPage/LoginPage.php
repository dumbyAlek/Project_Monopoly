<?php
session_start();
require_once __DIR__ . '/../../Database/db_config.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_user = trim($_POST['username'] ?? '');
    $form_pass = trim($_POST['password'] ?? '');

    if ($form_user === '' || $form_pass === '') {
        $message = 'Enter username and password.';
    } else {
        // Prepare statement
        $stmt = $con->prepare("SELECT password FROM Player WHERE username = ?");
        $stmt->bind_param("s", $form_user);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($hash);
            $stmt->fetch();

            if (password_verify($form_pass, $hash)) {
                $_SESSION['username'] = $form_user;
                header('Location: ../HomePage/HomePage.php');
                exit;
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "User not found. <a href='../SignUpPage/SignUpPage.php'>Sign up</a>";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
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
        background: #ffffff; cursor: pointer;
        transition: 0.2s ease;
    }
    button:hover { opacity: 0.8; color: rgba(207, 47, 236, 1); }
    .message { color: red; margin-bottom: 10px; }
    a { color: #fff; text-decoration: underline; }
</style>
</head>
<body>
    <div class="container">
        <h3>Login</h3>
        <?php if ($message) echo "<div class='message'>$message</div>"; ?>
        <form method="post">
            <input name="username" placeholder="Username" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p><a href="../SignUpPage/SignUpPage.php">Not registered? Sign up</a></p>
    </div>
</body>
</html>
