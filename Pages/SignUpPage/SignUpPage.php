<?php
// SignUpPage.php
session_start();
require_once __DIR__ . '/../../Database/db_connect.php'; // includes db_config.php and $con

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pwd = trim($_POST['password'] ?? '');
    $pwd2 = trim($_POST['password_confirm'] ?? '');

    if ($uname === '' || $pwd === '' || $pwd !== $pwd2) {
        $message = "Fill fields and ensure passwords match.";
    } else {
        // check existence
        $stmt = $con->prepare("SELECT player_id FROM Player WHERE username = ?");
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Username already exists.";
            $stmt->close();
        } else {
            $stmt->close();
            // Hash password using PHP's password_hash (bcrypt by default)
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            // Insert: set defaults for money/position etc. Adjust columns per your Player schema.
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
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Sign Up</title></head>
<body>
  <div style="width:360px;margin:40px auto;padding:20px;border:1px solid #eee;">
    <h3>Sign Up</h3>
    <?php if ($message) echo "<div style='color:red;'>".htmlspecialchars($message)."</div>"; ?>
    <form method="post" action="">
      <input name="username" placeholder="Username" required style="width:100%;padding:8px;margin:8px 0;"><br>
      <input name="password" type="password" placeholder="Password" required style="width:100%;padding:8px;margin:8px 0;"><br>
      <input name="password_confirm" type="password" placeholder="Confirm Password" required style="width:100%;padding:8px;margin:8px 0;"><br>
      <button type="submit">Create account</button>
    </form>
  </div>
</body>
</html>
