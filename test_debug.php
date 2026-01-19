<?php
include 'db.php';

echo "Database Connection: ";
if ($conn->connect_error) {
    echo "Failed - " . $conn->connect_error;
} else {
    echo "Success";
}
echo "<br>";

// Check posts table structure
echo "<h3>Posts Table Structure:</h3>";
$result = $conn->query("DESCRIBE posts");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}

// Check if there are any posts
echo "<h3>Posts Count:</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM posts");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total Posts: " . $row['total'];
} else {
    echo "Error: " . $conn->error;
}

// Check published posts
echo "<h3>Published Posts Count:</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM posts WHERE status = 'published'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Published Posts: " . $row['total'];
} else {
    echo "Error: " . $conn->error;
}

// Try the actual query
echo "<h3>Sample Query:</h3>";
$sql = "SELECT id, featured_image, publish_date, title, content FROM posts WHERE status = 'published' ORDER BY publish_date DESC LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    echo "Query successful. Rows: " . $result->num_rows . "<br>";
    while ($row = $result->fetch_assoc()) {
        echo "<pre>";
        echo "ID: " . $row['id'] . "\n";
        echo "Title: " . $row['title'] . "\n";
        echo "Featured Image: " . $row['featured_image'] . "\n";
        echo "Publish Date: " . $row['publish_date'] . "\n";
        echo "Content: " . substr($row['content'], 0, 100) . "...\n";
        echo "</pre>";
    }
} else {
    echo "Query Error: " . $conn->error;
}
?>
