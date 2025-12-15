<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../LoginPage/LoginPage.php');
    exit;
}

$db = Database::getInstance();
$con = $db->getConnection();

$message = '';
$currentUser = $_SESSION['username'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Change username
    if (isset($_POST['change_username'])) {
        $newName = trim($_POST['new_username'] ?? '');
        if ($newName === '') {
            $message = "Username cannot be empty.";
        } else {
            $stmt = $con->prepare("UPDATE Player SET username = ? WHERE username = ?");
            $stmt->bind_param("ss", $newName, $currentUser);
            if ($stmt->execute()) {
                $_SESSION['username'] = $newName;
                $currentUser = $newName;
                $message = "Username updated successfully.";
            } else {
                $message = "Error updating username.";
            }
            $stmt->close();
        }
    }

    // Change password
    if (isset($_POST['change_password'])) {
        $oldPass = trim($_POST['old_password'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');
        if ($oldPass === '' || $newPass === '') {
            $message = "Both fields are required.";
        } else {
            $stmt = $con->prepare("SELECT password FROM Player WHERE username = ?");
            $stmt->bind_param("s", $currentUser);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res && password_verify($oldPass, $res['password'])) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $updateStmt = $con->prepare("UPDATE Player SET password = ? WHERE username = ?");
                $updateStmt->bind_param("ss", $newHash, $currentUser);
                if ($updateStmt->execute()) {
                    $message = "Password updated successfully.";
                } else {
                    $message = "Error updating password.";
                }
                $updateStmt->close();
            } else {
                $message = "Old password is incorrect.";
            }
            $stmt->close();
        }
    }

    // Export save file
    if (isset($_POST['export_save'])) {
        $stmt = $con->prepare("SELECT * FROM Player WHERE username = ?");
        $stmt->bind_param("s", $currentUser);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="save_'.$currentUser.'.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    // Backup database
    if (isset($_POST['backup_db'])) {
        $backupFile = __DIR__ . "/backup_monopoly_".date('Ymd_His').".sql";
        $command = "mysqldump -u root -p'yourPassword' monopoly > $backupFile"; // change password
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $message = "Database backup created: " . basename($backupFile);
        } else {
            $message = "Error creating backup. Check server permissions.";
        }
    }

    // Delete user
    if (isset($_POST['delete_user'])) {
        $stmt = $con->prepare("DELETE FROM Player WHERE username = ?");
        $stmt->bind_param("s", $currentUser);
        if ($stmt->execute()) {
            session_destroy();
            header("Location: ../LoginPage/LoginPage.php");
            exit;
        } else {
            $message = "Error deleting user.";
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
<title>Settings</title>
<link rel="stylesheet" href="../../../Assets/css/style.css">
<style>
form button { margin-bottom: 12px; }
</style>
</head>
<body>
<div class="container">
    <h1>Settings</h1>

    <?php if($message): ?>
        <div class="meta"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Change username -->
    <form method="post">
        <input type="text" name="new_username" placeholder="New Username">
        <button type="submit" name="change_username" class="btn primary">Change Username</button>
    </form>

    <!-- Change password -->
    <form method="post">
        <input type="password" name="old_password" placeholder="Old Password">
        <input type="password" name="new_password" placeholder="New Password">
        <button type="submit" name="change_password" class="btn primary">Change Password</button>
    </form>

    <!-- Export save file -->
    <form method="post">
        <button type="submit" name="export_save" class="btn primary">Export Save File</button>
    </form>

    <!-- Backup database -->
    <form method="post">
        <button type="submit" name="backup_db" class="btn primary">Backup Database</button>
    </form>

    <!-- Delete user -->
    <button class="btn danger" id="deleteBtn">Delete User</button>

    <!-- Back to homepage -->
    <form action="../HomePage/HomePage.php">
        <button type="submit" class="btn ghost">Back to Home</button>
    </form>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h3>Confirm Delete</h3>
        <p>Are you sure you want to delete your account? This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn danger" id="confirmDelete">Yes, Delete</button>
            <button class="btn secondary" id="cancelDelete">Cancel</button>
        </div>
    </div>
</div>

<script>
const deleteBtn = document.getElementById("deleteBtn");
const modalOverlay = document.getElementById("modalOverlay");
const cancelDelete = document.getElementById("cancelDelete");
const confirmDelete = document.getElementById("confirmDelete");

// Show modal
deleteBtn.addEventListener("click", () => {
    modalOverlay.style.display = "flex";
});

// Cancel modal
cancelDelete.addEventListener("click", () => {
    modalOverlay.style.display = "none";
});

// Confirm deletion
confirmDelete.addEventListener("click", () => {
    const form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = '<input type="hidden" name="delete_user" value="1">';
    document.body.appendChild(form);
    form.submit();
});
</script>
</body>
</html>
