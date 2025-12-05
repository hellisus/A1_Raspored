<?php
require  'config.php';

if (!isset($_SESSION['Ime'])) {
    header("location:../index.php");
}

// --- Logic for Schedule ---

// 1. Get Filters
// Support for multiple months: input name 'month[]'
// If no month is selected, default to current month
if (isset($_GET['month']) && is_array($_GET['month'])) {
    $selectedMonths = array_map('intval', $_GET['month']);
} elseif (isset($_GET['month']) && !is_array($_GET['month'])) {
    // Fallback for single value
    $selectedMonths = [(int)$_GET['month']];
} else {
    $selectedMonths = [(int)date('n')];
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// 2. Fetch Data
$crud = new CRUD('srnalozi_a1_raspored');

// Build placeholder string for IN clause (e.g., "?,?,?")
$placeholders = implode(',', array_fill(0, count($selectedMonths), '?'));

$sql = "SELECT * FROM glavna_tabela 
        WHERE MONTH(`Scheduled start`) IN ($placeholders) 
          AND YEAR(`Scheduled start`) = ? 
          AND (`Current state` IS NULL OR `Current state` NOT IN ('Finalized', 'Canceled'))
        ORDER BY `Scheduled start` ASC";

// Combine parameters: months first, then year
$params = array_merge($selectedMonths, [$year]);

$stmt = $crud->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Group Data
// Structure: $schedule[week_number][assignee][date_string] = [job1, job2...]
$schedule = [];
$assigneesInMonth = [];
$cities = [];

foreach ($jobs as $job) {
    // Clean city name to ensure matching
    if (isset($job['City'])) {
        $job['City'] = trim($job['City']);
    }

    $assignee = !empty($job['Assignees']) ? $job['Assignees'] : 'Unassigned';
    $assigneesInMonth[$assignee] = true;
    
    $dateObj = new DateTime($job['Scheduled start']);
    $dateStr = $dateObj->format('Y-m-d');
    $weekNum = $dateObj->format('W');
    $weekYear = $dateObj->format('o'); // ISO-8601 week-numbering year
    
    $weekKey = $weekYear . '-' . $weekNum;
    
    if (!isset($schedule[$weekKey])) {
        $schedule[$weekKey] = [];
    }
    if (!isset($schedule[$weekKey][$assignee])) {
        $schedule[$weekKey][$assignee] = [];
    }
    if (!isset($schedule[$weekKey][$assignee][$dateStr])) {
        $schedule[$weekKey][$assignee][$dateStr] = [];
    }
    
    $schedule[$weekKey][$assignee][$dateStr][] = $job;
    
    if (!empty($job['City'])) {
        $cities[$job['City']] = true;
    }
}

// 4. Color Generation for Cities
// Specific colors for requested cities, others use consistent hashing
$specificColors = [
    'Novi Sad' => '#e0e0e0',      // Svetlo siva
    'Subotica' => '#ffadad',      // Crvena (pastelna)
    'Zrenjanin' => '#a0c4ff',     // Plava (pastelna)
    'Ruma' => '#ffd6a5',          // Narandžasta (pastelna)
    'Bačka Palanka' => '#caffbf'  // Zelena (pastelna)
];

// Create normalized map for case-insensitive matching
$normalizedSpecificColors = [];
foreach ($specificColors as $key => $val) {
    $normalizedSpecificColors[mb_strtolower($key, 'UTF-8')] = $val;
}

$predefinedColors = [
    '#ffadad', '#ffd6a5', '#fdffb6', '#caffbf', '#9bf6ff', '#a0c4ff', '#bdb2ff', '#ffc6ff', '#fffffc',
    '#e5e5e5', '#f0f0f0', '#d4d4d4', '#ffcccc', '#ccffcc', '#ccccff', '#ffe5b4', '#e6e6fa', '#f0fff0'
];

$cityColors = [];
foreach (array_keys($cities) as $city) {
    // Check if specific color is defined for this city (case-insensitive)
    $normalizedCity = mb_strtolower($city, 'UTF-8');
    
    if (isset($normalizedSpecificColors[$normalizedCity])) {
        $cityColors[$city] = $normalizedSpecificColors[$normalizedCity];
    } else {
        // CRC32 hash of city name for consistent index
        // abs() ensures positive number
        $hash = abs(crc32($city));
        $index = $hash % count($predefinedColors);
        $cityColors[$city] = $predefinedColors[$index];
    }
}

// Sort weeks and assignees
ksort($schedule);
$uniqueAssignees = array_keys($assigneesInMonth);
sort($uniqueAssignees);

// Helper to get days of week for a specific week number
function getDaysInWeek($year, $week) {
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $ret = [];
    
    // Serbian day names
    $daysSerbian = ['Pon', 'Uto', 'Sre', 'Čet', 'Pet', 'Sub', 'Ned'];
    
    for ($i = 0; $i < 7; $i++) {
        $dayIndex = (int)$dto->format('N') - 1; // 1 (Mon) - 7 (Sun) -> 0 - 6
        $ret[] = [
            'date' => $dto->format('Y-m-d'),
            'display' => $daysSerbian[$dayIndex] . ' ' . $dto->format('d.m'), // Pon 25.11
            'display_full' => $dto->format('l d.m.Y')
        ];
        $dto->modify('+1 day');
    }
    return $ret;
}

?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

    <title>A1 raspored - Raspored</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <!-- jQuery UI for Drag and Drop -->
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="../src/js/funkcije.js"></script>
  
  <style>
      .job-card {
          font-size: 0.85rem;
          border-radius: 4px;
          box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }
      .droppable-cell {
          min-height: 100px;
          height: 100%;
          vertical-align: top !important;
      }
      .day-container {
          min-height: 80px;
      }
      .ui-sortable-placeholder {
          border: 1px dashed #ccc;
          visibility: visible !important;
          background: #f0f0f0;
          height: 50px;
      }
      /* Select2 custom styles */
      .select2-container .select2-selection--multiple {
          min-height: 38px;
      }
  </style>

</head>

<body>
    <div class="wrapper">
        <!-- Sidebar  -->
        <?php require_once 'sidebar.php' ?>

        <!-- Page Content  -->
        <div id="content">
            <!-- Topbar  -->
            <?php require_once 'topbar.php' ?>
            <div class="container-fluid">
                
                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body py-2">
                        <form method="GET" class="form-inline">
                            <label class="mr-2">Mesec:</label>
                            <div class="mr-3" style="min-width: 300px;">
                                <select name="month[]" class="form-control select2-months" multiple="multiple" style="width: 100%;">
                                    <?php 
                                    // Serbian month names
                                    $monthsSerbian = [
                                        1 => 'Januar', 2 => 'Februar', 3 => 'Mart', 4 => 'April', 
                                        5 => 'Maj', 6 => 'Jun', 7 => 'Jul', 8 => 'Avgust', 
                                        9 => 'Septembar', 10 => 'Oktobar', 11 => 'Novembar', 12 => 'Decembar'
                                    ];
                                    for($m=1; $m<=12; $m++) {
                                        $selected = in_array($m, $selectedMonths) ? 'selected' : '';
                                        echo "<option value='$m' $selected>$m ({$monthsSerbian[$m]})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <label class="mr-2">Godina:</label>
                            <select name="year" class="form-control mr-3">
                                <?php 
                                $currentYear = date('Y');
                                for($y=$currentYear-2; $y<=$currentYear+2; $y++) {
                                    $selected = ($y == $year) ? 'selected' : '';
                                    echo "<option value='$y' $selected>$y</option>";
                                }
                                ?>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">Prikaži</button>
                            
                            <button type="button" class="btn btn-info ml-2" data-toggle="modal" data-target="#changesModal">
                                Lista izmena <span class="badge badge-light" id="changesCount">0</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Schedule Tables -->
                <?php if (empty($schedule)): ?>
                    <div class="alert alert-info">Nema podataka za izabrani period.</div>
                <?php else: ?>
                    <?php foreach ($schedule as $weekKey => $assigneeData): ?>
                        <?php 
                            list($wYear, $wNum) = explode('-', $weekKey);
                            $days = getDaysInWeek($wYear, $wNum);
                        ?>
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <strong>Nedelja od <?php echo date('d.m.Y', strtotime($days[0]['date'])); ?> do <?php echo date('d.m.Y', strtotime($days[6]['date'])); ?></strong>
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-bordered table-sm mb-0" style="table-layout: fixed; min-width: 1200px;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 150px;">Tim</th>
                                            <?php foreach ($days as $day): ?>
                                                <th class="text-center">
                                                    <?php echo $day['display']; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($uniqueAssignees as $assignee): ?>
                                            <tr>
                                                <td class="font-weight-bold align-middle"><?php echo htmlspecialchars($assignee); ?></td>
                                                <?php foreach ($days as $day): ?>
                                                    <td class="droppable-cell p-1" data-date="<?php echo $day['date']; ?>" data-assignee="<?php echo htmlspecialchars($assignee); ?>">
                                                        <div class="day-container h-100">
                                                        <?php 
                                                            if (isset($assigneeData[$assignee][$day['date']])) {
                                                                foreach ($assigneeData[$assignee][$day['date']] as $job) {
                                                                    $bgColor = isset($cityColors[$job['City']]) ? $cityColors[$job['City']] : '#ffffff';
                                                                    $originalDate = $day['date'];
                                                                    $originalAssigneeEncoded = htmlspecialchars($assignee);
                                                                    
                                                                    echo "<div class='job-card draggable-item p-2 mb-2' style='background-color: $bgColor; cursor:move;' data-id='{$job['ID']}' data-original-date='$originalDate' data-original-assignee='$originalAssigneeEncoded'>";
                                                                    
                                                                    // Time
                                                                    $time = date('H:i', strtotime($job['Scheduled start']));
                                                                    echo "<div><strong>$time</strong> <span class='badge badge-light float-right'>" . htmlspecialchars($job['City']) . "</span></div>";
                                                                    
                                                                    // Customer & Phone
                                                                    echo "<div class='text-truncate' title='" . htmlspecialchars($job['Customer Name']) . "'>";
                                                                    echo "<i class='fas fa-user'></i> " . htmlspecialchars($job['Customer Name']);
                                                                    echo "</div>";
                                                                    
                                                                    echo "<div><small><i class='fas fa-phone'></i> " . htmlspecialchars($job['Contact Phone On Location']) . "</small></div>";
                                                                    
                                                                    // Type
                                                                    echo "<div><small><strong>" . htmlspecialchars($job['WO_InstallationType']) . "</strong></small></div>";
                                                                    
                                                                    // Address
                                                                    echo "<div class='text-truncate' title='" . htmlspecialchars($job['Address']) . " " . htmlspecialchars($job['House Number']) . "'>";
                                                                    echo "<i class='fas fa-map-marker-alt'></i> " . htmlspecialchars($job['Address']) . " " . htmlspecialchars($job['House Number']);
                                                                    echo "</div>";
                                                                    
                                                                    // Adapter & WOID
                                                                    echo "<div class='mt-1 border-top pt-1'><small>Adapter: " . htmlspecialchars($job['Adapter ID']) . "</small></div>";
                                                                    echo "<div><small>WOID: " . htmlspecialchars($job['Woid']) . "</small></div>";
                                                                    
                                                                    echo "</div>";
                                                                }
                                                            }
                                                        ?>
                                                        </div>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="changesModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Lista izmena</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div id="changesList">
                <p class="text-muted">Nema izmena.</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" id="downloadChangesBtn">Snimi izmene (TXT)</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Zatvori</button>
          </div>
        </div>
      </div>
    </div>

    <script>
    $(function() {
        // Initialize Select2 for months
        $('.select2-months').select2({
            placeholder: "Izaberite mesece",
            allowClear: true,
            theme: 'bootstrap-5'
        });

        var changes = {};

        function updateChangesButton() {
            var count = Object.keys(changes).length;
            $('#changesCount').text(count);
        }

        $(".day-container").sortable({
            connectWith: ".day-container",
            placeholder: "ui-sortable-placeholder",
            tolerance: "pointer",
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height());
            },
            receive: function(event, ui) {
                var item = ui.item;
                var id = item.data('id');
                var originalDate = item.data('original-date');
                var originalAssignee = item.data('original-assignee');
                
                var newDate = $(this).closest('td').data('date');
                var newAssignee = $(this).closest('td').data('assignee');
                
                // Format date for display
                var dateParts = newDate.split('-');
                var displayDate = dateParts[2] + '.' + dateParts[1] + '.' + dateParts[0];
                
                var messages = [];
                if (newDate !== originalDate) {
                    messages.push("ponovo zakazati za dan " + displayDate);
                }
                if (newAssignee !== originalAssignee) {
                    messages.push("prebaciti na tim " + newAssignee);
                }

                // Check for time conflict in the new cell (same day, same assignee)
                var droppedTimeText = item.find('div:first strong').text().trim(); // e.g. "10:00"
                var conflictFound = false;
                
                // Iterate over other items in the same cell
                $(this).children('.job-card').each(function() {
                    if ($(this).data('id') == id) return; // Skip self
                    
                    var existingTimeText = $(this).find('div:first strong').text().trim();
                    if (existingTimeText === droppedTimeText) {
                        conflictFound = true;
                        return false; // break loop
                    }
                });

                if (conflictFound) {
                    messages.push("<span class='text-danger'>već postoji nalog sa istim vremenom (" + droppedTimeText + ")</span>");
                }
                
                if (messages.length > 0) {
                    // Get Customer Name for context
                    var customerName = item.find('.text-truncate').first().text().trim();
                    if(!customerName) customerName = "ID: " + id;

                    changes[id] = {
                        customer: customerName,
                        messages: messages
                    };
                } else {
                    delete changes[id];
                }
                
                updateChangesButton();
            }
        }).disableSelection();

        // On click button, populate modal
        $('[data-target="#changesModal"]').click(function() {
            var html = '<ul class="list-group">';
            var hasChanges = false;
            $.each(changes, function(id, data) {
               hasChanges = true;
               html += '<li class="list-group-item"><strong>' + data.customer + ' (ID: ' + id + ')</strong>: ' + data.messages.join(', ') + '</li>';
            });
            html += '</ul>';
            if (!hasChanges) {
                html = '<p class="text-muted">Nema izmena.</p>';
                $('#downloadChangesBtn').prop('disabled', true);
            } else {
                $('#downloadChangesBtn').prop('disabled', false);
            }
            $('#changesList').html(html);
        });

        // Download as TXT
        $('#downloadChangesBtn').click(function() {
            var textContent = "Lista izmena - " + new Date().toLocaleString() + "\n\n";
            $.each(changes, function(id, data) {
                textContent += data.customer + " (ID: " + id + "):\n";
                $.each(data.messages, function(i, msg) {
                    // Strip HTML tags for text file
                    var plainMsg = msg.replace(/<\/?[^>]+(>|$)/g, "");
                    textContent += " - " + plainMsg + "\n";
                });
                textContent += "\n";
            });

            var blob = new Blob([textContent], { type: "text/plain;charset=utf-8" });
            
            // Generate filename: izmene_YYYY-MM-DD_HH-MM.txt
            var now = new Date();
            var year = now.getFullYear();
            var month = String(now.getMonth() + 1).padStart(2, '0');
            var day = String(now.getDate()).padStart(2, '0');
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var filename = "izmene_" + year + "-" + month + "-" + day + "_" + hours + "-" + minutes + ".txt";

            if (window.navigator && window.navigator.msSaveOrOpenBlob) { // IE
                window.navigator.msSaveOrOpenBlob(blob, filename);
            } else {
                var link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    });
    </script>
</body>

</html>
