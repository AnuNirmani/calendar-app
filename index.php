<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<?php
include 'db.php';

$today = new DateTime();

// Load special dates
// Update fetch to get color
$datesQuery = $conn->query("
    SELECT 
        sd.date, 
        sd.color, 
        st.type, 
        st.description 
    FROM 
        special_dates sd
    LEFT JOIN 
        special_types st ON sd.type_id = st.id
");

$specialDates = [];
while ($row = $datesQuery->fetch_assoc()) {
    $specialDates[$row['date']] = [
        'type' => $row['type'],
        'description' => $row['description'],
        'color' => $row['color']
    ];
}

// Function to render one month
function renderCalendar($month, $year, $specialDates, $today) {
    $firstDay = new DateTime("$year-$month-01");
    $daysInMonth = (int)$firstDay->format('t');
    $startWeekday = (int)$firstDay->format('w'); // Sunday = 0

    $pad = ($startWeekday + 6) % 7; // Adjust to Monday start

    echo "<div class='calendar-box'>";
    echo "<h3>" . $firstDay->format('F Y') . "</h3>";
    echo "<table>";
    echo "<tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr><tr>";

    for ($i = 0; $i < $pad; $i++) echo "<td></td>";

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateObj = DateTime::createFromFormat('Y-n-j', "$year-$month-$d");
        $dateStr = $dateObj->format('Y-m-d');
        $dow = (int)$dateObj->format('w');

        $class = "";
        $tooltip = "";

        // Check if it's today
        if ($dateStr === $today->format('Y-m-d')) {
            $class .= " today";
        }

        if ($dow == 0) $class .= " sunday";
        else if ($dow == 6) $class .= " saturday";

        $style = "";
        $tooltip = "";

        if (isset($specialDates[$dateStr])) {
            $type = $specialDates[$dateStr]['type'];
            $desc = htmlspecialchars($specialDates[$dateStr]['description']);
            $color = htmlspecialchars($specialDates[$dateStr]['color']);
            $tooltip = "<span class='tooltip'>$desc</span>";

            if (!empty($color)) {
                $style = "background: linear-gradient(135deg, $color 0%, " . adjustBrightness($color, 0.8) . " 100%);";
            }
        }

        // echo "<td class='$class' onclick=\"window.location.href='pdf.html'\" style='cursor: pointer; $style'>
        //     <div class='tooltip-wrapper'>" . sprintf('%02d', $d) . "$tooltip</div>
        //         </td>";

        $desc = htmlspecialchars($specialDates[$dateStr]['description'] ?? '');
        $color = $specialDates[$dateStr]['color'] ?? '';
        $dayText = sprintf('%02d', $d);

        echo "<td class='$class' onclick=\"window.location.href='pdf.html'\" title='$desc' style='cursor: pointer; color: black; $style'>$dayText</td>";


        if ((($d + $pad) % 7) == 0) echo "</tr><tr>";
    }

    echo "</tr></table></div>";
}

// Helper function to adjust color brightness
function adjustBrightness($hex, $percent) {
    // Remove # if present
    $hex = str_replace('#', '', $hex);
    
    // Convert to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Adjust brightness
    $r = max(0, min(255, $r * $percent));
    $g = max(0, min(255, $g * $percent));
    $b = max(0, min(255, $b * $percent));
    
    // Convert back to hex
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
                 str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
                 str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

include 'index.html';
?>