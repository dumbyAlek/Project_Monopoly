<?php
// Pages/LoginPage/LoginPage.php
session_start();
require_once __DIR__ . '/../../Database/db_config.php'; // adjust path

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_user = trim($_POST['username'] ?? '');
    $form_pass = trim($_POST['password'] ?? '');

    if ($form_user === '' || $form_pass === '') {
        $message = 'Enter username and password.';
    } else {
        // Path to the compiled C++ binary (adjust to where you put auth_exec)
        $authExec = realpath(__DIR__ . '/../../Backend/Session Management/auth_exec'); // adapt path

        if (!$authExec || !file_exists($authExec)) {
            $message = 'Server auth executable is missing.';
        } else {
            // Use DB credentials from db_config.php ($servername, $username, $password, $dbname)
            // IMPORTANT: $username and $password in db_config.php are DB credentials; rename to avoid confusion.
            $dbHost = $servername;
            $dbUser = $username; // from db_config.php (DB user)
            $dbPass = $password; // from db_config.php (DB password)
            $dbName = $dbname;

            // Build secure command
            $cmd = escapeshellcmd($authExec)
                 . ' ' . escapeshellarg($dbHost)
                 . ' ' . escapeshellarg($dbUser)
                 . ' ' . escapeshellarg($dbPass)
                 . ' ' . escapeshellarg($dbName)
                 . ' ' . escapeshellarg($form_user)
                 . ' ' . escapeshellarg($form_pass);

            // Execute
            $output = [];
            $return_var = 0;
            exec($cmd, $output, $return_var);
            $resp = isset($output[0]) ? trim($output[0]) : '';

            if ($resp === 'OK') {
                $_SESSION['username'] = $form_user;
                header('Location: ../GamePage/gamepage.php');
                exit;
            } elseif ($resp === 'NOT_FOUND') {
                $message = "User not found. <a href='../SignUpPage/SignUpPage.php'>Sign up</a>";
            } else {
                $message = "Login failed. Incorrect credentials or server error.";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
  <div style="width:320px;margin:40px auto;padding:20px;border:1px solid #eee;border-radius:6px;">
    <h3>Login</h3>
    <?php if ($message): ?><div style="color:red;"><?= $message ?></div><?php endif; ?>
    <form method="post" action="">
      <input name="username" placeholder="Username" style="width:100%;padding:8px;margin:8px 0"><br>
      <input name="password" type="password" placeholder="Password" style="width:100%;padding:8px;margin:8px 0"><br>
      <button type="submit">Login</button>
    </form>
    <p><a href="../SignUpPage/SignUpPage.php">Not registered? Sign up</a></p>
  </div>
</body>
</html>
