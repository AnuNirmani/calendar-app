<?php
include '../db.php';

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM special_dates WHERE id = $id");
    header("Location: index.php");
    exit;
}

// Fetch all special dates
$result = $conn->query("SELECT * FROM special_dates ORDER BY date ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Special Dates</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="images/logo.jpg" type="image/png">
</head>
<body class="admin-page">

    <h2>📅 Admin Panel - Special Dates</h2>

    <div class="special-dates-table">
        <div style="text-align: left; margin-bottom: 20px;">
            <a href="add.php">➕ Add New Special Date</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['type']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this date?')">
                                🗑 Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
