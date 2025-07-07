<?php
include '../db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $desc = $_POST['description'];

    if (!$date || !$type) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO special_dates (date, type, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $date, $type, $desc);
        $stmt->execute();
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Special Date</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h2>➕ Add New Special Date</h2>
    <form method="POST" action="">
        <p>
            <label>Date: <input type="date" name="date" required></label>
        </p>
        <p>
            <label>Type:
                <select name="type" required>
                    <option value="">-- Select --</option>
                    <option value="holiday">Holiday</option>
                    <option value="poya">Poya</option>
                </select>
            </label>
        </p>
        <p>
            <label>Description: <input type="text" name="description" placeholder="Optional"></label>
        </p>
        <p>
            <button type="submit">Add Date</button>
            <a href="index.php">← Back</a>
        </p>
        <?php if ($error): ?>
            <p style="color:red;"><?= $error ?></p>
        <?php endif; ?>
    </form>
</body>
</html>
