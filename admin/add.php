<?php
include '../db.php';

// Fetch special types
$types = $conn->query("SELECT id, type, description FROM special_types");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Special Date</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-page">
    <h2>➕ Add New Special Date</h2>

    <form action="save.php" method="POST" class="add-form">
    <label for="date">Date:</label>
    <input type="date" name="date" required>

    <label for="type_id">Type:</label>
    <select name="type_id" required>
        <option value="">-- Select --</option>
        <?php while($row = $types->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>">
                <?= htmlspecialchars($row['type']) ?> — <?= htmlspecialchars($row['description']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="color">Color:</label>
    <input type="color" name="color" value="#ff0000" required>

    <button type="submit">Add Date</button>
    <a href="index.php">← Back</a>
</form>

</body>
</html>
