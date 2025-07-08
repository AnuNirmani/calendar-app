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
$datesQuery = $conn->query("SELECT date, type, description, color FROM special_dates");
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

        if ($dow == 0) $class = "sunday";
        else if ($dow == 6) $class = "saturday";

        $style = "";
        $tooltip = "";

        if (isset($specialDates[$dateStr])) {
            $type = $specialDates[$dateStr]['type'];
            $desc = htmlspecialchars($specialDates[$dateStr]['description']);
            $color = $specialDates[$dateStr]['color'];

            $tooltip = "<span class='tooltip'>$desc</span>";
            $style = "style='background-color: $color'";
        }

        // Highlight today
        $tdClass = $class;
        if ($dateStr == $today->format('Y-m-d')) {
            $tdClass .= " today";
        }

        echo "<td class='$tdClass' $style><div class='tooltip-wrapper'>" . sprintf('%02d', $d) . "$tooltip</div></td>";


        if ((($d + $pad) % 7) == 0) echo "</tr><tr>";
    }

    echo "</tr></table></div>";
}

include 'index.html';

?>