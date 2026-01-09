<?php
include '../db.php';
include '../auth.php';

checkAuth('super_admin');

// Auto logout after inactivity
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];

    if ($id == $_SESSION['user_id']) {
        $error = "You cannot deactivate your own account!";
    } else {
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultStatus = $stmt->get_result();
        $user = $resultStatus->fetch_assoc();

        if (!$user) {
            $error = "User not found!";
        } else {
            $new_status = ((int)$user['status'] === 1) ? 0 : 1;

            $stmt = $conn->prepare("UPDATE users SET status = ?, edited_by = ?, edited_at = NOW() WHERE id = ?");
            $stmt->bind_param("iii", $new_status, $_SESSION['user_id'], $id);
            $stmt->execute();

            $status_text = ($new_status === 1) ? "activated" : "deactivated";
            $success = "User " . $status_text . " successfully!";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "User deleted successfully!";
    }
}

// Fetch users
$result = $conn->query("
    SELECT 
        u.id, u.username, u.role, u.status, u.created_at,
        creator.username as created_by_username,
        editor.username as edited_by_username,
        u.edited_at
    FROM users u
    LEFT JOIN users creator ON u.created_by = creator.id
    LEFT JOIN users editor ON u.edited_by = editor.id
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Match your UI button styling */
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
            white-space: nowrap;
        }
        a.edit-button:hover {
            background-color: rgb(164, 107, 166) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 5px 15px rgb(159, 124, 160) !important;
        }

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
            white-space: nowrap;
        }
        a.delete-button:hover {
            background-color: red !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 5px 15px rgba(255, 0, 0, 0.3) !important;
        }

        .status-button {
            padding: 8px 14px;
            border-radius: 999px;
            color: white;
            font-weight: 700;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .status-active { background: linear-gradient(135deg, #4caf50, #45a049); }
        .status-active:hover { background: linear-gradient(135deg, #f44336, #f44336); transform: translateY(-1px); }

        .status-inactive { background: linear-gradient(135deg, #f44336, #e53935); }
        .status-inactive:hover { background: linear-gradient(135deg, #4caf50, #45a049); transform: translateY(-1px); }
    </style>
</head>

<body class="bg-gray-100 font-sans">
<div class="flex min-h-screen">
    <?php
    $base_path = '../';
    include __DIR__ . '/includes/slidebar2.php';
    ?>

    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">✨ Admin Panel - Manage Users</h1>

        <div class="flex justify-center mb-6">
            <a href="dashboard.php"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-5 py-2 rounded-full font-semibold text-sm hover:from-indigo-600 hover:to-purple-700 transition">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 my-4 rounded">
                <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 my-4 rounded">
                <strong>✅ Success:</strong> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto bg-white rounded-lg shadow-md p-4">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-indigo-600 text-white text-left">
                        <th class="px-4 py-3">👤 Username</th>
                        <th class="px-4 py-3">🏷️ Role</th>
                        <th class="px-4 py-3">🔄 Status</th>
                        <th class="px-4 py-3">📅 Created At</th>
                        <th class="px-4 py-3">👨‍💼 Created By</th>
                        <th class="px-4 py-3">✏️ Edited At</th>
                        <th class="px-4 py-3">🧑 Edited By</th>
                        <th class="px-4 py-3 text-center">⚡ Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($row['username']) ?></td>
                        <td class="px-4 py-3"><?= $row['role'] === 'super_admin' ? '👑 Super Admin' : '👤 Admin' ?></td>

                        <td class="px-4 py-3">
                            <?php if ((int)$row['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="?toggle_status=<?= (int)$row['id'] ?>"
                                   class="status-button <?= ((int)$row['status'] === 1) ? 'status-active' : 'status-inactive' ?>"
                                   onclick="return confirm('Are you sure you want to <?= ((int)$row['status'] === 1) ? 'deactivate' : 'activate' ?> this user?')">
                                    <?= ((int)$row['status'] === 1) ? '✅ Active' : '❌ Inactive' ?>
                                </a>
                            <?php else: ?>
                                <span class="status-button status-active" style="cursor:not-allowed; opacity:0.7;">
                                    ✅ Active
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['created_by_username'] ?? 'System') ?></td>
                        <td class="px-4 py-3"><?= $row['edited_at'] ? date('M j, Y', strtotime($row['edited_at'])) : '-' ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($row['edited_by_username'] ?? '-') ?></td>

                        <td class="px-4 py-3 text-center">
                            <?php if ((int)$row['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="edit_user.php?id=<?= (int)$row['id'] ?>" class="edit-button">✏️ Edit</a>
                                <a href="?delete=<?= (int)$row['id'] ?>" class="delete-button"
                                   onclick="return confirm('⚠️ Are you sure you want to delete this user?')">
                                   🗑️ Delete
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    // Auto-hide success/error messages
    setTimeout(() => {
        document.querySelectorAll('.bg-red-100, .bg-green-100').forEach(el => el.style.display = 'none');
    }, 2000);
</script>

</body>
</html>
