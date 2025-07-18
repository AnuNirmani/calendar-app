<?php
// auth.php - Place this in your root directory

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAuth($requiredRole = null) {
    // Check if user is logged in
    if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header("Location: ../login.php");
        exit;
    }
    
    // If a specific role is required, check it
    if ($requiredRole !== null) {
        if ($_SESSION['role'] !== $requiredRole) {
            // If not super admin and trying to access super admin only features
            if ($requiredRole === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
                header("Location: index.php?error=access_denied");
                exit;
            }
        }
    }
    
    return true;
}

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
}

function getCurrentUserId() {
    global $conn;
    if (!isset($_SESSION['username'])) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return $user['id'];
    }
    
    return null;
}
?>