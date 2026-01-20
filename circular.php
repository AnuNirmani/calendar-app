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

// Fetch telephone directory entries with enhanced filtering
$phone_search = isset($_GET['phone_search']) ? trim($_GET['phone_search']) : '';
$phone_dept = isset($_GET['phone_dept']) ? trim($_GET['phone_dept']) : 'all';
$phone_entries = [];
$phone_total_records = 0;

// Fetch all departments for dropdown
$departments = [];
$dept_query = "SELECT id, name FROM Department ORDER BY name ASC";
$dept_result = $conn->query($dept_query);
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Build directory query - UPDATED to include position field
$phone_where = [];
$phone_params = [];

if (!empty($phone_search)) {
    $phone_search_clean = $conn->real_escape_string($phone_search);
    $phone_where[] = "(td.name LIKE '%$phone_search_clean%' 
                     OR td.phone_number LIKE '%$phone_search_clean%' 
                     OR td.email LIKE '%$phone_search_clean%' 
                     OR td.extension LIKE '%$phone_search_clean%' 
                     OR td.position LIKE '%$phone_search_clean%' 
                     OR d.name LIKE '%$phone_search_clean%')";
}

if ($phone_dept !== 'all') {
    $phone_dept_clean = $conn->real_escape_string($phone_dept);
    $phone_where[] = "d.id = '$phone_dept_clean'";
}

// Construct the query - UPDATED to include position field
$phone_sql = "SELECT DISTINCT td.id, td.name, td.phone_number, td.email, td.extension, td.position, d.name AS department_name, d.id AS department_id
              FROM Telephone_Directory td 
              LEFT JOIN Department d ON td.department_id = d.id";

if (!empty($phone_where)) {
    $phone_sql .= " WHERE " . implode(" AND ", $phone_where);
}

$phone_sql .= " ORDER BY d.name ASC, td.name ASC";

// Check if we need to limit results
$phone_show_all = (!empty($phone_search) || $phone_dept !== 'all');
if (!$phone_show_all) {
    $phone_sql .= " LIMIT 50"; // Show more entries initially
}

$phone_result = $conn->query($phone_sql);
if ($phone_result) {
    while ($row = $phone_result->fetch_assoc()) {
        // Process phone numbers - split comma-separated values
        if (!empty($row['phone_number'])) {
            $row['phone_numbers'] = explode(',', $row['phone_number']);
            $row['phone_display'] = implode('<br>', $row['phone_numbers']);
        } else {
            $row['phone_numbers'] = [];
            $row['phone_display'] = 'Not available';
        }
        $phone_entries[] = $row;
    }
    $phone_total_records = count($phone_entries);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wijeya Newspapers - Internal Communications</title>
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <link rel="icon" href="images/logo.png" type="image/png">
    <style>
        /* Fallback fonts when Google Fonts is unavailable */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }
        :root {
            --primary-blue: #0056b3;
            --secondary-blue: #1e88e5;
            --accent-blue: #64b5f6;
            --dark-blue: #003366;
            --light-blue: #e3f2fd;
            --gradient-primary: linear-gradient(135deg, #0056b3 0%, #1e88e5 50%, #64b5f6 100%);
            --gradient-secondary: linear-gradient(135deg, #1e88e5 0%, #64b5f6 100%);
            --gradient-dark: linear-gradient(135deg, #003366 0%, #0056b3 100%);
            --shadow-light: 0 8px 30px rgba(0, 86, 179, 0.12);
            --shadow-medium: 0 15px 35px rgba(0, 86, 179, 0.15);
            --shadow-heavy: 0 20px 50px rgba(0, 86, 179, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        
        /* Enhanced Header Styles */
        .main-header {
            background: var(--gradient-primary);
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-medium);
            border-bottom: 4px solid rgba(255, 255, 255, 0.2);
        }
        
        .header-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            z-index: 1;
        }
        
        .header-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: 1200px 100px;
            z-index: 1;
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            padding: 2rem 0;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .logo-container:hover {
            transform: translateY(-2px);
        }
        
        .logo-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 20px;
            padding: 10px;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        /* .logo-wrapper:hover {
            transform: rotate(5deg) scale(1.05);
            box-shadow: var(--shadow-heavy);
        } */
        
        .logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 15px;
        }        
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #ffffff 0%, #e3f2fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .tagline {
            font-size: 1.1rem;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }
        
        .quick-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-content small {
            display: block;
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }
        
        .stat-content strong {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        /* Navigation Menu */
        .main-nav {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 0.75rem 1.5rem;
            margin-top: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 0.5rem;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .nav-indicator {
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 3px;
            background: #ffffff;
            border-radius: 2px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .nav-link.active .nav-indicator {
            opacity: 1;
        }
        
        /* Main Content Container */
        .main-container {
            flex: 1;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            width: 100%;
        }
        
        .content-wrapper {
            background: white;
            border-radius: 25px;
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            border: 1px solid rgba(0, 86, 179, 0.1);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .content-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-heavy);
        }
        
        /* Tab Navigation */
        .tab-header {
            background: var(--gradient-secondary);
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .tab-title {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .tab-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }
        
        .tab-navigation {
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            border-radius: 15px;
            margin-top: 1.5rem;
            backdrop-filter: blur(10px);
        }
        
        .tab-btn {
            flex: 1;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tab-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .tab-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .tab-btn:hover, .tab-btn.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .tab-btn.active {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Tab Content */
        .tab-content {
            padding: 2.5rem;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Search Section */
        .search-section {
            background: linear-gradient(135deg, var(--light-blue) 0%, #f0f8ff 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            border: 2px dashed var(--accent-blue);
        }
        
        .search-title {
            color: var(--dark-blue);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .search-title i {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        
        .search-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow-light);
            transition: transform 0.3s ease;
        }
        
        .search-card:hover {
            transform: translateY(-3px);
        }
        
        /* Circular Cards */
        .circular-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .circular-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid #eef2f7;
            position: relative;
        }
        
        .circular-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-heavy);
            border-color: var(--accent-blue);
        }
        
        .circular-image-container {
            position: relative;
            height: 220px;
            overflow: hidden;
        }
        
        .circular-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        
        .circular-card:hover .circular-image {
            transform: scale(1.1);
        }
        
        .circular-date-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 86, 179, 0.95);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 86, 179, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 2;
        }
        
        .circular-content {
            padding: 1.75rem;
        }
        
        .circular-title {
            font-size: 1.25rem;
            color: var(--dark-blue);
            margin-bottom: 1rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .circular-excerpt {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Enhanced Footer */
        .main-footer {
            background: var(--gradient-dark);
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .footer-wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" fill="%23000000"/></svg>');
            background-size: 1200px 100px;
        }
        
        .footer-content {
            position: relative;
            z-index: 2;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-section {
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .footer-logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #ffffff 0%, #64b5f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .footer-description {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        
        .footer-heading {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .footer-heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--accent-blue);
            border-radius: 2px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
        }
        
        .footer-links a:hover {
            color: white;
            transform: translateX(10px);
        }
        
        .footer-links a i {
            width: 20px;
            text-align: center;
            color: var(--accent-blue);
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .contact-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .social-link {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .social-link:hover {
            background: var(--accent-blue);
            transform: translateY(-5px) rotate(5deg);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .copyright {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .footer-legal {
            display: flex;
            gap: 2rem;
        }
        
        .footer-legal a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .footer-legal a:hover {
            color: white;
        }
        
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: var(--shadow-light);
        }
        
        .scroll-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .scroll-top:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-heavy);
        }
        
        /* Enhanced Telephone Directory Styles - ROW FORMAT */
        .directory-filters {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
        }
        
        .directory-summary {
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-blue);
        }
        
        /* Department Group */
        .department-group {
            margin-bottom: 2rem;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #eef2f7;
        }
        
        .department-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .department-name {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .department-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        /* Contact Details in Row Format */
        .contact-row {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        
        .contact-row:hover {
            background: #f8fafc;
        }
        
        .contact-row:last-child {
            border-bottom: none;
        }
        
        .contact-info-col {
            flex: 1;
            min-width: 250px;
        }
        
        .contact-name {
            font-weight: 600;
            color: var(--dark-blue);
            font-size: 1.05rem;
            margin-bottom: 0.25rem;
        }
        
        .contact-position {
            font-size: 0.85rem;
            color: #64748b;
            font-style: italic;
            margin-bottom: 0.25rem;
        }
        
        .contact-extension {
            color: #64748b;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .contact-details-col {
            flex: 2;
            display: flex;
            gap: 2rem;
        }
        
        .contact-detail {
            display: flex;
            flex-direction: column;
            min-width: 180px;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-size: 0.95rem;
            color: #334155;
            font-weight: 500;
        }
        
        .detail-value a {
            color: var(--primary-blue);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .detail-value a:hover {
            color: var(--secondary-blue);
            text-decoration: underline;
        }
        
        .phone-numbers {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .phone-number-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .copy-phone-btn {
            background: none;
            border: none;
            color: var(--primary-blue);
            cursor: pointer;
            font-size: 0.8rem;
            padding: 2px 4px;
            border-radius: 3px;
            transition: all 0.2s ease;
        }
        
        .copy-phone-btn:hover {
            background-color: #f1f5f9;
        }
        
        .contact-actions-col {
            flex: 0 0 auto;
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            transform: translateY(-1px);
        }
        
        .action-btn i {
            font-size: 0.9rem;
        }
        
        /* No Data State */
        .empty-directory {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border: 2px dashed #cbd5e1;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }
        
        /* Responsive Design for Rows */
        @media (max-width: 1024px) {
            .contact-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .contact-info-col {
                min-width: 100%;
            }
            
            .contact-details-col {
                flex-direction: column;
                width: 100%;
                gap: 1rem;
            }
            
            .contact-detail {
                min-width: 100%;
            }
            
            .contact-actions-col {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 768px) {
            .company-name {
                font-size: 2rem;
            }
            
            .logo-wrapper {
                width: 80px;
                height: 80px;
            }
            
            .quick-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .circular-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            
            .tab-content {
                padding: 1.5rem;
            }
            
            .department-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .contact-row {
                padding: 1rem;
            }
            
            .contact-details-col {
                gap: 0.75rem;
            }
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border: 3px solid rgba(0, 86, 179, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-blue);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Active Filters */
        .active-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--light-blue);
            border: 1px solid var(--accent-blue);
            color: var(--dark-blue);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .active-filter i {
            font-size: 0.75rem;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .active-filter i:hover {
            opacity: 1;
        }

        /* .contact-position-name {
    font-weight: 600;
    color: var(--dark-blue);
    font-size: 1.05rem;
    margin: 0;
} */


        .contact-strong {
            font-weight: 600;
            color: var(--dark-blue);
            font-size: 1.05rem;
            margin: 0;
            line-height: 1.2;
        }

        .contact-strong.muted {
            opacity: 0.5;
        }



/* ONE grid template for both header row and data row */
.contact-row,
.contact-header-row {
    display: grid;
    grid-template-columns: 260px 260px 120px 180px 260px 90px; 
    /*  Employee | Position | Extension | Phone | Email | Actions  */
    align-items: center;
    column-gap: 16px;
}

/* header row style */
.contact-header-row {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 24px;
}

/* data rows */
.contact-row {
    padding: 16px 24px;
    border-bottom: 1px solid #f1f5f9;
}

.contact-row:hover {
    background: #f8fafc;
}

/* prevent wrapping so everything stays one line */
.col-nowrap {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* phone row items in one line */
.phone-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* actions aligned */
.contact-actions-col {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

/* Make long emails/positions not break layout */
.position-col,
.email-col {
    min-width: 0;
}



    </style>
</head>
<body>
    <!-- Enhanced Header -->
    <header class="main-header">
        <div class="header-background"></div>
        <div class="header-wave"></div>
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <div class="logo-wrapper">
                        <img src="images/logo.jpg" alt="Wijeya Newspapers" class="logo">
                    </div>
                    <div class="company-info">
                        <h1 class="company-name">WIJEYA NEWSPAPERS</h1>
                        <p class="tagline">Excellence in Journalism Since 1970</p>
                    </div>
                </div>
                
                <nav class="main-nav">
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="index.php" class="nav-link">
                                <i class="fas fa-home"></i>
                                <span>Home</span>
                                <div class="nav-indicator"></div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="circular.php?tab=directory" class="nav-link <?php echo $current_tab == 'directory' ? 'active' : ''; ?>">
                                <i class="fas fa-phone"></i>
                                <span>Directory</span>
                                <div class="nav-indicator"></div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="circular.php?tab=circulars" class="nav-link <?php echo $current_tab == 'circulars' ? 'active' : ''; ?>">
                                <i class="fas fa-newspaper"></i>
                                <span>Circulars</span>
                                <div class="nav-indicator"></div>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="content-wrapper">
            <div class="tab-content">
                <!-- Circulars Tab Content -->
                <div id="circulars-tab-content" class="tab-pane-content <?php echo $current_tab == 'circulars' ? 'active' : 'd-none'; ?>">
                    <!-- Circulars content remains the same -->
                    <div class="search-section">
                        <h3 class="search-title">
                            <i class="fas fa-search"></i>
                            Search Circulars
                        </h3>
                        <div class="search-card">
                            <form method="GET" action="" class="row g-3">
                                <input type="hidden" name="tab" value="circulars">
                                <div class="col-md-6">
                                    <label class="form-label">Search Content</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-search text-primary"></i>
                                        </span>
                                        <input type="text" 
                                               name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" 
                                               class="form-control border-start-0" 
                                               placeholder="Search by title or content...">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Filter by Date</label>
                                    <input type="date" 
                                           name="date" 
                                           value="<?php echo htmlspecialchars($filter_date); ?>" 
                                           class="form-control">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
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
                                        <a href="circular.php?tab=circulars" class="btn btn-sm btn-outline-primary">
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
                                        <i class="fas fa-calendar-alt"></i>
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
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="circular_detail.php?id=<?php echo $row["id"]; ?>" 
                                           class="btn btn-primary">
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
                        <div class="text-center py-5">
                            <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Circulars Found</h4>
                            <p class="text-muted">
                                <?php if (!empty($search) || !empty($filter_date)): ?>
                                    No circulars match your search criteria.
                                <?php else: ?>
                                    There are no circulars available at the moment.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-5">
                        <nav aria-label="Circulars pagination">
                            <ul class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
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
                                           href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" 
                                           href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_date) ? '&date=' . urlencode($filter_date) : ''; ?>&tab=circulars">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

<!-- Directory Tab Content - UPDATED for position field and multiple phone numbers -->
<div id="directory-tab-content" class="tab-pane-content <?php echo $current_tab == 'directory' ? 'active' : 'd-none'; ?>">
    <div class="search-section">
        <h3 class="search-title">
            <i class="fas fa-address-book"></i>
            Employee Directory
        </h3>
        <div class="search-card">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="tab" value="directory">

                <div class="col-md-4">
                    <label class="form-label">Filter by Department</label>
                    <select name="phone_dept" class="form-select">
                        <option value="all" <?php echo $phone_dept == 'all' ? 'selected' : ''; ?>>All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"
                                    <?php echo $phone_dept == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Search Contacts</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text"
                               name="phone_search"
                               value="<?php echo htmlspecialchars($phone_search); ?>"
                               class="form-control border-start-0"
                               placeholder="Search by name, phone, email, position, or department...">
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <?php if (!empty($phone_search) || $phone_dept !== 'all'): ?>
                            <a href="circular.php?tab=directory" class="btn btn-outline-primary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if (!empty($phone_search) || $phone_dept !== 'all'): ?>
                <!-- <div class="directory-summary mt-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Active Filters:</h6>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <?php if (!empty($phone_search)): ?>
                                    <span class="active-filter">
                                        Search: "<?php echo htmlspecialchars($phone_search); ?>"
                                        <i class="fas fa-times" onclick="clearFilter('phone_search')"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if ($phone_dept !== 'all'):
                                    $selected_dept = '';
                                    foreach ($departments as $dept) {
                                        if ($dept['id'] == $phone_dept) {
                                            $selected_dept = $dept['name'];
                                            break;
                                        }
                                    }
                                ?>
                                    <span class="active-filter">
                                        Department: <?php echo htmlspecialchars($selected_dept); ?>
                                        <i class="fas fa-times" onclick="clearFilter('phone_dept')"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted"><?php echo $phone_total_records; ?> contact(s) found</small>
                        </div>
                    </div>
                </div> -->
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($phone_entries)): ?>
        <?php
        $grouped_contacts = [];
        foreach ($phone_entries as $entry) {
            $dept_name = !empty($entry['department_name']) ? $entry['department_name'] : 'Unassigned';
            $grouped_contacts[$dept_name][] = $entry;
        }
        ksort($grouped_contacts);
        ?>

        <div class="directory-results" id="directory-view">
            <?php foreach ($grouped_contacts as $dept_name => $contacts): ?>
                <div class="department-group">
                    <div class="department-header">
                        <div class="department-name">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($dept_name); ?>
                        </div>
                        <div class="department-count">
                            <?php echo count($contacts); ?> employee(s)
                        </div>
                    </div>

                    <!-- Table Headers -->
                    <div class="contact-header-row">

<div class="detail-label">Employee</div>
<div class="detail-label">Position</div>
<div class="detail-label">Extension</div>
<div class="detail-label">Phone</div>
<div class="detail-label">Email</div>
<div class="detail-label text-end">Actions</div>

                    </div>

                    <!-- Contact Rows -->
                    <?php foreach ($contacts as $entry): ?>
                        <div class="contact-row">
                            <div class="contact-info-col" style="flex:1; min-width:200px;">
                                <div class="contact-name col-nowrap">
    <?php echo htmlspecialchars($entry['name']); ?>
</div>

                            </div>

                            <div style="flex:0.8; min-width:150px; padding:0 1.5rem;">
                                <?php if (!empty($entry['position'])): ?>
                                    <div class="contact-strong col-nowrap position-col">
    <?php echo !empty($entry['position']) ? htmlspecialchars($entry['position']) : 'Not available'; ?>
</div>

                                <?php else: ?>
                                    <div class="contact-strong muted">Not available</div>
                                <?php endif; ?>
                            </div>


                            <div style="flex:0.6; min-width:100px; padding:0 1.5rem;">
                                <?php if (!empty($entry['extension'])): ?>
                                    <div class="contact-strong">
                                        <?php echo htmlspecialchars($entry['extension']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="contact-strong muted">-</div>
                                <?php endif; ?>
                            </div>

                            <!-- ✅ Phone column -->
                            <div style="flex:0.75; min-width:180px;">
                                <?php if (!empty($entry['phone_numbers'])): ?>
                                    <?php foreach ($entry['phone_numbers'] as $phone): ?>
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                                            <a href="tel:<?php echo htmlspecialchars($phone); ?>"
                                            class="contact-strong"
                                            style="text-decoration:none; color:var(--dark-blue);">
                                                <?php echo htmlspecialchars($phone); ?>
                                            </a>
                                            <button class="copy-phone-btn"
                                                    onclick="copyToClipboard('<?php echo htmlspecialchars($phone); ?>', event)"
                                                    title="Copy number">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="contact-strong muted">Not available</div>
                                <?php endif; ?>
                            </div>


                            <!-- ✅ Email column -->
                            <div style="flex:0.75; min-width:220px;">
                                <?php if (!empty($entry['email'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($entry['email']); ?>"
                                    class="contact-strong"
                                    style="text-decoration:none; color:var(--dark-blue);">
                                        <?php echo htmlspecialchars($entry['email']); ?>
                                    </a>
                                <?php else: ?>
                                    <div class="contact-strong muted">Not available</div>
                                <?php endif; ?>
                            </div>


                            <!-- Actions -->
                            <div class="contact-actions-col" style="flex:0.6; display:flex; gap:0.5rem;">
                                <?php if (!empty($entry['phone_numbers']) && count($entry['phone_numbers']) > 0): ?>
                                    <button class="action-btn"
                                            onclick="callNumber('<?php echo htmlspecialchars($entry['phone_numbers'][0]); ?>')"
                                            style="width:36px; height:36px; border-radius:8px; border:1px solid #e2e8f0; background:white; color:var(--primary-blue); display:flex; align-items:center; justify-content:center; cursor:pointer;"
                                            title="Call">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if (!empty($entry['email'])): ?>
                                    <button class="action-btn"
                                            onclick="sendEmail('<?php echo htmlspecialchars($entry['email']); ?>')"
                                            style="width:36px; height:36px; border-radius:8px; border:1px solid #e2e8f0; background:white; color:var(--primary-blue); display:flex; align-items:center; justify-content:center; cursor:pointer;"
                                            title="Email">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- <button class="action-btn"
                                        onclick="copyContactDetails(this)"
                                        style="width:36px; height:36px; border-radius:8px; border:1px solid #e2e8f0; background:white; color:var(--primary-blue); display:flex; align-items:center; justify-content:center; cursor:pointer;"
                                        title="Copy Details">
                                    <i class="fas fa-copy"></i>
                                </button> -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($phone_search) && $phone_dept === 'all' && $phone_total_records >= 50): ?>
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>
                Showing 50 most recent contacts. Use search or department filter to find specific contacts.
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-directory">
            <div class="empty-icon">
                <i class="fas fa-users"></i>
            </div>
            <h4 class="text-muted mb-3">
                <?php if (!empty($phone_search) || $phone_dept !== 'all'): ?>
                    No contacts found matching your criteria
                <?php else: ?>
                    Employee Directory is Empty
                <?php endif; ?>
            </h4>
            <p class="text-muted mb-4">
                <?php if (!empty($phone_search)): ?>
                    No results found for "<?php echo htmlspecialchars($phone_search); ?>"
                <?php elseif ($phone_dept !== 'all'): ?>
                    No employees found in the selected department
                <?php else: ?>
                    There are no contacts in the directory yet.
                <?php endif; ?>
            </p>
            <?php if (!empty($phone_search) || $phone_dept !== 'all'): ?>
                <a href="circular.php?tab=directory" class="btn btn-primary">
                    <i class="fas fa-redo me-1"></i>Show All Contacts
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

            </div>
        </div>
    </main>

    <!-- Enhanced Footer -->
    <footer class="main-footer">
        <div class="footer-wave"></div>
        <div class="container">
            <div class="footer-content">  
                <div class="footer-bottom">
                    <div class="copyright">
                        <i class="far fa-copyright me-1"></i>
                        <?php echo date('Y'); ?> Wijeya Newspapers Ltd. All Rights Reserved.
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTopBtn">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Tab Switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-pane-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Update active tab button
                    tabBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show corresponding content
                    tabContents.forEach(content => {
                        content.classList.add('d-none');
                        content.classList.remove('active');
                    });
                    
                    const activeContent = document.getElementById(tabId + '-tab-content');
                    if (activeContent) {
                        activeContent.classList.remove('d-none');
                        activeContent.classList.add('active');
                    }
                    
                    // Update URL
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.replaceState({}, '', url);
                    
                    // Focus search input in directory tab
                    if (tabId === 'directory') {
                        setTimeout(() => {
                            document.querySelector('input[name="phone_search"]').focus();
                        }, 100);
                    }
                });
            });
            
            // Scroll to Top
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollTopBtn.classList.add('show');
                } else {
                    scrollTopBtn.classList.remove('show');
                }
            });
            
            scrollTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Animate elements on scroll
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
            
            // Observe department groups
            document.querySelectorAll('.department-group').forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(element);
            });
            
            // Auto-focus search in directory tab if active
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'directory') {
                setTimeout(() => {
                    const searchInput = document.querySelector('input[name="phone_search"]');
                    if (searchInput) searchInput.focus();
                }, 300);
            }
        });
        
        // Function to clear individual filters
        function clearFilter(filterName) {
            const url = new URL(window.location);
            url.searchParams.delete(filterName);
            window.location.href = url.toString();
        }
        
        // Call phone number
        function callNumber(phoneNumber) {
            window.location.href = `tel:${phoneNumber}`;
        }
        
        // Send email
        function sendEmail(email) {
            window.location.href = `mailto:${email}`;
        }
        
        // Copy phone number to clipboard
        function copyToClipboard(text, e) {
            navigator.clipboard.writeText(text).then(function() {
                const btn = event.target.closest('button');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.style.color = '#10b981';
                
                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                    btn.style.color = '';
                }, 1500);
            }, function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy phone number');
            });
        }
        
        // Copy contact details to clipboard
        function copyContactDetails(button) {
            const contactRow = button.closest('.contact-row');
            const name = contactRow.querySelector('.contact-name').textContent;
            const positionElement = contactRow.querySelector('.contact-position');
            const position = positionElement ? positionElement.textContent.replace(/^\s*[^:]*:\s*/, '').trim() : '';
            const extensionElement = contactRow.querySelector('.contact-extension');
            const extension = extensionElement ? extensionElement.textContent.replace('Extension:', '').trim() : '';
            
            // Get all phone numbers
            const phoneItems = contactRow.querySelectorAll('.phone-number-item');
            let phones = [];
            phoneItems.forEach(item => {
                const phone = item.querySelector('a').textContent.trim();
                phones.push(phone);
            });
            
            // Get email
            const emailElement = contactRow.querySelector('a[href^="mailto:"]');
            const email = emailElement ? emailElement.textContent.trim() : 'Not available';
            
            const contactText = `Name: ${name}
${position ? `Position: ${position}\n` : ''}
${extension ? `Extension: ${extension}\n` : ''}
Phone Numbers: ${phones.length > 0 ? phones.join(', ') : 'Not available'}
Email: ${email}`;
            
            navigator.clipboard.writeText(contactText).then(() => {
                showNotification('Contact details copied to clipboard!');
            });
        }
        
        // Show notification
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Print directory
        function printDirectory() {
            window.print();
        }
    </script>
</body>
</html>