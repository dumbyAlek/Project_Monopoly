<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../LoginPage/LoginPage.php');
    exit;
}

$db = Database::getInstance();
$con = $db->getConnection();
$currentUser = $_SESSION['username'];

// Check if current user is admin
$stmt = $con->prepare("
    SELECT a.user_id 
    FROM Admin a 
    JOIN User u ON a.user_id = u.user_id 
    WHERE u.username = ?
");
$stmt->bind_param("s", $currentUser);
$stmt->execute();
$res = $stmt->get_result();
$isAdmin = $res->num_rows > 0;
$stmt->close();

interface UserAction {
    public function execute();
}

class ChangeUsername implements UserAction {
    private $con;
    private $currentUser;
    private $newName;
    public $message = '';
    public function __construct($con, $currentUser, $newName) {
        $this->con = $con;
        $this->currentUser = $currentUser;
        $this->newName = $newName;
    }
    public function execute() {
        if (empty($this->newName)) {
            $this->message = "Username cannot be empty.";
            return;
        }
        $stmt = $this->con->prepare("UPDATE User SET username = ? WHERE username = ?");
        $stmt->bind_param("ss", $this->newName, $this->currentUser);
        if ($stmt->execute()) {
            $_SESSION['username'] = $this->newName;
            $this->message = "Username updated successfully.";
        } else {
            $this->message = "Error updating username.";
        }
        $stmt->close();
    }
}

class ChangePassword implements UserAction {
    private $con;
    private $currentUser;
    private $oldPass;
    private $newPass;
    public $message = '';
    public function __construct($con, $currentUser, $oldPass, $newPass) {
        $this->con = $con;
        $this->currentUser = $currentUser;
        $this->oldPass = $oldPass;
        $this->newPass = $newPass;
    }
    public function execute() {
        if (empty($this->oldPass) || empty($this->newPass)) {
            $this->message = "Both password fields are required.";
            return;
        }
        $stmt = $this->con->prepare("SELECT password FROM User WHERE username = ?");
        $stmt->bind_param("s", $this->currentUser);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && password_verify($this->oldPass, $res['password'])) {
            $newHash = password_hash($this->newPass, PASSWORD_DEFAULT);
            $updateStmt = $this->con->prepare("UPDATE User SET password = ? WHERE username = ?");
            $updateStmt->bind_param("ss", $newHash, $this->currentUser);
            if ($updateStmt->execute()) {
                $this->message = "Password updated successfully.";
            } else {
                $this->message = "Error updating password.";
            }
            $updateStmt->close();
        } else {
            $this->message = "Old password is incorrect.";
        }
        $stmt->close();
    }
}

class BackupDatabase implements UserAction {
    public $message = '';
    public function execute() {
        $backupFile = __DIR__ . "/backup_monopoly_" . date('Ymd_His') . ".sql";
        $command = "mysqldump -u rahiq -p'yourPassword' monopoly > $backupFile"; // change password
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->message = "Database backup created: " . basename($backupFile);
        } else {
            $this->message = "Error creating backup. Check server permissions.";
        }
    }
}

class ImportDatabase implements UserAction {
    private $filePath;
    public $message = '';
    public function __construct($filePath) {
        $this->filePath = $filePath;
    }
    public function execute() {
        if (!file_exists($this->filePath)) {
            $this->message = "SQL file not found.";
            return;
        }
        $command = "mysql -u root -p'yourPassword' monopoly < " . escapeshellarg($this->filePath);
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->message = "Database imported successfully.";
        } else {
            $this->message = "Error importing database. Check server permissions.";
        }
    }
}

class DeleteUser implements UserAction {
    private $con;
    private $currentUser;
    public $message = '';
    public function __construct($con, $currentUser) {
        $this->con = $con;
        $this->currentUser = $currentUser;
    }
    public function execute() {
        $stmt = $this->con->prepare("DELETE FROM User WHERE username = ?");
        $stmt->bind_param("s", $this->currentUser);
        if ($stmt->execute()) {
            session_destroy();
            header("Location: ../LoginPage/LoginPage.php");
            exit;
        } else {
            $this->message = "Error deleting user.";
        }
        $stmt->close();
    }
}

class ActionContext {
    private UserAction $action;
    public function setAction(UserAction $action) { $this->action = $action; }
    public function execute() { $this->action->execute(); return $this->action; }
}

$message = '';
$importFilePath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $context = new ActionContext();

    if (isset($_POST['change_username'])) {
        $action = new ChangeUsername($con, $currentUser, trim($_POST['new_username'] ?? ''));
        $context->setAction($action);
        $message = $context->execute()->message;
        $currentUser = $_SESSION['username'];
    }

    if (isset($_POST['change_password'])) {
        $action = new ChangePassword($con, $currentUser, trim($_POST['old_password'] ?? ''), trim($_POST['new_password'] ?? ''));
        $context->setAction($action);
        $message = $context->execute()->message;
    }

    if ($isAdmin && isset($_POST['backup_db'])) {
        $action = new BackupDatabase();
        $context->setAction($action);
        $message = $context->execute()->message;
    }

    if ($isAdmin && isset($_POST['import_db'])) {
        $importFilePath = $_FILES['import_file']['tmp_name'] ?? '';
        if ($importFilePath) {
            $action = new ImportDatabase($importFilePath);
            $context->setAction($action);
            $message = $context->execute()->message;
        } else {
            $message = "Please select a SQL file to import.";
        }
    }

    if (isset($_POST['delete_user'])) {
        $action = new DeleteUser($con, $currentUser);
        $context->setAction($action);
        $context->execute(); // redirects
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
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); justify-content:center; align-items:center; }
.modal { background:#fff; padding:20px; border-radius:12px; width:300px; text-align:center; }
.modal-actions { margin-top:15px; display:flex; justify-content:space-between; }
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

    <?php if($isAdmin): ?>
        <!-- Backup database -->
        <form method="post">
            <button type="submit" name="backup_db" class="btn primary">Backup Database</button>
        </form>

        <!-- Import database -->
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="import_file" accept=".sql">
            <button type="submit" name="import_db" class="btn primary">Import Database</button>
        </form>
    <?php endif; ?>

    <!-- Delete user -->
    <!-- <button class="btn danger" id="deleteBtn">Delete Account</button> -->

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

deleteBtn.addEventListener("click", () => {
    modalOverlay.style.display = "flex";
});

cancelDelete.addEventListener("click", () => {
    modalOverlay.style.display = "none";
});

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
