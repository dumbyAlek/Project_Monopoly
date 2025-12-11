<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';
require_once __DIR__ . '/../../Backend/User.php';

$message = '';

$db = Database::getInstance();
$con = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_user = trim($_POST['username'] ?? '');
    $form_pass = trim($_POST['password'] ?? '');

    if ($form_user === '' || $form_pass === '') {
        $message = 'Enter username and password.';
    } else {
        $stmt = $con->prepare("SELECT password FROM User WHERE username = ?");
        $stmt->bind_param("s", $form_user);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($hash);
            $stmt->fetch();

            if (password_verify($form_pass, $hash)) {
                session_start(); // make sure session is started
                setUserSession($form_user);
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
<link rel="stylesheet" href="../../../Assets/css/style.css">
</head>
<body>
    <div class="container">
        <h3>Login</h3>
        <?php if ($message) echo "<div class='message'>$message</div>"; ?>
        <form method="post">
            <input name="username" placeholder="Username" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit" class="primary">Login</button>
        </form>
        <a class="login-link" href="../SignUpPage/SignUpPage.php">Not registered? Sign up</a>
    </div>
</body>
</html>
