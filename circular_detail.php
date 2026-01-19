<?php
include 'db.php';

// Get circular ID from URL
$circular_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

if (!$circular_id) {
    header('Location: circular.php?error=invalid_id&tab=circulars');
    exit;
}

// Function to get correct image path
function getImagePath($image_name) {
    if (empty($image_name) || $image_name == 'NULL') {
        return 'images/logo.jpg';
    }
    
    // If it's already a full URL, return as is
    if (filter_var($image_name, FILTER_VALIDATE_URL)) {
        return $image_name;
    }
    
    // Check different possible locations
    $possible_paths = [
        $image_name, // Original path
        'images/' . $image_name,
        'images/circulars/' . $image_name,
        'uploads/' . $image_name,
        '../images/' . $image_name,
        'images/posts/' . $image_name,
        'assets/images/' . $image_name,
        'img/' . $image_name,
        'media/' . $image_name
    ];
    
    foreach ($possible_paths as $path) {
        // Check if file exists
        if (file_exists($path)) {
            return $path;
        }
        // Check relative to current directory
        if (file_exists(__DIR__ . '/' . $path)) {
            return $path;
        }
    }
    
    return 'images/logo.jpg'; // Default fallback
}

// Fetch the circular details from the posts table
$sql = "SELECT id, title, content, featured_image, publish_date FROM posts WHERE id = ? AND status = 'published'";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $circular_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: circular.php?error=not_found&tab=circulars');
    exit;
}

$circular = $result->fetch_assoc();
$stmt->close();

// Get correct image path
$circular['featured_image'] = getImagePath($circular['featured_image']);

// Format dates
$formatted_date = date("F j, Y", strtotime($circular['publish_date']));
$time = date("h:i A", strtotime($circular['publish_date']));
$datetime_iso = date("c", strtotime($circular['publish_date']));

// Get next and previous circulars for navigation
$prev_next_sql = "SELECT id, title FROM posts WHERE status = 'published' AND publish_date < ? ORDER BY publish_date DESC LIMIT 1";
$stmt_prev = $conn->prepare($prev_next_sql);
$stmt_prev->bind_param("s", $circular['publish_date']);
$stmt_prev->execute();
$prev_result = $stmt_prev->get_result();
$prev_circular = $prev_result->fetch_assoc();
$stmt_prev->close();

$next_sql = "SELECT id, title FROM posts WHERE status = 'published' AND publish_date > ? ORDER BY publish_date ASC LIMIT 1";
$stmt_next = $conn->prepare($next_sql);
$stmt_next->bind_param("s", $circular['publish_date']);
$stmt_next->execute();
$next_result = $stmt_next->get_result();
$next_circular = $next_result->fetch_assoc();
$stmt_next->close();

// Debug info (comment out in production)
// echo "<!-- Debug: Image path = " . htmlspecialchars($circular['featured_image']) . " -->";
// echo "<!-- Debug: File exists = " . (file_exists($circular['featured_image']) ? 'Yes' : 'No') . " -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($circular['title']); ?> - Wijeya Newspapers</title>
    
    <!-- Meta tags -->
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($circular['content']), 0, 160)); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($circular['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($circular['content']), 0, 160)); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <?php if (!empty($circular['featured_image']) && $circular['featured_image'] != 'images/logo.jpg'): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($circular['featured_image']); ?>">
    <?php endif; ?>
    <meta property="article:published_time" content="<?php echo $datetime_iso; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <!-- Google Fonts - Fallback to system fonts -->
    <!-- jsPDF -->
    <script src="assets/js/jspdf.umd.min.js"></script>
    <script src="assets/js/html2canvas.min.js"></script>
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
            line-height: 1.8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }
        
        /* Enhanced Header */
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
            padding: 1.5rem 0;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-wrapper {
            position: relative;
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 15px;
            padding: 8px;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
        }
        
        /* .logo-wrapper:hover {
            transform: rotate(5deg) scale(1.05);
            box-shadow: var(--shadow-heavy);
        } */
        
        .logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 10px;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .company-name {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            background: linear-gradient(to right, #ffffff 0%, #e3f2fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .circular-title-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Main Content */
        .main-container {
            flex: 1;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            width: 100%;
        }
        
        /* Breadcrumb */
        .breadcrumb-container {
            background: white;
            border-radius: 15px;
            padding: 1.25rem 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(0, 86, 179, 0.1);
        }
        
        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .breadcrumb-item a {
            color: var(--primary-blue);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .breadcrumb-item a:hover {
            background: var(--light-blue);
            transform: translateY(-2px);
        }
        
        .breadcrumb-item.active {
            color: #666;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .breadcrumb-separator {
            color: #999;
        }
        
        /* Circular Content */
        .circular-container {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: var(--shadow-medium);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 86, 179, 0.1);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .circular-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-heavy);
        }
        
        .circular-header {
            background: var(--gradient-secondary);
            padding: 2.5rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .circular-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.1;
        }
        
        .circular-title-main {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
        }
        
        .circular-meta {
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .meta-item:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .meta-item i {
            font-size: 1.2rem;
        }
        
        /* Featured Image */
        .featured-image-container {
            position: relative;
            height: 450px;
            overflow: hidden;
            margin: 0 2.5rem;
            border-radius: 20px;
            transform: translateY(-25px);
            box-shadow: var(--shadow-heavy);
            z-index: 3;
            background: var(--light-blue);
        }
        
        .featured-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s ease;
            background: linear-gradient(135deg, var(--light-blue) 0%, #f0f8ff 100%);
        }
        
        .featured-image-container:hover .featured-image {
            transform: scale(1.05);
        }
        
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
            color: white;
            padding: 2rem;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .featured-image-container:hover .image-overlay {
            transform: translateY(-10px);
        }
        
        /* Image Fallback Styling */
        .image-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--light-blue) 0%, #f0f8ff 100%);
        }
        
        .image-fallback-icon {
            font-size: 5rem;
            color: var(--accent-blue);
            margin-bottom: 1.5rem;
        }
        
        .image-fallback-text {
            color: var(--dark-blue);
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .image-fallback-subtext {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        /* Circular Body */
        .circular-body {
            padding: 3rem 2.5rem;
        }
        
        .content-wrapper {
            font-size: 1.15rem;
            line-height: 1.9;
            color: #444;
        }
        
        .content-wrapper h2 {
            color: var(--dark-blue);
            margin: 2.5rem 0 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid var(--light-blue);
        }
        
        .content-wrapper h3 {
            color: var(--secondary-blue);
            margin: 2rem 0 1rem;
        }
        
        .content-wrapper p {
            margin-bottom: 1.75rem;
        }
        
        .content-wrapper ul, .content-wrapper ol {
            margin-bottom: 1.75rem;
            padding-left: 2rem;
        }
        
        .content-wrapper li {
            margin-bottom: 0.75rem;
        }
        
        .content-wrapper blockquote {
            border-left: 5px solid var(--primary-blue);
            padding-left: 2rem;
            margin: 2.5rem 0;
            font-style: italic;
            color: #555;
            background: var(--light-blue);
            padding: 2rem;
            border-radius: 0 15px 15px 0;
            box-shadow: var(--shadow-light);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 3rem;
            padding-top: 2.5rem;
            border-top: 2px solid #eef2f7;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-heavy);
            color: white;
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: transparent;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-3px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-light);
        }
        
        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-heavy);
            color: white;
        }
        
        /* Navigation */
        .circular-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            gap: 2rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            flex: 1;
            background: white;
            box-shadow: var(--shadow-light);
            border: 1px solid #eef2f7;
        }
        
        .nav-item:hover {
            background: var(--light-blue);
            transform: translateX(10px);
            text-decoration: none;
            color: var(--primary-blue);
            box-shadow: var(--shadow-medium);
        }
        
        .nav-item.prev:hover {
            transform: translateX(-10px);
        }
        
        .nav-icon {
            font-size: 1.75rem;
            color: var(--primary-blue);
            width: 60px;
            height: 60px;
            background: var(--light-blue);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-content h5 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nav-content h4 {
            font-size: 1.1rem;
            color: inherit;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
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
        
        /* Scroll to Top Button */
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
        
        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        
        .toast-custom {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow-heavy);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .company-name {
                font-size: 1.5rem;
            }
            
            .logo-wrapper {
                width: 60px;
                height: 60px;
            }
            
            .circular-title-main {
                font-size: 1.8rem;
            }
            
            .circular-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .featured-image-container {
                height: 250px;
                margin: 0 1.5rem;
            }
            
            .circular-body {
                padding: 2rem 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .circular-navigation {
                flex-direction: column;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
        
        @media print {
            .action-buttons,
            .circular-navigation,
            .main-header,
            .main-footer,
            .scroll-top {
                display: none !important;
            }
            
            .circular-container {
                box-shadow: none !important;
                border: none !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
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
                <div class="d-flex align-items-center justify-content-between">
                    <div class="logo-container">
                        <div class="logo-wrapper">
                            <img src="images/logo.jpg" alt="Wijeya Newspapers" class="logo">
                        </div>
                        <div>
                            <h1 class="company-name">WIJEYA NEWSPAPERS</h1>
                            <div class="circular-title-header">
                                <i class="fas fa-file-alt me-2"></i>
                                <?php echo htmlspecialchars($circular['title']); ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="circular.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Circulars
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb-custom">
                    <li class="breadcrumb-item">
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="breadcrumb-separator">
                        <i class="fas fa-chevron-right"></i>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="circular.php">
                            <i class="fas fa-newspaper"></i>
                            <span>Circulars</span>
                        </a>
                    </li>
                    <li class="breadcrumb-separator">
                        <i class="fas fa-chevron-right"></i>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-file-alt"></i>
                        <span><?php echo htmlspecialchars($circular['title']); ?></span>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Circular Content -->
        <div class="circular-container">
            <div class="circular-header">
                <h1 class="circular-title-main"><?php echo htmlspecialchars($circular['title']); ?></h1>
                <div class="circular-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo $formatted_date; ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $time; ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-hashtag"></i>
                        <span>ID: CIR-<?php echo str_pad($circular['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
            </div>

            <!-- Featured Image -->
<div class="featured-image-container">
    <?php 
    $image_src = htmlspecialchars($circular['featured_image']);
    $image_alt = htmlspecialchars($circular['title']);
    $is_logo = ($circular['featured_image'] == 'images/logo.jpg');
    
    // Debug output (remove in production)
    // echo "<!-- Debug: Image path = $image_src -->";
    // echo "<!-- Debug: Is logo = " . ($is_logo ? 'Yes' : 'No') . " -->";
    // echo "<!-- Debug: File exists = " . (file_exists($circular['featured_image']) ? 'Yes' : 'No') . " -->";
    
    // Try to find the image in multiple locations
    $found_image = false;
    $actual_image_path = '';
    
    if (!empty($circular['featured_image']) && $circular['featured_image'] != 'images/logo.jpg') {
        $possible_paths = [
            $circular['featured_image'],
            'images/' . basename($circular['featured_image']),
            'images/circulars/' . basename($circular['featured_image']),
            'uploads/' . basename($circular['featured_image']),
            '../images/' . basename($circular['featured_image']),
            'images/posts/' . basename($circular['featured_image'])
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $found_image = true;
                $actual_image_path = $path;
                break;
            }
        }
    }
    
    if ($found_image && !$is_logo): 
    ?>
        <img src="<?php echo htmlspecialchars($actual_image_path); ?>" 
             alt="<?php echo $image_alt; ?>" 
             class="featured-image"
             onerror="this.onerror=null; this.src='images/logo.jpg'; this.classList.add('image-error');">
        <div class="image-overlay">
            <h4><?php echo htmlspecialchars($circular['title']); ?></h4>
            <p class="mb-0">Published on <?php echo $formatted_date; ?></p>
        </div>
    <?php else: ?>
        <div class="image-fallback">
            <i class="fas fa-image image-fallback-icon"></i>
            <div class="image-fallback-text">Circular Image</div>
            <div class="image-fallback-subtext">
                <?php if ($is_logo): ?>
                    Using company logo as featured image
                <?php else: ?>
                    No featured image available
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

            <!-- Circular Body -->
            <div class="circular-body">
                <div class="content-wrapper">
                    <?php echo $circular['content']; ?>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="circular.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i>Back to List
                    </a>
                    <button class="btn btn-primary" onclick="printCircular()">
                        <i class="fas fa-print me-1"></i>Print Circular
                    </button>
                    <button class="btn btn-danger" id="downloadPdfBtn" onclick="generatePDF()">
                        <i class="fas fa-download me-1"></i>
                        <span id="btn-text">Download PDF</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Circular Navigation -->
        <div class="circular-navigation">
            <?php if ($prev_circular): ?>
            <a href="circular_detail.php?id=<?php echo $prev_circular['id']; ?>" class="nav-item prev">
                <div class="nav-icon">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="nav-content">
                    <h5>Previous Circular</h5>
                    <h4><?php echo htmlspecialchars($prev_circular['title']); ?></h4>
                </div>
            </a>
            <?php else: ?>
            <div class="nav-item prev text-muted">
                <div class="nav-icon">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="nav-content">
                    <h5>Previous Circular</h5>
                    <h4>No older circulars</h4>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($next_circular): ?>
            <a href="circular_detail.php?id=<?php echo $next_circular['id']; ?>" class="nav-item next">
                <div class="nav-content">
                    <h5>Next Circular</h5>
                    <h4><?php echo htmlspecialchars($next_circular['title']); ?></h4>
                </div>
                <div class="nav-icon">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            <?php else: ?>
            <div class="nav-item next text-muted">
                <div class="nav-content">
                    <h5>Next Circular</h5>
                    <h4>No newer circulars</h4>
                </div>
                <div class="nav-icon">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
            <?php endif; ?>
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

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Print functionality
        function printCircular() {
            window.print();
        }
        
        // PDF Generation
        async function generatePDF() {
            const btn = document.getElementById('downloadPdfBtn');
            const btnText = document.getElementById('btn-text');
            const originalText = btnText.innerHTML;
            
            // Show loading state
            btn.disabled = true;
            btnText.innerHTML = '<span class="loading-spinner"></span> Generating PDF...';
            
            try {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                // Add watermark
                pdf.setFontSize(40);
                pdf.setTextColor(230, 230, 230);
                pdf.setFont('helvetica', 'bold');
                pdf.text('WIJEYA', 105, 150, { align: 'center', angle: 45 });
                
                // Reset text color
                pdf.setTextColor(0, 0, 0);
                
                // Add header
                pdf.setFontSize(24);
                pdf.setFont('helvetica', 'bold');
                pdf.setTextColor(0, 86, 179);
                pdf.text('WIJEYA NEWSPAPERS', 105, 20, { align: 'center' });
                
                pdf.setFontSize(16);
                pdf.setTextColor(70, 130, 180);
                pdf.text('Internal Communication Circular', 105, 30, { align: 'center' });
                
                // Add separator line
                pdf.setLineWidth(0.5);
                pdf.setDrawColor(0, 86, 179);
                pdf.line(20, 35, 190, 35);
                
                // Add circular title
                pdf.setFontSize(14);
                pdf.setTextColor(0, 0, 0);
                pdf.setFont('helvetica', 'bold');
                const title = "<?php echo addslashes($circular['title']); ?>";
                const titleLines = pdf.splitTextToSize(title, 170);
                pdf.text(titleLines, 20, 50);
                
                // Add metadata
                pdf.setFontSize(10);
                pdf.setFont('helvetica', 'normal');
                pdf.text(`Date: <?php echo $formatted_date; ?>`, 20, 70);
                pdf.text(`Time: <?php echo $time; ?>`, 20, 76);
                pdf.text(`Document ID: CIR-<?php echo str_pad($circular['id'], 5, '0', STR_PAD_LEFT); ?>`, 20, 82);
                
                // Add content
                pdf.setFontSize(11);
                const content = `<?php echo addslashes(strip_tags($circular['content'])); ?>`;
                const contentLines = pdf.splitTextToSize(content, 170);
                
                let yPosition = 90;
                const pageHeight = pdf.internal.pageSize.height;
                
                for (let i = 0; i < contentLines.length; i++) {
                    if (yPosition > pageHeight - 20) {
                        pdf.addPage();
                        yPosition = 20;
                        
                        // Add page header
                        pdf.setFontSize(10);
                        pdf.setTextColor(100, 100, 100);
                        pdf.text(`Circular: ${title}`, 105, 10, { align: 'center' });
                        pdf.setFontSize(11);
                        pdf.setTextColor(0, 0, 0);
                    }
                    pdf.text(contentLines[i], 20, yPosition);
                    yPosition += 7;
                }
                
                // Add footer
                const pageCount = pdf.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    pdf.setPage(i);
                    pdf.setFontSize(8);
                    pdf.setTextColor(100, 100, 100);
                    pdf.text(`Page ${i} of ${pageCount}`, 105, 287, { align: 'center' });
                    pdf.text('CONFIDENTIAL - Internal Use Only', 105, 292, { align: 'center' });
                }
                
                // Save the PDF
                const fileName = `Wijeya_Circular_${"<?php echo str_pad($circular['id'], 5, '0', STR_PAD_LEFT); ?>"}_<?php echo date('Y-m-d', strtotime($circular['publish_date'])); ?>.pdf`;
                pdf.save(fileName);
                
                // Show success toast
                showToast('PDF generated successfully!', 'success');
                
            } catch (error) {
                console.error('PDF generation error:', error);
                showToast('Error generating PDF. Please try again.', 'error');
            } finally {
                // Reset button state
                btn.disabled = false;
                btnText.innerHTML = originalText;
            }
        }
        
        // Toast notification
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();
            
            const toast = document.createElement('div');
            toast.className = `toast toast-custom border-0 ${type === 'success' ? 'bg-success' : 'bg-danger'}`;
            toast.id = toastId;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="toast-header ${type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'} border-0">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body bg-white">
                    ${message}
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, {
                animation: true,
                autohide: true,
                delay: 3000
            });
            
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Observe elements
            document.querySelectorAll('.circular-container, .nav-item, .footer-section').forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(element);
            });
            
            // Add hover effects
            document.querySelectorAll('.btn, .nav-item, .social-link, .meta-item').forEach(element => {
                element.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                element.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Debug image loading
            document.querySelectorAll('img').forEach(img => {
                img.addEventListener('error', function() {
                    console.log('Image failed to load:', this.src);
                    if (!this.classList.contains('image-error')) {
                        this.src = 'images/logo.jpg';
                        this.classList.add('image-error');
                    }
                });
            });
        });
    </script>
</body>
</html>