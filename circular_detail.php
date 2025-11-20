<?php
include 'db.php';

// Get circular ID from URL
$circular_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

if (!$circular_id) {
    header('Location: circular.php?error=invalid_id');
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
    header('Location: circular.php?error=not_found');
    exit;
}

$circular = $result->fetch_assoc();
$stmt->close();

// Format the publish date
$formatted_date = date("F j, Y", strtotime($circular['publish_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <center> 
        <img src="images/logo.jpg" style="width: 250px;"> 
    </center>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($circular['title']); ?> - Wijeya Newspapers</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- jsPDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
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
        .container {
            padding-top: 4rem;
        }
        .back-btn {
            background-color: #4682B4;
            color: white;
            border: none;
        }
        .back-btn:hover {
            background-color: #315f87;
            color: white;
        }
        .title {
            color: #2c3e50;
        }
        .breadcrumb {
            background-color: #ffffff;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .breadcrumb-item a {
            color: #4682B4;
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            color: #315f87;
            text-decoration: underline;
        }
        .breadcrumb-item.active {
            color: #6c757d;
        }
        .circular-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .image-placeholder {
            background: linear-gradient(135deg, var(--wijeya-light) 0%, var(--wijeya-primary) 100%);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--wijeya-dark);
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            min-height: 250px;
        }
        
        /* PDF Download Button Enhancement */
        .btn-pdf {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-pdf:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
        }
        
        /* Loading state for PDF generation */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Content styling */
        .card-text {
            line-height: 1.8;
            color: #333;
        }

        .card-text ul {
            margin-left: 1.5rem;
        }

        .card-text li {
            margin-bottom: 0.5rem;
        }

        .publish-date {
            color: #6c757d;
            font-size: 0.95rem;
        }

        footer {
            background-color: var(--wijeya-dark);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        footer a {
            color: #ddd;
            text-decoration: none;
        }
        footer a:hover {
            color: white;
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
                    <li class="nav-item">
                        <a class="nav-link active" href="circular.php"><i class="fas fa-newspaper me-1"></i> Circulars</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
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
        
        <h1 class="title mb-4"><?php echo htmlspecialchars($circular['title']); ?></h1>
        
        <div class="card shadow-sm mb-4" id="circular-content">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($circular['title']); ?></h5>
                <p class="card-text publish-date"><strong>Date:</strong> <?php echo $formatted_date; ?></p>
                
                <!-- Circular Image -->
                <?php if (!empty($circular['featured_image'])): ?>
                    <img src="<?php echo htmlspecialchars($circular['featured_image']); ?>" alt="<?php echo htmlspecialchars($circular['title']); ?>" class="circular-image" onerror="this.src='images/logo.jpg'">
                <?php else: ?>
                    <div class="image-placeholder">
                        <div class="text-center">
                            <i class="fas fa-image fa-3x mb-3"></i>
                            <p class="mb-0">Circular Image</p>
                            <small class="text-muted">No featured image available</small>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Circular Content -->
                <div class="card-text">
                    <?php echo $circular['content']; ?>
                </div>
                
                <div class="mt-4">
                    <button class="btn back-btn" onclick="goBack()">
                        <i class="fas fa-arrow-left me-1"></i> Back to Circulars
                    </button>
                    <button class="btn btn-pdf ms-2" id="downloadPdfBtn" onclick="downloadPDF()">
                        <i class="fas fa-download me-1"></i> <span id="btn-text">Download PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer class="copyright-footer">
        <div class="container">
            <p>© Copyright WNL. All Rights Reserved</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function goBack() {
            window.history.back();
        }
        
        function downloadPDF() {
            const btn = document.getElementById('downloadPdfBtn');
            const btnText = document.getElementById('btn-text');
            const originalText = btnText.innerHTML;
            
            // Show loading state
            btn.classList.add('btn-loading');
            btn.disabled = true;
            btnText.innerHTML = 'Generating...';
            
            // Create new jsPDF instance
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF();
            
            // Set font
            pdf.setFont('helvetica');
            
            // Add company header
            pdf.setFontSize(20);
            pdf.setTextColor(70, 130, 180); // Steel blue color
            pdf.text('WIJEYA NEWSPAPERS', 105, 20, { align: 'center' });
            
            pdf.setFontSize(16);
            pdf.setTextColor(44, 62, 80); // Dark color
            pdf.text('Internal Communication Circular', 105, 30, { align: 'center' });
            
            // Add a line separator
            pdf.setLineWidth(0.5);
            pdf.setDrawColor(70, 130, 180);
            pdf.line(20, 35, 190, 35);
            
            // Add circular title
            pdf.setFontSize(14);
            pdf.setTextColor(0, 0, 0);
            pdf.text('<?php echo addslashes($circular['title']); ?>', 20, 50);
            
            // Add date
            pdf.setFontSize(12);
            pdf.text('Date: <?php echo $formatted_date; ?>', 20, 60);
            
            // Add main content (strip HTML tags for PDF)
            pdf.setFontSize(11);
            const contentText = `<?php echo addslashes(strip_tags($circular['content'])); ?>`;
            
            // Split long text into multiple lines
            const splitContent = pdf.splitTextToSize(contentText, 170);
            pdf.text(splitContent, 20, 75);
            
            // Add footer
            pdf.setFontSize(10);
            pdf.setTextColor(100, 100, 100);
            pdf.text('Wijeya Newspapers Ltd. - Internal Communications', 105, 280, { align: 'center' });
            pdf.text(`Generated on: ${new Date().toLocaleDateString()}`, 105, 287, { align: 'center' });
            
            // Simulate processing time
            setTimeout(() => {
                try {
                    // Save the PDF
                    pdf.save('<?php echo addslashes($circular['title']); ?>.pdf');
                    
                    // Reset button state
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                    btnText.innerHTML = originalText;
                    
                    // Show success message
                    showSuccessMessage();
                } catch (error) {
                    console.error('Error generating PDF:', error);
                    alert('Error generating PDF. Please try again.');
                    
                    // Reset button state
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                    btnText.innerHTML = originalText;
                }
            }, 1500);
        }
        
        function showSuccessMessage() {
            const successMsg = document.createElement('div');
            successMsg.className = 'alert alert-success alert-dismissible fade show position-fixed';
            successMsg.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            successMsg.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                PDF downloaded successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(successMsg);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (successMsg.parentNode) {
                    successMsg.remove();
                }
            }, 3000);
        }
    </script>
</body>
</html>
