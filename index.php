<?php
include 'db.php';

$today = new DateTime();
$currentMonth = (int)$today->format('n');
$currentYear = (int)$today->format('Y');

// Get special dates from DB
$datesQuery = $conn->query("SELECT date, type FROM special_dates");
$specialDates = [];
while ($row = $datesQuery->fetch_assoc()) {
    $specialDates[$row['date']] = $row['type']; // e.g. '2025-04-14' => 'holiday'
}

// Function to render a month
function renderCalendar($month, $year, $specialDates, $today) {
    $firstDay = new DateTime("$year-$month-01");
    $daysInMonth = (int)$firstDay->format('t');
    $startWeekday = (int)$firstDay->format('w'); // 0 = Sunday

    $pad = ($startWeekday + 6) % 7; // Convert Sunday=0 to end of week

    echo "<div class='calendar-box'>";
    echo "<h3>" . $firstDay->format('F Y') . "</h3>";
    echo "<table>";
    echo "<tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr><tr>";

    for ($i = 0; $i < $pad; $i++) echo "<td></td>";

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateObj = DateTime::createFromFormat('Y-n-j', "$year-$month-$d");
        $dateStr = $dateObj->format('Y-m-d');
        $dow = (int)$dateObj->format('w'); // 0 = Sun, 6 = Sat

        $class = "";

        // Mark weekends
        if ($dow == 0) $class = "sunday"; // Yellow
        else if ($dow == 6) $class = "saturday"; // Pink

        // Override with DB dates
        if (isset($specialDates[$dateStr])) {
            $type = $specialDates[$dateStr];
            if ($type === 'holiday' || $type === 'poya') {
                $class = "holiday"; // Red for both
            }
        }

        // Highlight today
        if ($dateStr == $today->format('Y-m-d')) {
            $class .= ' today';
        }

        echo "<td class='$class'>" . sprintf('%02d', $d) . "</td>";

        if (($d + $pad) % 7 == 0) echo "</tr><tr>";
    }

    echo "</tr></table></div>";
}

include 'index.html';

?>

