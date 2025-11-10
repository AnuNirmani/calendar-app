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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Special Dates</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Edit Button Styling */
        a.edit-button {
            background-color: lightblue !important;
            color: rgb(0, 0, 0) !important;
            padding: 8px 16px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            margin: 0 5px 0 0 !important;
            display: inline-block !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            border: none !important;
        }

        a.edit-button:hover {
            background-color: rgb(164, 107, 166) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 5px 15px rgb(159, 124, 160) !important;
        }

        /* Delete Button Styling */
        a.delete-button {
            background-color: navy !important;
            color: white !important;
            padding: 8px 16px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            margin: 0 !important;
            display: inline-block !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            border: none !important;
        }

        a.delete-button:hover {
            background-color: red !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 5px 15px rgba(255, 0, 0, 0.3) !important;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex min-h-screen">
    <?php 
    $base_path = '../';
    include __DIR__ . '/includes/slidebar2.php'; 
    ?>

    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"><center>✨ Admin Panel - Special Dates</center></h1>

        <!-- <a href="dashboard.php" 
           class="inline-block bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-5 py-2 rounded-full font-semibold text-sm hover:from-indigo-600 hover:to-purple-700 transition">
           <i class="fas fa-home"></i> Back to Dashboard
        </a> -->

        <?php if ($accessDeniedError): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 my-6 rounded">
                <strong>⚠️ Access Denied:</strong> You don't have permission to access that feature.
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="w-full my-6">
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <form method="GET" class="flex gap-2 flex-1 min-w-fit">
                    <input type="text" name="search" placeholder="Search by description..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none flex-1">
                    <button type="submit" class="bg-sky-500 text-white px-4 py-2 rounded-md font-semibold">🔎 Search</button>
                </form>

                <form method="GET" class="flex gap-2 flex-1 min-w-fit">
                    <select name="year" class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 flex-1">
                        <option value="">Select Year</option>
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++): ?>
                            <option value="<?= $y ?>" <?= isset($_GET['year']) && $_GET['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>

                    <select name="type" class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 flex-1">
                        <option value="">All Types</option>
                        <?php
                        $typeRes = $conn->query("SELECT id, type FROM special_types");
                        while ($row = $typeRes->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= isset($_GET['type']) && $_GET['type'] == $row['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['type']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" class="bg-sky-500 text-white px-4 py-2 rounded-md font-semibold">🎯 Filter</button>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow-md p-4">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-indigo-600 text-white text-left">
                        <th class="px-4 py-3">📅 Date</th>
                        <th class="px-4 py-3">🏷️ Type</th>
                        <th class="px-4 py-3">📝 Description</th>
                        <th class="px-4 py-3 text-center">🎨 Color</th>
                        <th class="px-4 py-3 text-center">⚡ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['date']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['joined_type'] ?? 'N/A') ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['joined_description'] ?? 'N/A') ?></td>
                        <td class="px-4 py-3 text-center">
                            <div class="w-6 h-6 mx-auto rounded-full border" style="background: <?= htmlspecialchars($row['color']) ?>"></div>
                        </td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="edit-button">✏️ Edit</a>
                            <a href="?delete=<?= $row['id'] ?>" class="delete-button" onclick="return confirm('⚠️ Are you sure you want to delete this date?')">🗑️ Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- <div class="mt-6">
            <span class="text-lg font-semibold text-gray-700">
                <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
            <a href="../logout.php" class="ml-4 bg-red-500 text-white px-4 py-2 rounded-full font-semibold hover:bg-red-600">🚪 Logout</a>
        </div> -->

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
