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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>

<body class="admin-page">
    <h2>✨ Add New Special Date</h2>

    <form action="save.php" method="POST" class="add-form">
        <label for="date">📅 Date:</label>
        <input type="date" name="date" required>

        <label for="type_id">🏷️ Type:</label>
        <select name="type_id" required>
            <option value="">-- Select Type --</option>
            <?php while($row = $types->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['type']) ?> — <?= htmlspecialchars($row['description']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="color">🎨 Color:</label>
        <input type="color" name="color" value="#ff6b9d" required>

        <button type="submit">💾 Add Date</button>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important; color: white !important; padding: 12px 25px !important; border-radius: 25px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; margin: 0 !important; display: inline-block !important; transition: all 0.3s ease !important;">← Back to Admin</a>
        </div>
    </form>

</body>
</html>