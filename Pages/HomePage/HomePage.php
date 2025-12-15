<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';

// Redirect to login if not authenticated
// if (!isset($_SESSION['username'])) {
//     header('Location: ../LoginPage/LoginPage.php');
//     exit;
// }

$username = $_SESSION['username'];

// Get DB connection from singleton
$db  = Database::getInstance();
$con = $db->getConnection();

// Find player_id for current user (prepared)
$player_id = null;
$lastSave = null;

if ($stmt = $con->prepare("SELECT user_id FROM User WHERE username = ? LIMIT 1")) {
    $stmt->bind_param("s", $username);
    if ($stmt->execute()) {
        $stmt->bind_result($pid);
        if ($stmt->fetch()) $player_id = $pid;
    }
    $stmt->close();
}

// If player_id found, fetch the most recent SaveFile row (join to Game for some metadata)
if ($player_id !== null) {
    $sql = "SELECT 
                g.game_id,
                g.last_saved_time,
                g.status
            FROM Game g
            WHERE g.user_id = ?
              AND g.last_saved_time IS NOT NULL
            ORDER BY g.last_saved_time DESC
            LIMIT 1";
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("i", $player_id);
        if ($stmt->execute()) {
            $stmt->bind_result($game_id, $saved_time, $game_status);
            if ($stmt->fetch()) {
                $lastSave = [
                  'game_id'    => $game_id,
                  'saved_time' => $saved_time,
                  'status'     => $game_status
                ];
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Home - Monopoly</title>
<link rel="stylesheet" href="../../../Assets/css/style.css">
</head>
<body>
    <div class="panel main">
      <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
      <p class="subtitle">Ready to play Monopoly? Choose an action below.</p>

      <div class="actions">
        <form id="newGameForm" method="post" action="../Game_Init/StartGame.php" style="margin:0;">
          <input type="hidden" name="action" value="start_new">
          <button type="submit" class="btn primary" id="startBtn">Start a New Game</button>
        </form>
        <?php if (!isset($_SESSION['username']) || $_SESSION['username'] === "Guest") { ?>
          <form id="SignUpBtn" method="post" action="../SignUpPage/SignUpPage.php" style="margin:0;">
            <input type="hidden" name="action" value="start_new">
            <button type="submit" class="btn" id="SignUpBtn">Sign Up</button>
          </form>
        <?php } ?>

        <?php if ($lastSave): ?>
          <form method="get" action="../LoadGame/LoadGame.php" style="margin:0;">
            <input type="hidden" name="game_id" value="<?= (int)$lastSave['game_id'] ?>">
            <button type="submit" class="btn" id="resumeBtn">Continue Last Saved Game</button>
            <div class="meta small">Last saved: <?= htmlspecialchars($lastSave['saved_time']) ?> — status: <?= htmlspecialchars($lastSave['status']) ?></div>
          </form>
        <?php else: ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['username']) && $_SESSION['username'] !== "Guest") { ?>
        <a class="btn ghost" href='../LoadGame/LoadGame.php'>Browse Saved Games</a>
        <a class="btn ghost" href="../SettingsPage/SettingsPage.php">Settings</a>
        <?php } ?>

        <!-- Logout: we intercept this submit and show a modal -->
         <?php if (isset($_SESSION['username']) && $_SESSION['username'] !== "Guest") { ?>
        <form id="logoutForm" method="post" action="../../index.php" style="margin:0;">
          <button type="submit" class="btn danger" id="logoutBtn">Logout</button>
        </form>
        <?php } ?>

      </div>
      
      <?php if (isset($_SESSION['username']) && $_SESSION['username'] !== "Guest") { ?>
      <footer style="margin-top:14px;">
        <div class="small">Tip: you can resume the last save or browse all saves. Use settings to change board theme and dice type.</div>
      </footer>
      <?php } ?>

    </div>

  <!-- Logout modal -->
  <div id="logoutModal" class="modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal" role="document" aria-labelledby="logoutTitle">
      <h3 id="logoutTitle">Confirm Logout</h3>
      <p>Are you sure you want to logout? Your current session will end and you will be redirected to the login page.</p>
      <div class="modal-actions" style="margin-top:8px;">
        <button id="cancelLogout" class="btn secondary">Cancel</button>
        <button id="confirmLogout" class="btn primary">Logout</button>
      </div>
    </div>
  </div>

<script src="HomePage.js"></script>
</body>
</html>
