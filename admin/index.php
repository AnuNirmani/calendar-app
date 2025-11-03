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

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM special_dates WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// Handle access denied error
$accessDeniedError = isset($_GET['error']) && $_GET['error'] === 'access_denied';

$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Base query
$query = "
    SELECT 
        sd.id, sd.date, sd.color, 
        st.type AS joined_type, 
        sd.description AS joined_description 
    FROM 
        special_dates sd 
    LEFT JOIN 
        special_types st ON sd.type_id = st.id 
    WHERE 
        YEAR(sd.date) = $currentYear
";

// Bind filter parameters
$conditions = [];
$params = [];
$types = "";

// Search by description
if (!empty($_GET['search'])) {
    $conditions[] = "sd.description LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
    $types .= "s";
}

// Filter by year
if (!empty($_GET['year'])) {
    $conditions[] = "YEAR(sd.date) = ?";
    $params[] = $_GET['year'];
    $types .= "i";
}

// Filter by type
if (!empty($_GET['type'])) {
    $conditions[] = "sd.type_id = ?";
    $params[] = $_GET['type'];
    $types .= "i";
}

// Add conditions to query
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY sd.date DESC";

// Prepare and bind
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Special Dates</title>
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>
<body class="admin-page">

    <div style="text-align: center; margin-bottom: 30px;">
    <h1 style="font-size: 28px;">✨ Admin Panel - Special Dates</h1>
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

    <div style="margin-top: 35px;">
        <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
            <!-- 🔍 Description Search Form -->
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="search" placeholder="Search by description..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                       style="padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; width: 220px;">
                <button type="submit" style="padding: 10px 25px; background: #03a9f4; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    🔎 Search
                </button>
            </form>

            <!-- 🎯 Year + Type Filter Form -->
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <select name="year" style="padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px;">
                    <option value="">Select Year</option>
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++): ?>
                        <option value="<?= $y ?>" <?= isset($_GET['year']) && $_GET['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <select name="type" style="padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px;">
                    <option value="">All Types</option>
                    <?php
                    $typeRes = $conn->query("SELECT id, type FROM special_types");
                    while ($row = $typeRes->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= isset($_GET['type']) && $_GET['type'] == $row['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['type']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" style="padding: 10px 25px; background: #03a9f4; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    🎯 Filter
                </button>
            </form>
        </div>

    <?php if ($accessDeniedError): ?>
        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f44336;">
            <strong>⚠️ Access Denied:</strong> You don't have permission to access that feature.
        </div>
    <?php endif; ?>

    <div class="special-dates-table">
        <!-- <div style="text-align: center; margin-bottom: 25px; display: flex; gap: 15px; justify-content: center; align-items: center;">
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
        </div> -->

        <table>
            <thead>
                <tr>
                    <th class="col-date">📅 Date</th>
                    <th class="col-type">🏷️ Type</th>
                    <th class="col-description">📝 Description</th>
                    <th class="col-color">🎨 Color</th>
                    <th class="col-actions">⚡ Action</th>
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
                    <a href="edit.php?id=<?= $row['id'] ?>" class="edit-button">✏️ Edit</a>
                    <a href="?delete=<?= $row['id'] ?>" class="delete-button" onclick="return confirm('⚠️ Are you sure you want to delete this date?')">🗑️ Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

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
    </div>

    

        <div style="margin-top: 10px;">
            <span style="background: <?= isSuperAdmin() ?>; color: navy; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
                <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
                🚪 Logout
            </a>
        </div>

<div class="footer-divider"></div>
<?php include 'includes/footer.php'; ?>