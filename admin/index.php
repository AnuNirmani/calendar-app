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
//$result = $conn->query("SELECT * FROM special_dates ORDER BY date DESC");
// $result = $conn->query("
//     SELECT sd.id, sd.date, sd.color, st.type, st.description
//     FROM special_dates sd
//     JOIN special_types st ON sd.type_id = st.id
//     ORDER BY sd.date ASC
// ");

$result = $conn->query("
    SELECT 
        sd.id, sd.date, sd.color, sd.type_id, 
        st.type AS joined_type, st.description AS joined_description
    FROM 
        special_dates sd
    LEFT JOIN 
        special_types st ON sd.type_id = st.id
    ORDER BY 
        sd.date DESC
");


?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Special Dates</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>
<body class="admin-page">

    <h2>✨ Admin Panel - Special Dates</h2>

    <div class="special-dates-table">
        <div style="text-align: left; margin-bottom: 25px;">

            <a href="add.php" style="background: linear-gradient(135deg,lightblue 0%,navy 100%) !important; 
            color: white !important; 
            padding: 12px 25px !important; 
            border-radius: 25px !important; 
            font-weight: 600 !important; 
            text-transform: uppercase !important; 
            letter-spacing: 0.5px !important; 
            margin: 0 !important; 
            display: inline-block !important; 
            transition: all 0.3s ease !important;">➕ Add New Special Date</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>📅 Date</th>
                    <th>🏷️ Type</th>
                    <th>📝 Description</th>
                    <th>🎨 Color</th>
                    <th>⚡ Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td style="font-weight: 600;"><?= htmlspecialchars($row['date']) ?></td>
        <td style="font-weight: 600;"><?= htmlspecialchars($row['joined_type'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($row['joined_description'] ?? 'N/A') ?></td>
        <td style="text-align: center;">
            <div style="width: 30px; height: 30px; background: <?= htmlspecialchars($row['color']) ?>; border-radius: 50%; margin: auto; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></div>
        </td>
        <td>
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('⚠️ Are you sure you want to delete this date?')">
                🗑️ Delete
            </a>
        </td>
    </tr>
<?php endwhile; ?>

            </tbody>
        </table>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="../index.php" class="go-calendar">📅 Go to Calendar</a>
        <a href="../home.php" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important; color: white !important; padding: 15px 30px !important; border-radius: 25px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; margin: 0 10px !important; display: inline-block !important; transition: all 0.3s ease !important;">🏠 Home</a>
    </div>

</body>
</html>