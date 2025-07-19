<?php
// update_passwords.php
include 'db.php';

$users = [
    ['username' => 'superadmin', 'password' => 'super123'],
    ['username' => 'admin1', 'password' => 'admin123']
];

foreach ($users as $user) {
    $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashedPassword, $user['username']);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo "Updated password for {$user['username']}\n";
    } else {
        echo "No update needed for {$user['username']}\n";
    }
}

echo "Password update complete.\n";
?>