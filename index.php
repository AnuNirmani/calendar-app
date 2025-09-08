<?php
include 'db.php'; // Ensure db.php is in the same directory or adjust the path

// Define $today at the beginning
$today = new DateTime();

// Load special dates
$datesQuery = $conn->query("
    SELECT 
        sd.date, 
        sd.color, 
        st.type
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
        $desc = '';

        // today?
        if ($dateStr === $today->format('Y-m-d')) {
            $class .= " today";
        }

        if ($dow == 0) $class .= " sunday";
        else if ($dow == 6) $class .= " saturday";

        $style = "";

        if (isset($specialDates[$dateStr])) {
            $type  = $specialDates[$dateStr]['type'];
            $desc  = htmlspecialchars($specialDates[$dateStr]['description']);
            $color = htmlspecialchars($specialDates[$dateStr]['color']);
            $tooltip = "<span class='tooltip'>$desc</span>";

            if (!empty($color)) {
                $style = "background: linear-gradient(135deg, $color 0%, " . adjustBrightness($color, 0.8) . " 100%);";
            }
        }

        // ✅ Make sure $dayText is defined before use
        $dayText = sprintf('%02d', $d);
        $dayNumber = "<span class='day-number'>$dayText</span>";

        echo "<td class='$class' title='$desc' style='cursor: pointer; color: black; $style'
             onclick=\"window.open('https://time.wnl/source/" . str_replace('-', '/', $dateStr) . "/sheet.pdf', '_blank')\">
             $dayNumber$tooltip</td>";

        if ((($d + $pad) % 7) == 0) echo "</tr><tr>";
    }

    echo "</tr></table></div>";
}


// Helper function to adjust color brightness
function adjustBrightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r * $percent));
    $g = max(0, min(255, $g * $percent));
    $b = max(0, min(255, $b * $percent));
    
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
                 str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
                 str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
?>

<?php include 'index.html'; ?>