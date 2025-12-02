<?php
include 'db.php';

// Get circular ID from URL
$circular_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

if (!$circular_id) {
    header('Location: circular.php?error=invalid_id&tab=circulars');
    exit;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($circular['title']); ?> - Wijeya Newspapers</title>
    
    <!-- Meta tags for SEO and sharing -->
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($circular['content']), 0, 160)); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($circular['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($circular['content']), 0, 160)); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <?php if (!empty($circular['featured_image'])): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($circular['featured_image']); ?>">
    <?php endif; ?>
    <meta property="article:published_time" content="<?php echo $datetime_iso; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            line-height: 1.8;
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
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
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
            height: 50px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .company-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        /* Breadcrumb */
        .breadcrumb-container {
            background: white;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-custom .breadcrumb-item {
            font-size: 0.9rem;
        }
        
        .breadcrumb-custom .breadcrumb-item a {
            color: var(--wijeya-primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb-custom .breadcrumb-item a:hover {
            color: var(--wijeya-accent);
            text-decoration: underline;
        }
        
        .breadcrumb-custom .breadcrumb-item.active {
            color: #666;
        }
        
        /* Circular Header */
        .circular-header {
            margin-bottom: 2rem;
        }
        
        .circular-title {
            font-size: 2.5rem;
            color: var(--wijeya-dark);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .circular-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            color: #666;
            font-size: 0.95rem;
            padding: 1rem 0;
            border-bottom: 2px solid var(--wijeya-light);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .meta-item i {
            color: var(--wijeya-primary);
        }
        
        /* Circular Content */
        .circular-content-wrapper {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: 1px solid #eef2f7;
        }
        
        .featured-image-container {
            position: relative;
            height: 400px;
            overflow: hidden;
        }
        
        .featured-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s ease;
        }
        
        .featured-image-container:hover .featured-image {
            transform: scale(1.03);
        }
        
        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
            color: white;
            padding: 2rem;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .featured-image-container:hover .image-overlay {
            transform: translateY(-10px);
        }
        
        .circular-body {
            padding: 3rem;
        }
        
        .content-area {
            font-size: 1.1rem;
            line-height: 1.9;
            color: #444;
        }
        
        .content-area h2 {
            color: var(--wijeya-dark);
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--wijeya-light);
        }
        
        .content-area h3 {
            color: var(--wijeya-accent);
            margin: 1.5rem 0 0.75rem;
        }
        
        .content-area p {
            margin-bottom: 1.5rem;
        }
        
        .content-area ul, .content-area ol {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }
        
        .content-area li {
            margin-bottom: 0.5rem;
        }
        
        .content-area blockquote {
            border-left: 4px solid var(--wijeya-primary);
            padding-left: 1.5rem;
            margin: 2rem 0;
            font-style: italic;
            color: #555;
            background: var(--wijeya-light);
            padding: 1.5rem;
            border-radius: 0 8px 8px 0;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eef2f7;
        }
        
        .btn-wijeya {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 86, 179, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-wijeya:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 86, 179, 0.3);
            color: white;
        }
        
        .btn-outline-wijeya {
            border: 2px solid var(--wijeya-primary);
            color: var(--wijeya-primary);
            background: transparent;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-outline-wijeya:hover {
            background: var(--wijeya-primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        /* Navigation */
        .circular-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            flex: 1;
            max-width: 45%;
        }
        
        .nav-item:hover {
            background: var(--wijeya-light);
            transform: translateX(5px);
            text-decoration: none;
            color: var(--wijeya-primary);
        }
        
        .nav-item.prev:hover {
            transform: translateX(-5px);
        }
        
        .nav-icon {
            font-size: 1.5rem;
            color: var(--wijeya-primary);
        }
        
        .nav-content h5 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .nav-content h4 {
            font-size: 1rem;
            color: inherit;
            margin: 0;
            line-height: 1.4;
        }
        
        /* Footer */
        .main-footer {
            background: var(--wijeya-dark);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
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
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .circular-title {
                font-size: 1.8rem;
            }
            
            .circular-body {
                padding: 1.5rem;
            }
            
            .featured-image-container {
                height: 250px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .circular-navigation {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-item {
                max-width: 100%;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
        
        @media print {
            .action-buttons, 
            .circular-navigation,
            .main-header,
            .main-footer {
                display: none !important;
            }
            
            .circular-content-wrapper {
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
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <img src="images/logo.jpg" alt="Wijeya Newspapers" class="logo">
                    <div class="company-name">WIJEYA NEWSPAPERS</div>
                </div>
                <div>
                    <a href="circular.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Circulars
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-custom">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="circular.php">Circulars</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($circular['title']); ?>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Circular Header -->
        <div class="circular-header">
            <h1 class="circular-title"><?php echo htmlspecialchars($circular['title']); ?></h1>
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
                    <i class="fas fa-file-alt"></i>
                    <span>Internal Circular</span>
                </div>
            </div>
        </div>

        <!-- Circular Content -->
        <div class="circular-content-wrapper" id="circular-content">
            <!-- Featured Image -->
            <?php if (!empty($circular['featured_image'])): ?>
            <div class="featured-image-container">
                <img src="<?php echo htmlspecialchars($circular['featured_image']); ?>" 
                     alt="<?php echo htmlspecialchars($circular['title']); ?>" 
                     class="featured-image"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center h-100 bg-light\'><div class=\'text-center p-4\'><i class=\'fas fa-image fa-3x text-muted mb-3\'></i><h4>Circular Image</h4><p class=\'text-muted mb-0\'>No featured image available</p></div></div>'">
                <div class="image-overlay">
                    <h3><?php echo htmlspecialchars($circular['title']); ?></h3>
                    <p class="mb-0">Published on <?php echo $formatted_date; ?></p>
                </div>
            </div>
            <?php else: ?>
            <div class="featured-image-container bg-light d-flex align-items-center justify-content-center">
                <div class="text-center p-4">
                    <i class="fas fa-image fa-4x text-muted mb-3"></i>
                    <h4>Circular Image</h4>
                    <p class="text-muted mb-0">No featured image available</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Circular Body -->
            <div class="circular-body">
                <div class="content-area">
                    <?php echo $circular['content']; ?>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="circular.php" class="btn btn-outline-wijeya">
                        <i class="fas fa-arrow-left me-1"></i>Back to List
                    </a>
                    <button class="btn btn-wijeya" onclick="printCircular()">
                        <i class="fas fa-print me-1"></i>Print Circular
                    </button>
                    <button class="btn btn-danger" id="downloadPdfBtn" onclick="generatePDF()">
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
                <div class="nav-content text-right">
                    <h5>Next Circular</h5>
                    <h4><?php echo htmlspecialchars($next_circular['title']); ?></h4>
                </div>
                <div class="nav-icon">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            <?php else: ?>
            <div class="nav-item next text-muted text-right">
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
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    <i class="far fa-copyright me-1"></i>
                    <?php echo date('Y'); ?> Wijeya Newspapers Ltd. All Rights Reserved.
                </div>
                <div class="text-end">
                    <small>Document ID: CIR-<?php echo str_pad($circular['id'], 5, '0', STR_PAD_LEFT); ?></small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Print functionality
        function printCircular() {
            window.print();
        }
        
        // PDF Generation with jsPDF
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
                pdf.setTextColor(0, 86, 179); // Primary blue
                pdf.text('WIJEYA NEWSPAPERS', 105, 20, { align: 'center' });
                
                pdf.setFontSize(16);
                pdf.setTextColor(70, 130, 180); // Accent blue
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
        
        // Toast notification function
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
        
        // Add scroll animation
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.circular-content-wrapper, .circular-navigation');
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });
            
            elements.forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(element);
            });
            
            // Add click animation to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!this.disabled) {
                        this.style.transform = 'scale(0.98)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
            });
        });
    </script>
</body>
</html>