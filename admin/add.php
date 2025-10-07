<?php
include '../db.php';
include '../auth.php';

// Check if user is authenticated (both admin and super_admin can access)
checkAuth();

// Auto logout after inactivity
$timeout = 900; // 15 minutes = 900 seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php"); // or "login.php" depending on path
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Fetch special types
$types = $conn->query("SELECT id, type FROM special_types");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Add Special Date</title>
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>

<body class="admin-page">
    <div style="text-align: center; margin-bottom: 30px;">
        <!-- <a href="dashboard.php" style="background: #667eea; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; position: absolute; left: 0; font-weight: 600;">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="index.php" style="background: #1976d2; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; position: absolute; left: 140px; font-weight: 600;">
            ← Back
        </a> -->
    <h1 style="font-size: 28px;">➕ Add New Special Date</h1>
    <a href="dashboard.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
        color: white !important; 
        padding: 10px 20px !important; 
        border-radius: 20px !important; 
        font-weight: 600 !important; 
        text-transform: uppercase !important; 
        letter-spacing: 0.5px !important; 
        margin: 10px !important; 
        display: inline-block !important; 
        transition: all 0.3s ease !important;
        font-size: 14px !important;">
        <i class="fas fa-home"></i> Back to Dashboard
    </a>
    </div>

    <form action="save.php" method="POST" class="add-form">
        <label for="date">📅 Date:</label>
        <input type="date" name="date" required>

        <label for="type_id">🏷️ Type:</label>
        <select name="type_id" required>
            <option value="">-- Select Type --</option>
            <?php while($row = $types->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['type']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="description">📝 Description:</label>
        <input type="text" name="description" placeholder="Enter description" required>

        <label>🎨 Optional Color:</label>
        <div style="display: flex; gap: 20px; margin: 10px 0;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#ff0000" required>
                <span style="width: 20px; height: 20px; background: #ff0000; border-radius: 50%; border: 1px solid #333;"></span>
                Mercantile Holiday
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#ffea00" required>
                <span style="width: 20px; height: 20px; background: #ffea00; border-radius: 50%; border: 1px solid #333;"></span>
                Poya Day
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#dbdbdbff" required>
                <span style="width: 20px; height: 20px; background: #dbdbdbff; border-radius: 50%; border: 1px solid #333;"></span>
                Other
            </label>
        </div>

        <button type="submit">💾 Add Date</button>
    </form>

    <div style="margin-top: 10px;">
        <span style="color: white; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <footer class="footer">
        © <?php echo date('Y'); ?> Developed and Maintained by Web Publishing Department in collaboration with WNL Time Office<br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>
</body>
</html>