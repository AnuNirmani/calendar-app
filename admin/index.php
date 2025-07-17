<?php
include '../db.php';
include '../auth.php';

// Check if user is authenticated (both admin and super_admin can access)
checkAuth();

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM special_dates WHERE id = $id");
    header("Location: index.php");
    exit;
}

// Handle access denied error
$accessDeniedError = isset($_GET['error']) && $_GET['error'] === 'access_denied';

$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$result = $conn->query("
    SELECT 
        sd.id, sd.date, sd.color, 
        st.type AS joined_type, 
        st.description AS joined_description 
    FROM 
        special_dates sd 
    LEFT JOIN 
        special_types st ON sd.type_id = st.id 
    WHERE 
        YEAR(sd.date) = $currentYear
    ORDER BY sd.date DESC
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

    <div style="text-align: center; margin-bottom: 30px;">
    <h1 style="font-size: 28px;">✨ Admin Panel - Special Dates</h1>
    </div>


    <?php if ($accessDeniedError): ?>
        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f44336;">
            <strong>⚠️ Access Denied:</strong> You don't have permission to access that feature.
        </div>
    <?php endif; ?>

    <div class="special-dates-table">
        <!-- <div style="text-align: center; margin-bottom: 25px; display: flex; gap: 15px; align-items: center;"> -->
             <div style="text-align: center; margin-bottom: 25px; display: flex; gap: 15px; justify-content: center; align-items: center; ">

            <a href="add.php" style="background: linear-gradient(135deg,#2196f3 0%,#1976d2 100%) !important; 
            color: white !important; 
            padding: 12px 25px !important; 
            border-radius: 25px !important; 
            font-weight: 600 !important; 
            text-transform: uppercase !important; 
            letter-spacing: 0.5px !important; 
            margin: 0 !important; 
            display: inline-block !important; 
            transition: all 0.3s ease !important;">➕ Add New Special Date</a>

            <?php if (isSuperAdmin()): ?>
                <a href="manage_users.php" style="background: linear-gradient(135deg, #2196f3 0%, #6f38bcff 100%) !important; 
                color: white !important; 
                padding: 12px 25px !important; 
                border-radius: 25px !important; 
                font-weight: 600 !important; 
                text-transform: uppercase !important; 
                letter-spacing: 0.5px !important; 
                margin: 0 !important; 
                display: inline-block !important; 
                transition: all 0.3s ease !important;">👥 Manage Users</a>
            <?php endif; ?>
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
                    <td><?= htmlspecialchars($row['joined_type'] ?? 'N/A') ?></td>
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

        <!-- pagination part -->
        <?php
        $currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

        // Get all distinct years from DB
        $allYears = [];
        $yearsResult = $conn->query("SELECT DISTINCT YEAR(date) AS year FROM special_dates ORDER BY year DESC");
        while ($row = $yearsResult->fetch_assoc()) {
            $allYears[] = (int)$row['year'];
        }

        // Filter only previous, current, and next year
        $filteredYears = array_filter($allYears, function($year) use ($currentYear) {
            return ($year >= $currentYear - 1 && $year <= $currentYear + 1);
        });
        ?>

        <div style="margin-top: 30px; text-align: center;">
            <?php foreach ($filteredYears as $year): ?>
                <a href="?year=<?= $year ?>" 
                   class="button" 
                   style="<?= ($year == $currentYear) ? 'background: #007bff; color: white;' : 'background: #f1f1f1; color: #000;' ?> 
                          padding: 10px 20px; 
                          margin: 5px; 
                          border-radius: 30px; 
                          font-weight: bold; 
                          text-decoration: none; 
                          display: inline-block;">
                    <?= $year ?>
                </a>
            <?php endforeach; ?>
        </div>

    </div> <!-- closes .special-dates-table -->


    <div style="margin-top: 10px;">
        <span style="background: <?= isSuperAdmin() ?>; color: white; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>


    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Developed and Maintained by Web Publishing Department in collaboration with WNL Time Office<br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>

</body>
</html>