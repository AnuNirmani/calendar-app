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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="images/logo.jpg" type="image/png">
    <style>
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
        
        .logo-wrapper:hover {
            transform: rotate(5deg) scale(1.05);
            box-shadow: var(--shadow-heavy);
        }
        
        .logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 15px;
        }
        
        .logo-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 3px 8px rgba(255, 71, 87, 0.3);
            animation: pulse 2s infinite;
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
        
        /* Responsive Design */
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
                        <div class="logo-badge">
                            <i class="fas fa-star"></i>
                        </div>
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
                            <a href="circular.php?tab=circulars" class="nav-link <?php echo $current_tab == 'circulars' ? 'active' : ''; ?>">
                                <i class="fas fa-newspaper"></i>
                                <span>Circulars</span>
                                <div class="nav-indicator"></div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="circular.php?tab=directory" class="nav-link <?php echo $current_tab == 'directory' ? 'active' : ''; ?>">
                                <i class="fas fa-address-book"></i>
                                <span>Directory</span>
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

                <!-- Directory Tab Content -->
                <div id="directory-tab-content" class="tab-pane-content <?php echo $current_tab == 'directory' ? 'active' : 'd-none'; ?>">
                    <div class="search-section">
                        <h3 class="search-title">
                            <i class="fas fa-address-book"></i>
                            Telephone Directory
                        </h3>
                        <div class="search-card">
                            <form method="GET" action="" class="mb-4">
                                <input type="hidden" name="tab" value="directory">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input type="text" 
                                           name="phone_search" 
                                           value="<?php echo htmlspecialchars($phone_search); ?>" 
                                           class="form-control border-start-0" 
                                           placeholder="Search by name, department, phone, or email...">
                                    <button type="submit" class="btn btn-primary">
                                        Search
                                    </button>
                                </div>
                                <?php if (!empty($phone_search)): ?>
                                <div class="d-flex justify-content-center mt-3">
                                    <a href="circular.php?tab=directory" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-times me-1"></i>Clear Search
                                    </a>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($phone_entries)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-primary">
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
                                        <td class="fw-semibold"><?php echo htmlspecialchars($entry['name']); ?></td>
                                        <td>
                                            <a href="tel:<?php echo htmlspecialchars($entry['phone_number']); ?>" 
                                               class="text-decoration-none">
                                                <i class="fas fa-phone text-success me-2"></i>
                                                <?php echo htmlspecialchars($entry['phone_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (!empty($entry['email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($entry['email']); ?>" 
                                                   class="text-decoration-none">
                                                    <i class="fas fa-envelope text-primary me-2"></i>
                                                    <?php echo htmlspecialchars($entry['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($entry['extension'])): ?>
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($entry['extension']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($entry['department_name'])): ?>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($entry['department_name']); ?></span>
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
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Showing 20 most recent entries. Use the search bar to find specific contacts.
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">
                                <?php if (!empty($phone_search)): ?>
                                    No contacts found for "<?php echo htmlspecialchars($phone_search); ?>"
                                <?php else: ?>
                                    Start typing to search the directory
                                <?php endif; ?>
                            </h4>
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
                    <div class="footer-legal">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Cookie Policy</a>
                        <a href="#">Accessibility</a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
            
            // Observe cards and sections
            document.querySelectorAll('.circular-card, .footer-section').forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(element);
            });
            
            // Add hover effects to buttons
            document.querySelectorAll('.btn, .nav-link, .social-link').forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
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
    </script>
</body>
</html>