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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wijeya Newspapers - Circulars</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="images/logo.jpg" type="image/png">
    <style>
        :root {
            --wijeya-primary: #1a237e; /* Navy Blue */
            --wijeya-secondary: #283593; /* Darker Navy */
            --wijeya-accent: #3949ab; /* Medium Blue */
            --wijeya-light: #e8eaf6; /* Light Blue */
            --wijeya-highlight: #ffc107; /* Gold for accents */
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--wijeya-primary) 0%, var(--wijeya-secondary) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
            color: white !important;
            font-size: 1.5rem;
        }
        
        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .navbar-dark .navbar-nav .nav-link:hover {
            color: var(--wijeya-highlight);
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--wijeya-primary) 0%, var(--wijeya-secondary) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-weight: 300;
            opacity: 0.9;
        }
        
        .circular-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
        }
        
        .circular-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .circular-image-container {
            position: relative;
            overflow: hidden;
            height: 200px;
        }
        
        .circular-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .circular-card:hover .circular-image {
            transform: scale(1.05);
        }
        
        .circular-date {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(26, 35, 126, 0.85);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .circular-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--wijeya-primary);
            margin-bottom: 0.75rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .circular-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .btn-wijeya {
            background: linear-gradient(135deg, var(--wijeya-primary) 0%, var(--wijeya-secondary) 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-wijeya:hover {
            background: linear-gradient(135deg, var(--wijeya-secondary) 0%, var(--wijeya-accent) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .search-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .search-input {
            padding-left: 45px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .search-input:focus {
            border-color: var(--wijeya-primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 35, 126, 0.15);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--wijeya-primary);
            pointer-events: none;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--wijeya-primary);
            margin-bottom: 0.5rem;
        }
        
        .pagination .page-link {
            color: var(--wijeya-primary);
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin: 0 3px;
            transition: all 0.3s;
        }
        
        .pagination .page-link:hover {
            background-color: var(--wijeya-light);
            border-color: var(--wijeya-primary);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--wijeya-primary) 0%, var(--wijeya-secondary) 100%);
            border-color: var(--wijeya-primary);
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .no-results-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .copyright-footer {
            background: linear-gradient(135deg, var(--wijeya-primary) 0%, var(--wijeya-secondary) 100%);
            color: white;
            padding: 1.5rem 0;
            margin-top: 3rem;
            text-align: center;
        }
        
        .results-count {
            color: var(--wijeya-primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .filter-badge {
            background: var(--wijeya-light);
            color: var(--wijeya-primary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-flex;
            align-items: center;
        }
        
        .filter-badge i {
            margin-right: 0.3rem;
        }
        
        .clear-filters {
            color: var(--wijeya-primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .clear-filters:hover {
            color: var(--wijeya-secondary);
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 2rem 0;
                border-radius: 0 0 15px 15px;
            }
            
            .circular-image-container {
                height: 180px;
            }
            
            .card-content {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-newspaper me-2"></i>WIJEYA NEWSPAPERS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="circular.php"><i class="fas fa-file-alt me-1"></i> Circulars</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Company Circulars</h1>
            <p class="page-subtitle">Stay updated with the latest announcements and official communications</p>
        </div>
    </div>

    <div class="container">
        <!-- Search and Filter Section -->
        <div class="search-container">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Search Circulars</label>
                        <div class="search-container position-relative">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control search-input" placeholder="Search by title or content...">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Filter by Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" class="form-control">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-wijeya"><i class="fas fa-search me-2"></i>Search</button>
                    <?php if (!empty($search) || !empty($filter_date)): ?>
                        <a href="circular.php" class="clear-filters"><i class="fas fa-times me-1"></i>Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Active Filters Display -->
            <?php if (!empty($search) || !empty($filter_date)): ?>
                <div class="mt-3">
                    <p class="mb-2"><strong>Active Filters:</strong></p>
                    <?php if (!empty($search)): ?>
                        <span class="filter-badge">
                            <i class="fas fa-search"></i> Search: "<?php echo htmlspecialchars($search); ?>"
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($filter_date)): ?>
                        <span class="filter-badge">
                            <i class="fas fa-calendar-alt"></i> Date: <?php echo date("F j, Y", strtotime($filter_date)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Results Count -->
        <div class="results-count">
            <?php if ($total_records > 0): ?>
                Showing <?php echo count($posts); ?> of <?php echo $total_records; ?> circular<?php echo $total_records != 1 ? 's' : ''; ?>
            <?php else: ?>
                No circulars found
            <?php endif; ?>
        </div>
        
        <!-- Circulars Grid -->
        <?php if (!empty($posts)): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($posts as $row): 
                    // Format the date
                    $formatted_date = date("F j, Y", strtotime($row["publish_date"]));
                    
                    // Truncate content for description
                    $description = substr(strip_tags($row["content"]), 0, 120) . "...";
                    
                    // Use featured image or fallback to logo
                    $image_path = !empty($row["featured_image"]) ? $row["featured_image"] : 'images/logo.jpg';
                ?>
                    <div class="col">
                        <div class="card h-100 circular-card">
                            <div class="circular-image-container">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($row["title"]); ?>" class="circular-image" onerror="this.src='images/logo.jpg'">
                                <div class="circular-date">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo date("M j", strtotime($row["publish_date"])); ?>
                                </div>
                            </div>
                            <div class="card-content">
                                <h5 class="circular-title"><?php echo htmlspecialchars($row["title"]); ?></h5>
                                <p class="circular-description">
                                    <?php echo htmlspecialchars($description); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="circular_detail.php?id=<?php echo $row["id"]; ?>" class="btn btn-wijeya">View Details</a>
                                    <small class="text-muted"><?php echo $formatted_date; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>No Circulars Found</h3>
                <p class="text-muted">Try adjusting your search criteria or browse all circulars.</p>
                <a href="circular.php" class="btn btn-wijeya mt-2">View All Circulars</a>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-angle-double-left"></i></span>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-angle-left"></i></span>
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
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-angle-right"></i></span>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="fas fa-angle-double-right"></i></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <footer class="copyright-footer">
        <div class="container">
            <p>© Copyright WNL. All Rights Reserved</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>