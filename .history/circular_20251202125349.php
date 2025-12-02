<?php
include 'db.php';

// Pagination configuration
$records_per_page = 9; // 3x3 grid

// Get current page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Calculate offset
$offset = ($current_page - 1) * $records_per_page;

// Initialize search and date filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date = isset($_GET['date']) ? trim($_GET['date']) : '';

// Build count query for total records
$count_sql = "SELECT COUNT(*) as total FROM posts WHERE status = 'published'";

// Build base query for fetching data - check if featured_image column exists
$sql = "SELECT id, featured_image, publish_date, title, content FROM posts WHERE status = 'published'";

// Add search conditions if provided
if (!empty($search)) {
    $search_clean = $conn->real_escape_string($search);
    $sql .= " AND (title LIKE '%$search_clean%' OR content LIKE '%$search_clean%')";
    $count_sql .= " AND (title LIKE '%$search_clean%' OR content LIKE '%$search_clean%')";
}

// Add date filter if provided
if (!empty($filter_date)) {
    $date_clean = $conn->real_escape_string($filter_date);
    $sql .= " AND DATE(publish_date) = '$date_clean'";
    $count_sql .= " AND DATE(publish_date) = '$date_clean'";
}

// Complete the SQL queries
$sql .= " ORDER BY publish_date DESC LIMIT $offset, $records_per_page";

// Initialize total_pages to avoid undefined variable errors
$total_pages = 1;
$total_records = 0;

// Get total records
$count_result = $conn->query($count_sql);
if (!$count_result) {
    echo "Count query failed: " . $conn->error;
    $total_records = 0;
} else {
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row['total'];
}

$total_pages = $total_records > 0 ? ceil($total_records / $records_per_page) : 1;

// Ensure current page is within valid range
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Fetch posts
$posts = [];
$result = $conn->query($sql);
if (!$result) {
    echo "Query failed: " . $conn->error;
} else {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

// Fetch telephone directory entries
$phone_search = isset($_GET['phone_search']) ? trim($_GET['phone_search']) : '';
$phone_entries = [];
$phone_total_records = 0;

if (!empty($phone_search)) {
    $phone_search_clean = $conn->real_escape_string($phone_search);
    $phone_sql = "SELECT DISTINCT td.id, td.name, td.phone_number, td.email, td.extension, d.name AS department_name 
                  FROM Telephone_Directory td 
                  LEFT JOIN Department d ON td.department_id = d.id
                  WHERE td.name LIKE '%$phone_search_clean%' 
                     OR td.phone_number LIKE '%$phone_search_clean%' 
                     OR td.email LIKE '%$phone_search_clean%' 
                     OR td.extension LIKE '%$phone_search_clean%' 
                     OR d.name LIKE '%$phone_search_clean%'
                  ORDER BY td.name ASC";
    
    $phone_result = $conn->query($phone_sql);
    if ($phone_result) {
        while ($row = $phone_result->fetch_assoc()) {
            $phone_entries[] = $row;
        }
        $phone_total_records = count($phone_entries);
    }
} else {
    // Get limited entries for initial display
    $phone_sql = "SELECT DISTINCT td.id, td.name, td.phone_number, td.email, td.extension, d.name AS department_name 
                  FROM Telephone_Directory td 
                  LEFT JOIN Department d ON td.department_id = d.id
                  ORDER BY td.name ASC 
                  LIMIT 20";
    
    $phone_result = $conn->query($phone_sql);
    if ($phone_result) {
        while ($row = $phone_result->fetch_assoc()) {
            $phone_entries[] = $row;
        }
        $phone_total_records = count($phone_entries);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <center> 
        <img src="images/logo.jpg" style="width: 250px;"> 
    </center>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wijeya Newspapers - Internal Communications</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="images/logo.jpg" type="image/png">
    <style>
        :root {
            --wijeya-primary: #87CEEB; /* Sky Blue */
            --wijeya-dark: #4682B4; /* Steel Blue */
            --wijeya-light: #E6F2F8; /* Light Sky Blue */
        }
        body {
            font-family: 'Georgia', serif;
        }
        .navbar {
            background-color: var(--wijeya-primary) !important;
            border-bottom: 3px solid var(--wijeya-dark);
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #fff !important;
        }
        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
        }
        .circulation-card {
            transition: transform 0.3s;
            border-left: 4px solid transparent;
            height: 100%;
        }
        .circulation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .unread {
            border-left: 4px solid var(--wijeya-primary);
        }
        .btn-wijeya {
            background-color: var(--wijeya-primary);
            color: white;
            border: none;
        }
        .btn-wijeya:hover {
            background-color: var(--wijeya-dark);
            color: white;
        }
        .badge-wijeya {
            background-color: var(--wijeya-dark);
        }
        footer {
            background-color: var(--wijeya-dark);
            color: white;
            padding: 2rem 0;
        }
        footer a {
            color: #ddd;
            text-decoration: none;
        }
        footer a:hover {
            color: white;
        }
        .page-item.active .page-link {
            background-color: var(--wijeya-primary);
            border-color: var(--wijeya-primary);
        }
        .page-link {
            color: var(--wijeya-primary);
        }
        .footer-heading {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: white;
            font-weight: 600;
        }
        .footer-logo {
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
            display: block;
        }
        .footer-divider {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin: 1.5rem 0;
        }
        .footer-contact {
            line-height: 1.8;
        }
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem 1rem;
        }
        .circular-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--wijeya-primary);
            margin-bottom: 1rem;
        }
        .btn-outline-wijeya {
            color: var(--wijeya-primary);
            border-color: var(--wijeya-primary);
        }
        .btn-outline-wijeya:hover {
            color: white;
            background-color: var(--wijeya-primary);
        }
        .search-container {
            position: relative;
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        .search-input {
            padding-left: 45px;
        }
        .search-input:focus {
            border-color: var(--wijeya-primary);
            box-shadow: 0 0 0 0.2rem rgba(135, 206, 235, 0.25);
        }
        
        /* Enhanced card styling for Circular 1 */
        .enhanced-card {
            overflow: hidden;
        }
        .enhanced-card .card-body {
            padding: 0;
            display: block;
        }
        .circular-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .card-content {
            padding: 1.25rem;
            text-align: left;
        }
        .circular-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--wijeya-primary);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        .circular-description {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.4;
            margin-bottom: 1rem;
        }
        .circular-date {
            font-size: 0.75rem;
            color: #999;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        .enhanced-card .btn-wijeya {
            width: 100%;
            padding: 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Telephone Directory Styles */
        .nav-tabs .nav-link {
            color: #666;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border: none;
            border-bottom: 3px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: var(--wijeya-primary);
            background-color: transparent;
            border-bottom: 3px solid var(--wijeya-primary);
        }
        .nav-tabs .nav-link:hover {
            color: var(--wijeya-dark);
            border-bottom: 3px solid var(--wijeya-dark);
        }
        .phone-table {
            width: 100%;
            border-collapse: collapse;
        }
        .phone-table th {
            background-color: var(--wijeya-light);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--wijeya-dark);
            border-bottom: 2px solid var(--wijeya-primary);
        }
        .phone-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .phone-table tr:hover {
            background-color: #f8f9fa;
        }
        .phone-table .phone-number {
            font-family: monospace;
            font-size: 1.05em;
            color: #333;
        }
        .phone-table .email-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .phone-search-container {
            position: relative;
            margin-bottom: 20px;
        }
        .phone-search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--wijeya-primary);
            pointer-events: none;
        }
        .phone-search-input {
            padding-left: 45px;
            border-radius: 25px;
            border: 2px solid var(--wijeya-light);
        }
        .phone-search-input:focus {
            border-color: var(--wijeya-primary);
            box-shadow: 0 0 0 0.2rem rgba(135, 206, 235, 0.25);
        }
        .tab-pane {
            padding-top: 20px;
        }
        .phone-results-count {
            background-color: var(--wijeya-light);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: var(--wijeya-dark);
            font-size: 0.9em;
        }
        .department-badge {
            background-color: var(--wijeya-primary);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 500;
        }
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .no-results i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                WIJEYA NEWSPAPERS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="circulars-tab" data-bs-toggle="tab" data-bs-target="#circulars" type="button" role="tab" aria-controls="circulars" aria-selected="true">
                    <i class="fas fa-newspaper me-2"></i>Circulars
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="directory-tab" data-bs-toggle="tab" data-bs-target="#directory" type="button" role="tab" aria-controls="directory" aria-selected="false">
                    <i class="fas fa-phone-alt me-2"></i>Telephone Directory
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            <!-- Circulars Tab -->
            <div class="tab-pane fade show active" id="circulars" role="tabpanel" aria-labelledby="circulars-tab">
                <div class="row mb-4 mt-4">
                    <div class="col-md-6">
                        <h2>Circulars</h2>
                    </div>
                </div>
                
                <!-- Search and Date Filter Section -->
                <form method="GET" action="" class="row mb-4">
                    <input type="hidden" name="tab" value="circulars">
                    <div class="col-md-8">
                        <label class="form-label">Search</label>
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control search-input" placeholder="Search by circular title or content...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" class="form-control">
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-wijeya"><i class="fas fa-search me-2"></i>Search</button>
                        <?php if (!empty($search) || !empty($filter_date)): ?>
                            <a href="circular.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Circulations List - 3x3 Grid -->
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php
                    if (!empty($posts)) {
                        foreach ($posts as $row) {
                            // Format the date
                            $formatted_date = date("F j, Y", strtotime($row["publish_date"]));
                            
                            // Truncate content for description
                            $description = substr(strip_tags($row["content"]), 0, 120) . "...";
                            
                            // Use featured image or fallback to logo
                            $image_path = !empty($row["featured_image"]) ? $row["featured_image"] : 'images/logo.jpg';
                            
                            echo '
                            <div class="col">
                                <div class="card h-100 circulation-card enhanced-card">
                                    <img src="' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($row["title"]) . '" class="circular-image" onerror="this.src=\'images/logo.jpg\'">
                                    <div class="card-content">
                                        <div class="circular-date">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            ' . $formatted_date . '
                                        </div>
                                        <h6 class="circular-title">' . htmlspecialchars($row["title"]) . '</h6>
                                        <p class="circular-description">
                                            ' . htmlspecialchars($description) . '
                                        </p>
                                        <a href="circular_detail.php?id=' . $row["id"] . '" class="btn btn-wijeya">View Details</a>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<div class="col-12"><p class="text-center">No circulars found.</p></div>';
                    }
                    ?>
                </div>
               
                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">Previous</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">First</span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Previous</span>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $current_page): ?>
                                <li class="page-item active"><span class="page-link"><?php echo $i; ?></span></li>
                            <?php else: ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>"><?php echo $i; ?></a></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">Last</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">Next</span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Last</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>

            <!-- Telephone Directory Tab -->
            <div class="tab-pane fade" id="directory" role="tabpanel" aria-labelledby="directory-tab">
                <div class="row mb-4 mt-4">
                    <div class="col-md-6">
                        <h2>Telephone Directory</h2>
                    </div>
                </div>
                
                <!-- Telephone Directory Search -->
                <form method="GET" action="" class="mb-4">
                    <input type="hidden" name="tab" value="directory">
                    <div class="phone-search-container">
                        <i class="fas fa-search phone-search-icon"></i>
                        <input type="text" name="phone_search" value="<?php echo htmlspecialchars($phone_search); ?>" class="form-control phone-search-input" placeholder="Search by name, phone number, email, extension, or department...">
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-wijeya"><i class="fas fa-search me-2"></i>Search</button>
                        <?php if (!empty($phone_search)): ?>
                            <a href="circular.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Clear Search</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Results Summary -->
                <?php if (!empty($phone_search) || !empty($phone_entries)): ?>
                    <div class="phone-results-count">
                        <?php if ($phone_total_records > 0): ?>
                            <i class="fas fa-info-circle me-2"></i>
                            <?php if (!empty($phone_search)): ?>
                                Found <?php echo $phone_total_records; ?> result(s) for "<strong><?php echo htmlspecialchars($phone_search); ?></strong>"
                            <?php else: ?>
                                Showing <?php echo min($phone_total_records, 20); ?> entries (<?php echo $phone_total_records; ?> total)
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle me-2"></i>
                            No entries found for "<strong><?php echo htmlspecialchars($phone_search); ?></strong>"
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Telephone Directory Table -->
                <div class="table-responsive">
                    <?php if (!empty($phone_entries)): ?>
                        <table class="phone-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone Number</th>
                                    <th>Email</th>
                                    <th>Extension</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($phone_entries as $entry): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars($entry['name']); ?></td>
                                        <td class="phone-number">
                                            <a href="tel:<?php echo htmlspecialchars($entry['phone_number']); ?>" class="text-decoration-none text-dark">
                                                <i class="fas fa-phone-alt me-2 text-success"></i>
                                                <?php echo htmlspecialchars($entry['phone_number']); ?>
                                            </a>
                                        </td>
                                        <td class="email-cell">
                                            <?php if (!empty($entry['email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($entry['email']); ?>" class="text-decoration-none">
                                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($entry['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($entry['extension'])): ?>
                                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($entry['extension']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($entry['department_name'])): ?>
                                                <span class="department-badge"><?php echo htmlspecialchars($entry['department_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (empty($phone_search) && $phone_total_records > 20): ?>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Showing 20 most recent entries. Use the search to find specific contacts.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fas fa-address-book"></i>
                            <h4 class="mt-3">No contacts found</h4>
                            <p class="text-muted">Try searching for a name, phone number, or department</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="copyright-footer">
        <div class="container">
            <p>© Copyright WNL. All Rights Reserved</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle tab persistence on page reload
        document.addEventListener('DOMContentLoaded', function() {
            // Get the tab parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam === 'directory') {
                // Switch to directory tab
                const directoryTab = new bootstrap.Tab(document.getElementById('directory-tab'));
                directoryTab.show();
            } else {
                // Default to circulars tab
                const circularsTab = new bootstrap.Tab(document.getElementById('circulars-tab'));
                circularsTab.show();
            }
            
            // Update URL when tab changes
            const tabTriggers = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabTriggers.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (event) {
                    const activeTab = event.target.getAttribute('id');
                    const tabValue = activeTab === 'directory-tab' ? 'directory' : 'circulars';
                    
                    // Update URL without reloading
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabValue);
                    window.history.replaceState({}, '', url);
                });
            });
            
            // Auto-focus search input when directory tab is opened
            document.getElementById('directory-tab').addEventListener('shown.bs.tab', function () {
                document.querySelector('input[name="phone_search"]').focus();
            });
        });
    </script>
</body>
</html>