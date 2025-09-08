<?php
include '../db.php';
include '../auth.php';
checkAuth();

// Auto logout after inactivity
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Get ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM special_dates WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// Fetch types
$types = $conn->query("SELECT id, type FROM special_types");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $type_id = (int)$_POST['type_id'];
    $color = $_POST['color'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE special_dates SET date=?, type_id=?, color=?, description=? WHERE id=?");
    $stmt->bind_param("sissi", $date, $type_id, $color, $description, $id);
    $stmt->execute();

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Edit Special Date</title>
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>
<body class="admin-page">
    <div style="text-align: center; margin-bottom: 30px;">
                <a href="index.php" style="background: #1976d2; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; position: absolute; left: 0; font-weight: 600;">
            ← Back
        </a>
        <h1 style="font-size: 28px;">✏️ Edit Special Date</h1>
    </div>

    <form method="POST" style="max-width: 1000px; margin: auto;">
        <label>Date:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($data['date']) ?>" required>

        <label>Type:</label>
        <select name="type_id" required>
            <option value="">-- Select Type --</option>
            <?php while($row = $types->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= $row['id'] == $data['type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['type']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Description:</label>
        <input type="text" name="description" value="<?= htmlspecialchars($data['description']) ?>" placeholder="Enter description" required>

        <label>🎨 Optional Color:</label>
        <div style="display: flex; gap: 20px; margin: 10px 0;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#ff0000" <?= $data['color'] === '#ff0000' ? 'checked' : '' ?> required>
                <span style="width: 20px; height: 20px; background: #ff0000; border-radius: 50%; border: 1px solid #333;"></span>
                Mercantile Holiday
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#ffea00" <?= $data['color'] === '#ffea00' ? 'checked' : '' ?> required>
                <span style="width: 20px; height: 20px; background: #ffea00; border-radius: 50%; border: 1px solid #333;"></span>
                Poya Day
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#dbdbdbff" <?= $data['color'] === '#dbdbdbff' ? 'checked' : '' ?> required>
                <span style="width: 20px; height: 20px; background: #dbdbdbff; border-radius: 50%; border: 1px solid #333;"></span>
                Other
            </label>
        </div>

        <div>
            <button type="submit" style="margin-top: 20px;">💾 Save Changes</button>
        </div>
    </form>

    <div style="margin-top: 20px; text-align: center;">
        <span style="color: white; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <footer class="footer">
        © <?= date('Y'); ?> Developed and Maintained by Web Publishing Department in collaboration with WNL Time Office<br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>
</body>
</html>
