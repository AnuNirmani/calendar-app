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
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'circulars';

// Build count query for total records
$count_sql = "SELECT COUNT(*) as total FROM posts WHERE status = 'published'";

// Build base query for fetching data
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

// Initialize variables
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wijeya Newspapers - Internal Communications</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="images/logo.jpg" type="image/png">
    <style>
        :root {
            --wijeya-primary: #0056b3;
            --wijeya-secondary: #87CEEB;
            --wijeya-accent: #4682B4;
            --wijeya-light: #F0F8FF;
            --wijeya-dark: #003366;
            --gradient-primary: linear-gradient(135deg, var(--wijeya-primary) 0%, var(--wijeya-accent) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--wijeya-secondary) 0%, #5DADE2 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
        }
        
        /* Header Styles */
        .main-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .main-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
            opacity: 0.1;
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .company-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 0.5px;
        }
        
        .tagline {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 400;
            margin-top: 0.25rem;
        }
        
        /* Navigation Tabs */
        .nav-tabs-wrapper {
            background: white;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.08);
            margin-top: -20px;
            position: relative;
            z-index: 10;
        }
        
        .nav-tabs-custom {
            border: none;
            padding: 0 2rem;
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            color: #666;
            font-weight: 600;
            padding: 1.25rem 2rem;
            margin: 0;
            position: relative;
            transition: all 0.3s ease;
            font-size: 1rem;
            border-radius: 10px 10px 0 0;
            background: transparent;
        }
        
        .nav-tabs-custom .nav-link:hover {
            color: var(--wijeya-primary);
            background: rgba(0, 86, 179, 0.05);
        }
        
        .nav-tabs-custom .nav-link.active {
            color: var(--wijeya-primary);
            background: white;
            box-shadow: 0 -4px 12px rgba(0, 86, 179, 0.1);
        }
        
        .nav-tabs-custom .nav-link.active::before {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 3px 3px 0 0;
        }
        
        /* Main Content */
        .main-container {
            background: white;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            margin-bottom: 3rem;
            min-height: 600px;
        }
        
        .tab-content {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Search Section */
        .search-section {
            background: var(--wijeya-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 86, 179, 0.1);
        }
        
        .search-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .search-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--wijeya-dark);
            margin-bottom: 0.5rem;
        }
        
        .search-input-group {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--wijeya-primary);
            z-index: 5;
        }
        
        .search-input {
            padding-left: 45px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            transition: all 0.3s ease;
            height: 48px;
        }
        
        .search-input:focus {
            border-color: var(--wijeya-primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.15);
        }
        
        /* Buttons */
        .btn-wijeya {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 86, 179, 0.2);
        }
        
        .btn-wijeya:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 86, 179, 0.3);
            color: white;
        }
        
        .btn-wijeya:active {
            transform: translateY(0);
        }
        
        .btn-outline-wijeya {
            border: 2px solid var(--wijeya-primary);
            color: var(--wijeya-primary);
            background: transparent;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-wijeya:hover {
            background: var(--wijeya-primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Circular Cards */
        .circular-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .circular-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid #eef2f7;
        }
        
        .circular-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 86, 179, 0.15);
            border-color: var(--wijeya-secondary);
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
            transition: transform 0.5s ease;
        }
        
        .circular-card:hover .circular-image {
            transform: scale(1.05);
        }
        
        .circular-date-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0, 86, 179, 0.9);
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(5px);
        }
        
        .circular-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .circular-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--wijeya-dark);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .circular-excerpt {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .circular-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eef2f7;
        }
        
        /* Telephone Directory */
        .directory-search {
            position: relative;
            max-width: 500px;
            margin: 0 auto 2rem;
        }
        
        .directory-search-input {
            padding-left: 50px;
            padding-right: 50px;
            height: 52px;
            border-radius: 25px;
            border: 2px solid #e1e5eb;
            font-size: 1rem;
        }
        
        .directory-search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--wijeya-primary);
            font-size: 1.1rem;
        }
        
        .directory-search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .directory-table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .directory-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .directory-table thead {
            background: var(--wijeya-light);
        }
        
        .directory-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 700;
            color: var(--wijeya-dark);
            border-bottom: 2px solid var(--wijeya-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .directory-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        
        .directory-table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .directory-table tbody tr:hover {
            background-color: rgba(0, 86, 179, 0.03);
        }
        
        .contact-name {
            font-weight: 600;
            color: var(--wijeya-dark);
        }
        
        .contact-phone, .contact-email {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .contact-phone:hover, .contact-email:hover {
            color: var(--wijeya-primary);
        }
        
        .department-badge {
            background: var(--wijeya-secondary);
            color: var(--wijeya-dark);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Pagination */
        .pagination-container {
            margin-top: 2.5rem;
        }
        
        .pagination-custom .page-link {
            border: none;
            color: var(--wijeya-primary);
            margin: 0 0.25rem;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            min-width: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .pagination-custom .page-link:hover {
            background: rgba(0, 86, 179, 0.1);
        }
        
        .pagination-custom .page-item.active .page-link {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 8px rgba(0, 86, 179, 0.2);
        }
        
        /* Footer */
        .main-footer {
            background: var(--wijeya-dark);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 3rem;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .copyright {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .footer-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .footer-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-link:hover {
            color: white;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #e1e5eb;
            margin-bottom: 1.5rem;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .empty-state-text {
            color: #999;
            margin-bottom: 1.5rem;
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border: 3px solid rgba(0, 86, 179, 0.3);
            border-radius: 50%;
            border-top-color: var(--wijeya-primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-tabs-custom .nav-link {
                padding: 1rem;
                font-size: 0.9rem;
            }
            
            .circular-grid {
                grid-template-columns: 1fr;
            }
            
            .directory-table {
                display: block;
                overflow-x: auto;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .main-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <img src="images/logo.jpg" alt="Wijeya Newspapers" class="logo">
                    <div>
                        <div class="company-name">WIJEYA NEWSPAPERS</div>
                        <div class="tagline">Internal Communications Portal</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="nav-tabs-wrapper">
        <div class="container">
            <ul class="nav nav-tabs nav-tabs-custom" id="mainTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $current_tab == 'circulars' ? 'active' : ''; ?>" 
                            id="circulars-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#circulars" 
                            type="button" 
                            role="tab" 
                            aria-controls="circulars" 
                            aria-selected="<?php echo $current_tab == 'circulars' ? 'true' : 'false'; ?>">
                        <i class="fas fa-newspaper me-2"></i>Circulars
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $current_tab == 'directory' ? 'active' : ''; ?>" 
                            id="directory-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#directory" 
                            type="button" 
                            role="tab" 
                            aria-controls="directory" 
                            aria-selected="<?php echo $current_tab == 'directory' ? 'true' : 'false'; ?>">
                        <i class="fas fa-address-book me-2"></i>Telephone Directory
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="main-container">
            <div class="tab-content" id="mainTabContent">
                <!-- Circulars Tab -->
                <div class="tab-pane fade <?php echo $current_tab == 'circulars' ? 'show active' : ''; ?>" 
                     id="circulars" 
                     role="tabpanel" 
                     aria-labelledby="circulars-tab">
                    
                    <div class="search-section">
                        <h3 class="mb-4">Search Circulars</h3>
                        <div class="search-card">
                            <form method="GET" action="" class="row g-3">
                                <input type="hidden" name="tab" value="circulars">
                                <div class="col-md-6">
                                    <label class="form-label">Search Content</label>
                                    <div class="search-input-group">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" 
                                               name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" 
                                               class="form-control search-input" 
                                               placeholder="Search by title or content...">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Filter by Date</label>
                                    <input type="date" 
                                           name="date" 
                                           value="<?php echo htmlspecialchars($filter_date); ?>" 
                                           class="form-control search-input">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-wijeya w-100">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                                
                                <?php if (!empty($search) || !empty($filter_date)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fas fa-info-circle me-2"></i>
                                            Showing results for 
                                            <?php if (!empty($search)): ?>
                                                search: "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                            <?php endif; ?>
                                            <?php if (!empty($filter_date)): ?>
                                                <?php if (!empty($search)): ?> and <?php endif; ?>
                                                date: <strong><?php echo htmlspecialchars($filter_date); ?></strong>
                                            <?php endif; ?>
                                        </div>
                                        <a href="circular.php?tab=circulars" class="btn btn-sm btn-outline-wijeya">
                                            <i class="fas fa-times me-1"></i>Clear Filters
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Circulars Grid -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>Latest Circulars</h3>
                        <div class="text-muted">
                            <i class="fas fa-layer-group me-1"></i>
                            <?php echo $total_records; ?> circular(s) found
                        </div>
                    </div>

                    <?php if (!empty($posts)): ?>
                        <div class="circular-grid">
                            <?php foreach ($posts as $row): 
                                $formatted_date = date("F j, Y", strtotime($row["publish_date"]));
                                $description = substr(strip_tags($row["content"]), 0, 150) . "...";
                                $image_path = !empty($row["featured_image"]) ? $row["featured_image"] : 'images/logo.jpg';
                            ?>
                            <div class="circular-card">
                                <div class="circular-image-container">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                         alt="<?php echo htmlspecialchars($row["title"]); ?>" 
                                         class="circular-image"
                                         onerror="this.src='images/logo.jpg'">
                                    <div class="circular-date-badge">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?php echo $formatted_date; ?>
                                    </div>
                                </div>
                                <div class="circular-content">
                                    <h4 class="circular-title">
                                        <?php echo htmlspecialchars($row["title"]); ?>
                                    </h4>
                                    <p class="circular-excerpt">
                                        <?php echo htmlspecialchars($description); ?>
                                    </p>
                                    <div class="circular-footer">
                                        <a href="circular_detail.php?id=<?php echo $row["id"]; ?>" 
                                           class="btn btn-wijeya btn-sm">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                        <span class="text-muted small">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date("h:i A", strtotime($row["publish_date"])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <h3 class="empty-state-title">No Circulars Found</h3>
                            <p class="empty-state-text">
                                <?php if (!empty($search) || !empty($filter_date)): ?>
                                    No circulars match your search criteria. Try different keywords or clear filters.
                                <?php else: ?>
                                    There are no circulars available at the moment.
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search) || !empty($filter_date)): ?>
                                <a href="circular.php?tab=circulars" class="btn btn-wijeya">
                                    <i class="fas fa-times me-1"></i>Clear Search
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Circulars pagination">
                            <ul class="pagination pagination-custom justify-content-center">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars"
                                           title="First Page">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars"
                                           title="Previous">
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
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $i; ?></span>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars"
                                           title="Next">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars"
                                           title="Last Page">
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
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Telephone Directory Tab -->
                <div class="tab-pane fade <?php echo $current_tab == 'directory' ? 'show active' : ''; ?>" 
                     id="directory" 
                     role="tabpanel" 
                     aria-labelledby="directory-tab">
                    
                    <div class="mb-4">
                        <h3>Telephone Directory</h3>
                        <p class="text-muted">Search for colleagues by name, department, phone number, or email</p>
                    </div>

                    <form method="GET" action="" class="mb-4">
                        <input type="hidden" name="tab" value="directory">
                        <div class="directory-search">
                            <i class="fas fa-search directory-search-icon"></i>
                            <input type="text" 
                                   name="phone_search" 
                                   value="<?php echo htmlspecialchars($phone_search); ?>" 
                                   class="form-control directory-search-input" 
                                   placeholder="Search contacts...">
                            <button type="submit" class="directory-search-btn">
                                Search
                            </button>
                        </div>
                        <?php if (!empty($phone_search)): ?>
                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <a href="circular.php?tab=directory" class="btn btn-outline-wijeya btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Search
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>

                    <?php if (!empty($phone_search)): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            Found <strong><?php echo $phone_total_records; ?></strong> result(s) for 
                            "<strong><?php echo htmlspecialchars($phone_search); ?></strong>"
                        </div>
                    </div>
                    <?php elseif (empty($phone_entries)): ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Start typing to search for contacts in the directory.
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($phone_entries)): ?>
                    <div class="directory-table-container">
                        <table class="directory-table">
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
                                    <td>
                                        <div class="contact-name">
                                            <?php echo htmlspecialchars($entry['name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="tel:<?php echo htmlspecialchars($entry['phone_number']); ?>" 
                                           class="contact-phone">
                                            <i class="fas fa-phone-alt"></i>
                                            <?php echo htmlspecialchars($entry['phone_number']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($entry['email'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($entry['email']); ?>" 
                                               class="contact-email">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($entry['email']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($entry['extension'])): ?>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo htmlspecialchars($entry['extension']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($entry['department_name'])): ?>
                                            <span class="department-badge">
                                                <?php echo htmlspecialchars($entry['department_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (empty($phone_search) && $phone_total_records > 20): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Showing 20 most recent entries. Use the search bar to find specific contacts.
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                        <?php if (!empty($phone_search)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="empty-state-title">No Results Found</h3>
                            <p class="empty-state-text">
                                No contacts found for "<strong><?php echo htmlspecialchars($phone_search); ?></strong>".
                                Try different search terms.
                            </p>
                            <a href="circular.php?tab=directory" class="btn btn-wijeya">
                                <i class="fas fa-times me-1"></i>Clear Search
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    <i class="far fa-copyright me-1"></i>
                    <?php echo date('Y'); ?> Wijeya Newspapers Ltd. All Rights Reserved.
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle tab persistence and URL updates
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab') || 'circulars';
            
            // Set active tab based on URL parameter
            const activeTab = new bootstrap.Tab(document.getElementById(tabParam + '-tab'));
            activeTab.show();
            
            // Update URL when tab changes without reloading
            const tabTriggers = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabTriggers.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(event) {
                    const activeTab = event.target.getAttribute('id');
                    const tabValue = activeTab === 'directory-tab' ? 'directory' : 'circulars';
                    
                    // Update URL
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabValue);
                    window.history.replaceState({}, '', url);
                    
                    // Focus search input in directory tab
                    if (tabValue === 'directory') {
                        setTimeout(() => {
                            document.querySelector('input[name="phone_search"]').focus();
                        }, 100);
                    }
                });
            });
            
            // Auto-focus search in directory tab if it's active
            if (tabParam === 'directory') {
                setTimeout(() => {
                    const phoneSearch = document.querySelector('input[name="phone_search"]');
                    if (phoneSearch) phoneSearch.focus();
                }, 300);
            }
            
            // Add animation to cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe circular cards
            document.querySelectorAll('.circular-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>