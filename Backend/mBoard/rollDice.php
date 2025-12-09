<?php
header('Content-Type: application/json');

// Compile dice.cpp if necessary
if (!file_exists("dice") || filemtime("dice.cpp") > filemtime("dice")) {
    $compile = shell_exec("g++ dice.cpp -o dice 2>&1");
    if (!file_exists("dice")) {
        echo json_encode(["error" => "Compilation failed", "details" => $compile]);
        exit;
    }
}

// Run the dice program
$output = shell_exec("./dice 2>&1");

// Try to decode and return as JSON
if ($output) {
    echo $output;
} else {
    echo json_encode(["error" => "Failed to roll dice"]);
}
?>
