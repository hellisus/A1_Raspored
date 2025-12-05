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

$sql = "SELECT * FROM lokalna_tabela 
        WHERE MONTH(`Scheduled start`) IN ($placeholders) 
          AND YEAR(`Scheduled start`) = ? 
          AND (`Current state` IS NULL OR `Current state` NOT IN ('Finalized', 'Canceled'))
        ORDER BY `Scheduled start` ASC";

// Combine parameters: months first, then year
$params = array_merge($selectedMonths, [$year]);

$stmt = $crud->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2b. Load reference data from glavna_tabela for comparison
$mainJobsById = [];
$jobIds = array_column($jobs, 'ID');
if (!empty($jobIds)) {
    $mainPlaceholders = implode(',', array_fill(0, count($jobIds), '?'));
    $mainSql = "SELECT * FROM glavna_tabela WHERE ID IN ($mainPlaceholders)";
    $mainStmt = $crud->prepare($mainSql);
    $mainStmt->execute($jobIds);
    $mainRows = $mainStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mainRows as $mainRow) {
        if (isset($mainRow['ID'])) {
            $mainJobsById[$mainRow['ID']] = $mainRow;
        }
    }
}

$comparisonFields = [
    'Scheduled start',
    'Assignees',
    'City',
    'Address',
    'House Number',
    'WO_InstallationType',
    'Customer Name',
    'Contact Phone On Location',
    'Adapter ID',
    'Woid'
];

foreach ($jobs as &$job) {
    $jobId = $job['ID'] ?? null;
    $hasDifference = false;

    if (!$jobId || !isset($mainJobsById[$jobId])) {
        $hasDifference = true;
    } else {
        $mainJob = $mainJobsById[$jobId];
        foreach ($comparisonFields as $field) {
            $localVal = isset($job[$field]) ? trim((string)$job[$field]) : '';
            $mainVal = isset($mainJob[$field]) ? trim((string)$mainJob[$field]) : '';

            if ($field === 'Scheduled start') {
                $localVal = $localVal !== '' ? date('Y-m-d H:i:s', strtotime($localVal)) : '';
                $mainVal = $mainVal !== '' ? date('Y-m-d H:i:s', strtotime($mainVal)) : '';
            }

            if ($localVal !== $mainVal) {
                $hasDifference = true;
                break;
            }
        }
    }

    $job['has_difference'] = $hasDifference;
}
unset($job);

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
natcasesort($uniqueAssignees);
$uniqueAssignees = array_values($uniqueAssignees);

$vanrednaTeamName = 'Vanredna ekipa';
// Uvek osiguraj da je Vanredna ekipa poslednja u koloni Tim
$uniqueAssignees = array_values(array_filter(
    $uniqueAssignees,
    function ($assignee) use ($vanrednaTeamName) {
        return $assignee !== $vanrednaTeamName;
    }
));
$uniqueAssignees[] = $vanrednaTeamName;

// Helper to get days of week for a specific week number
function getDaysInWeek($year, $week) {
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $ret = [];
    
    // Serbian day names
    $daysSerbian = ['Pon', 'Uto', 'Sre', 'Čet', 'Pet', 'Sub', 'Ned'];
    
    for ($i = 0; $i < 7; $i++) {
        $dayIndex = (int)$dto->format('N') - 1; // 1 (Mon) - 7 (Sun) -> 0 - 6
        $dayLabel = $daysSerbian[$dayIndex];
        $dateLabel = $dto->format('d.m');
        $ret[] = [
            'date' => $dto->format('Y-m-d'),
            'display' => $dayLabel . ' ' . $dateLabel, // Pon 25.11
            'display_day' => $dayLabel,
            'display_date' => $dateLabel,
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
    :root {
        --page-top-offset: 0px;
        --week-card-header-height: 44px;
        --table-header-row-height: 34px;
    }
       .schedule-table-wrapper {
           position: relative;
           overflow-x: auto;
       }
      .week-card-header {
           position: sticky;
           top: var(--page-top-offset, 0px);
           z-index: 9;
          min-height: var(--week-card-header-height, 44px);
          height: var(--week-card-header-height, 44px);
           display: flex;
           align-items: center;
       }
   .schedule-table-wrapper table thead th {
      position: static;
   }
  .table-sticky-row-1,
  .table-sticky-row-2 {
      background-color: #f8f9fa;
      box-shadow: inset 0 -1px 0 rgba(0,0,0,0.2);
  }
  .table-sticky-row-1 {
      position: sticky;
      top: calc(var(--page-top-offset, 0px) + var(--week-card-header-height, 44px));
      z-index: 8;
      min-height: var(--table-header-row-height, 34px);
  }
  .table-sticky-row-2 {
      position: sticky;
      top: calc(var(--page-top-offset, 0px) + var(--week-card-header-height, 44px) + var(--table-header-row-height, 34px));
      z-index: 7;
      min-height: var(--table-header-row-height, 34px);
  }
      .job-card {
          font-size: 0.82rem;
          border-radius: 4px;
          box-shadow: 0 1px 3px rgba(0,0,0,0.1);
          padding: 2px 6px;
          margin-bottom: 3px;
      }
      .job-card.job-card-diff {
          border: 2px solid #dc3545;
          box-shadow: 0 0 10px rgba(220,53,69,0.6);
          animation: pulseDiff 1.5s ease-in-out infinite;
      }
      @keyframes pulseDiff {
          0% {
              box-shadow: 0 0 6px rgba(220,53,69,0.5);
          }
          50% {
              box-shadow: 0 0 16px rgba(220,53,69,0.85);
          }
          100% {
              box-shadow: 0 0 6px rgba(220,53,69,0.5);
          }
      }
      .job-card-line-copied {
          animation: clipboardFlash 1s ease;
      }
      @keyframes clipboardFlash {
          0% { background-color: rgba(255, 230, 0, 0.6); }
          60% { background-color: rgba(255, 230, 0, 0.2); }
          100% { background-color: transparent; }
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
      .job-card.saving {
          opacity: 0.6;
          pointer-events: none;
      }
      /* Select2 custom styles */
      .select2-container .select2-selection--multiple {
          min-height: 38px;
      }
      .comment-trigger,
      .finalize-trigger {
          width: 26px;
          height: 26px;
          padding: 0;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          line-height: 1;
      }
      .comment-trigger.comment-empty {
          font-weight: bold;
      }
      .job-card .job-row {
          margin-bottom: 2px;
      }
      .job-card .job-row:last-child {
          margin-bottom: 0;
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
                            <div class="card-header bg-secondary text-white week-card-header">
                                <strong>Nedelja od <?php echo date('d.m.Y', strtotime($days[0]['date'])); ?> do <?php echo date('d.m.Y', strtotime($days[6]['date'])); ?></strong>
                            </div>
                            <div class="card-body p-0 table-responsive schedule-table-wrapper">
                                <table class="table table-bordered table-sm mb-0" style="table-layout: fixed; min-width: 1200px;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="table-sticky-row-1 align-middle" rowspan="2" style="width: 150px;">Tim</th>
                                            <?php foreach ($days as $day): ?>
                                                <th class="text-center table-sticky-row-1">
                                                    <?php echo $day['display_date']; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                        <tr>
                                            <?php foreach ($days as $day): ?>
                                                <th class="text-center table-sticky-row-2">
                                                    <?php echo $day['display_day']; ?>
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
                                                                    $time = date('H:i', strtotime($job['Scheduled start']));
                                                                    $timeAttr = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
                                                                    
                                                                    $diffClass = !empty($job['has_difference']) ? ' job-card-diff' : '';
                                                                    $adapterValue = isset($job['Adapter ID']) ? $job['Adapter ID'] : '';
                                                                    $adapterAttr = htmlspecialchars($adapterValue, ENT_QUOTES, 'UTF-8');
                                                                    echo "<div class='job-card draggable-item py-0 px-1 mb-1$diffClass' style='background-color: $bgColor; cursor:move;' data-id='{$job['ID']}' data-original-date='$originalDate' data-original-assignee='$originalAssigneeEncoded' data-start-time='$timeAttr' data-adapter='$adapterAttr'>";
                                                                    
                                                                    $comment = isset($job['Comment']) ? trim($job['Comment']) : '';
                                                                    $commentPreviewRaw = '+';
                                                                    if ($comment !== '') {
                                                                        $words = array_values(array_filter(preg_split('/\s+/', $comment)));
                                                                        $previewWords = array_slice($words, 0, 5);
                                                                        $commentPreviewRaw = implode(' ', $previewWords);
                                                                        if (count($words) > 5) {
                                                                            $commentPreviewRaw .= '...';
                                                                        }
                                                                    }
                                                                    $commentBtnClasses = ($comment !== '') ? 'btn-outline-secondary' : 'btn-outline-primary comment-empty';
                                                                    $commentDataAttr = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
                                                                    $commentBtnTitleRaw = ($comment !== '') ? $commentPreviewRaw : 'Dodaj komentar';
                                                                    $commentBtnTitle = htmlspecialchars($commentBtnTitleRaw, ENT_QUOTES, 'UTF-8');
                                                                    
                                                                    // Time + Comment trigger + City
                                                                    echo "<div class='d-flex align-items-center job-row'>";
                                                                    echo "<strong class='mr-2'>$time</strong>";
                                                                    echo "<button type='button' class='btn btn-sm comment-trigger $commentBtnClasses mx-1' data-id='{$job['ID']}' data-comment=\"{$commentDataAttr}\" title='{$commentBtnTitle}'>+</button>";
                                                                    echo "<button type='button' class='btn btn-sm btn-outline-danger finalize-trigger mx-1' data-id='{$job['ID']}' title='Označi kao Finalized (ukloni sa rasporeda)'>&times;</button>";
                                                                    echo "<span class='badge badge-light ml-auto'>" . htmlspecialchars($job['City']) . "</span>";
                                                                    echo "</div>";
                                                                    
                                                                    // Customer & Phone
                                                                    echo "<div class='text-truncate job-row' title='" . htmlspecialchars($job['Customer Name']) . "'>";
                                                                    echo "<i class='fas fa-user'></i> " . htmlspecialchars($job['Customer Name']);
                                                                    echo "</div>";
                                                                    
                                                                    echo "<div class='d-flex align-items-center job-row'>";
                                                                    echo "<small class='text-monospace mr-2'>" . htmlspecialchars($job['Woid']) . "</small>";
                                                                    echo "<small><i class='fas fa-phone'></i> " . htmlspecialchars($job['Contact Phone On Location']) . "</small>";
                                                                    echo "</div>";
                                                                    
                                                                    // Type
                                                                    echo "<div class='job-row'><small><strong>" . htmlspecialchars($job['WO_InstallationType']) . "</strong></small></div>";
                                                                    
                                                                    // Address
                                                                    echo "<div class='text-truncate job-row' title='" . htmlspecialchars($job['Address']) . " " . htmlspecialchars($job['House Number']) . "'>";
                                                                    echo "<i class='fas fa-map-marker-alt'></i> " . htmlspecialchars($job['Address']) . " " . htmlspecialchars($job['House Number']);
                                                                    echo "</div>";
                                                                    
                                                                    // Adapter moved to modal; WOID shown beside phone
                                                                    
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

    <!-- Comment Modal -->
    <div class="modal fade" id="commentModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Izmena naloga <span id="commentModalJobId"></span></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="alert alert-danger d-none" id="commentModalAlert"></div>
            <input type="hidden" id="commentJobIdInput" value="">
            
            <div class="form-group">
                <label for="editScheduledTo">Tim</label>
                <select id="editScheduledTo" class="form-control">
                    <!-- Biće popunjeno dinamički -->
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="editDate">Datum</label>
                    <input type="date" id="editDate" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="editTime">Vreme</label>
                    <input type="time" id="editTime" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="editAdapter">Adapter</label>
                <input type="text" id="editAdapter" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="commentText">Komentar</label>
                <textarea id="commentText" class="form-control" rows="3" placeholder="Unesite komentar..."></textarea>
            </div>

            <hr>
            <h6>Istorija izmena</h6>
            <div id="historyList" style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-radius: 4px;">
                <p class="text-muted small mb-0">Učitavanje istorije...</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Zatvori</button>
            <button type="button" class="btn btn-primary" id="commentModalSave">Sačuvaj</button>
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

        function getCustomerName(item, id) {
            var customerName = item.find('.text-truncate').first().text().trim();
            if (!customerName) {
                customerName = "ID: " + id;
            }
            return customerName;
        }

        function moveCardBackToOriginal(item, originalDate, originalAssignee) {
            var $targetCell = $('.droppable-cell').filter(function() {
                return $(this).data('date') === originalDate && $(this).data('assignee') === originalAssignee;
            }).first();

            if ($targetCell.length) {
                $targetCell.find('.day-container').append(item);
            }
        }

        function persistScheduleChange(payload) {
            return $.ajax({
                type: 'POST',
                url: 'funkcije/raspored_lokalna_update.php',
                dataType: 'json',
                data: payload
            });
        }
        
        function getCommentPreviewText(comment) {
            if (!comment) {
                return '+';
            }
            var words = comment.trim().split(/\s+/).filter(function(word) {
                return word.length > 0;
            });
            if (!words.length) {
                return '+';
            }
            var previewWords = words.slice(0, 5);
            var preview = previewWords.join(' ');
            if (words.length > 5) {
                preview += '...';
            }
            return preview;
        }

        var activeCommentBtn = null;

        function copyTextToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            }

            return new Promise(function(resolve, reject) {
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(text).select();
                try {
                    var successful = document.execCommand('copy');
                    $temp.remove();
                    if (successful) {
                        resolve();
                    } else {
                        reject(new Error('Copy failed'));
                    }
                } catch (err) {
                    $temp.remove();
                    reject(err);
                }
            });
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
                var $currentCell = $(this).closest('td');
                var newDate = $currentCell.data('date');
                var newAssignee = $currentCell.data('assignee');

                if (!newDate) {
                    return;
                }

                // Format date for display
                var dateParts = newDate.split('-');
                var displayDate = dateParts[2] + '.' + dateParts[1] + '.' + dateParts[0];

                var messages = [];
                var dateChanged = newDate !== originalDate;
                var assigneeChanged = newAssignee !== originalAssignee;

                if (dateChanged) {
                    messages.push("ponovo zakazati za dan " + displayDate);
                }
                if (assigneeChanged) {
                    messages.push("prebaciti na tim " + newAssignee);
                }

                // Check for time conflict in the new cell (same day, same assignee)
                var droppedTimeText = item.data('start-time') ? String(item.data('start-time')) : item.find('div:first strong').text().trim(); // e.g. "10:00"
                var conflictFound = false;
                
                // Iterate over other items in the same cell
                $(this).children('.job-card').each(function() {
                    if ($(this).data('id') == id) return; // Skip self
                    
                    var existingTimeText = $(this).data('start-time') ? String($(this).data('start-time')) : $(this).find('div:first strong').text().trim();
                    if (existingTimeText === droppedTimeText) {
                        conflictFound = true;
                        return false; // break loop
                    }
                });

                if (conflictFound) {
                    messages.push("<span class='text-danger'>već postoji nalog sa istim vremenom (" + droppedTimeText + ")</span>");
                }

                var customerName = getCustomerName(item, id);
                var hasMovement = dateChanged || assigneeChanged;

                if (!hasMovement) {
                    if (messages.length > 0) {
                        changes[id] = {
                            customer: customerName,
                            messages: messages
                        };
                    } else {
                        delete changes[id];
                    }
                    updateChangesButton();
                    return;
                }

                var payload = {
                    id: id,
                    date: newDate,
                    time: droppedTimeText,
                    assignee: newAssignee
                };

                item.addClass('saving');

                persistScheduleChange(payload)
                    .done(function() {
                        item.data('original-date', newDate);
                        item.attr('data-original-date', newDate);
                        item.data('original-assignee', newAssignee);
                        item.attr('data-original-assignee', newAssignee);

                        messages.push("<span class='text-success'>izmena snimljena u bazu</span>");

                        changes[id] = {
                            customer: customerName,
                            messages: messages
                        };
                    })
                    .fail(function(xhr) {
                        moveCardBackToOriginal(item, originalDate, originalAssignee);
                        var errorText = 'Došlo je do greške prilikom snimanja.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorText = xhr.responseJSON.error;
                        }

                        messages.push("<span class='text-danger'>" + errorText + "</span>");

                        changes[id] = {
                            customer: customerName,
                            messages: messages
                        };

                        item.data('original-date', originalDate);
                        item.attr('data-original-date', originalDate);
                        item.data('original-assignee', originalAssignee);
                        item.attr('data-original-assignee', originalAssignee);
                    })
                    .always(function() {
                        item.removeClass('saving');
                        updateChangesButton();
                    });
            }
        }).disableSelection();

        // Lista tehničara za dropdown (popunjava se iz uniqueAssignees)
        var assigneesList = <?php echo json_encode($uniqueAssignees); ?>;

        $(document).on('click', '.comment-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation();
            activeCommentBtn = $(this);
            var jobId = activeCommentBtn.data('id');
            var comment = activeCommentBtn.data('comment') || '';
            
            // ... (rest of existing logic to get data) ...
            var $card = activeCommentBtn.closest('.job-card');
            var $cell = $card.closest('td');
            var currentAssignee = $cell.data('assignee');
            
            var currentDate = $cell.data('date'); // YYYY-MM-DD
            var currentTime = $card.find('div:first strong').text().trim(); // HH:MM

            $('#commentModalJobId').text('#' + jobId);
            $('#commentJobIdInput').val(jobId);
            
            // Popunjavanje polja
            $('#commentText').val(comment);
            $('#editDate').val(currentDate);
            $('#editTime').val(currentTime);
            $('#editAdapter').val($card.data('adapter') || '');
            
            // Popunjavanje select-a tehničarima
            var $select = $('#editScheduledTo');
            $select.empty();
            $.each(assigneesList, function(index, value) {
                var isSelected = (value === currentAssignee) ? 'selected' : '';
                $select.append('<option value="' + value + '" ' + isSelected + '>' + value + '</option>');
            });

            // Učitavanje istorije izmena
            var $historyList = $('#historyList');
            $historyList.html('<p class="text-muted small mb-0">Učitavanje istorije...</p>');
            
            $.ajax({
                url: 'funkcije/raspored_lokalna_history.php',
                method: 'GET',
                data: { id: jobId },
                dataType: 'json'
            }).done(function(data) {
                $historyList.empty();
                if (data.length === 0) {
                    $historyList.html('<p class="text-muted small mb-0">Nema zabeleženih izmena.</p>');
                } else {
                    var html = '<ul class="list-unstyled mb-0">';
                    $.each(data, function(i, item) {
                        html += '<li class="mb-2 pb-2 border-bottom small">';
                        html += '<strong>' + item.change_date_formatted + '</strong> - ' + item.user + '<br>';
                        html += 'Polje: <strong>' + item.field_label + '</strong><br>';
                        html += '<span class="text-danger">Staro: ' + (item.old_value || '(prazno)') + '</span> &rarr; ';
                        html += '<span class="text-success">Novo: ' + (item.new_value || '(prazno)') + '</span>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    $historyList.html(html);
                }
            }).fail(function() {
                $historyList.html('<p class="text-danger small mb-0">Greška pri učitavanju istorije.</p>');
            });

            $('#commentModalAlert').addClass('d-none').text('');
            $('#commentModal').modal('show');
        });

        $('#commentModal').on('hidden.bs.modal', function() {
            activeCommentBtn = null;
            $('#commentJobIdInput').val('');
            $('#commentText').val('');
            $('#editAdapter').val('');
            $('#commentModalAlert').addClass('d-none').text('');
        });

        $('#commentModalSave').click(function() {
            if (!activeCommentBtn) {
                return;
            }
            var $btn = $(this);
            var jobId = $('#commentJobIdInput').val();
            var comment = $('#commentText').val().trim();
            var scheduledTo = $('#editScheduledTo').val();
            var date = $('#editDate').val();
            var time = $('#editTime').val();

            if (!date || !time) {
                $('#commentModalAlert').removeClass('d-none').text('Datum i vreme su obavezni.');
                return;
            }

            $btn.prop('disabled', true);
            $('#commentModalAlert').addClass('d-none').text('');

            $.ajax({
                type: 'POST',
                url: 'funkcije/raspored_lokalna_edit.php', // Promenjen endpoint
                dataType: 'json',
                data: {
                    id: jobId,
                    comment: comment,
                    scheduled_to: scheduledTo,
                    date: date,
                    time: time
                }
            }).done(function(response) {
                if (response.error) {
                    $('#commentModalAlert').removeClass('d-none').text(response.error);
                    $btn.prop('disabled', false);
                } else {
                    // Uspešno sačuvano - osveži stranicu
                    location.reload();
                }
            }).fail(function(xhr) {
                var errorText = 'Greška pri snimanju podataka.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorText = xhr.responseJSON.error;
                }
                $('#commentModalAlert').removeClass('d-none').text(errorText);
                $btn.prop('disabled', false);
            });
        });

        $(document).on('dblclick', '.job-card > div', function(e) {
            e.stopPropagation();
            var text = $(this).text().replace(/\s+/g, ' ').trim();
            if (!text) {
                return;
            }
            var $line = $(this);
            copyTextToClipboard(text)
                .then(function() {
                    $line.addClass('job-card-line-copied');
                    setTimeout(function() {
                        $line.removeClass('job-card-line-copied');
                    }, 900);
                })
                .catch(function() {
                    alert('Kopiranje nije uspelo.');
                });
        });

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

        // Finalize button handler (x button)
        $(document).on('click', '.finalize-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var btn = $(this);
            var id = btn.data('id');
            
            if (!confirm('Da li ste sigurni da želite da označite ovaj nalog kao Finalized i uklonite ga sa rasporeda?')) {
                return;
            }
            
            // Prevent double clicks
            btn.prop('disabled', true);
            
            $.ajax({
                type: 'POST',
                url: 'funkcije/raspored_lokalna_finalize.php',
                dataType: 'json',
                data: { id: id }
            }).done(function(response) {
                if (response.success) {
                    // Remove the card from UI with a fade out effect
                    btn.closest('.job-card').fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Greška: ' + (response.error || 'Nepoznata greška'));
                    btn.prop('disabled', false);
                }
            }).fail(function(xhr) {
                var errorMsg = 'Greška pri komunikaciji sa serverom.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                alert(errorMsg);
                btn.prop('disabled', false);
            });
        });
    });
    </script>
</body>

</html>
