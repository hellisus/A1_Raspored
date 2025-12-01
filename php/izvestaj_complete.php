<?php
require  'config.php';

if (!isset($_SESSION['Ime'])) {
    header("location:../index.php");
}

// Excel Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_GET['od']) && isset($_GET['do']) && isset($_GET['tip_naloga'])) {
    $od = $_GET['od'];
    $do = $_GET['do'];
    $tip_naloga = $_GET['tip_naloga'];
    
    $od_datetime = $od . ' 00:00:00';
    $do_datetime = $do . ' 23:59:59';

    $crud = new CRUD('srnalozi_a1_raspored');
    $crud->table = 'glavna_tabela';

    try {
        $sql = "SELECT * FROM `glavna_tabela` WHERE `Scheduled start` >= ? AND `Scheduled start` <= ? AND `Current state` = ?";
        $stmt = $crud->prepare($sql);
        $stmt->execute([$od_datetime, $do_datetime, $tip_naloga]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filename construction
        $clean_tip = preg_replace('/[^a-zA-Z0-9]/', '_', $tip_naloga);
        $date_od_fmt = date('dmY', strtotime($od));
        $date_do_fmt = date('dmY', strtotime($do));
        $filename = "{$clean_tip}_od_{$date_od_fmt}_do_{$date_do_fmt}.xlsx";

        require_once 'SimpleXLSXGen.php';

        $columns = [
            'Scheduled to', 'Woid', 'Adapter ID', 'Customer Name', 
            'Contact Phone On Location', 'City', 'Address', 
            'House Number', 'Scheduled start', 'Current state'
        ];

        $rows = [];
        $rows[] = $columns; // Header row

        foreach ($data as $row) {
            $r = [];
            foreach ($columns as $col) {
                if ($col === 'Scheduled start' && !empty($row[$col])) {
                    try {
                        $dateVal = new DateTime($row[$col]);
                        $r[] = $dateVal->format('d.m.Y H:i');
                    } catch (Exception $e) {
                        $r[] = $row[$col] ?? '';
                    }
                } else {
                    $r[] = $row[$col] ?? '';
                }
            }
            $rows[] = $r;
        }

        SimpleXLSXGen::fromArray($rows)->downloadAs($filename);
        exit;

    } catch (PDOException $e) {
        die("Greška pri generisanju fajla: " . $e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

    <title>A1 raspored - Izveštaj</title>

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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/funkcije.js"></script>
  


</head>

<body>
    <div class="wrapper">
        <!-- Sidebar  -->
        <?php require_once 'sidebar.php' ?>

        <!-- Page Content  -->
        <div id="content">
            <!-- Topbar  -->
            <?php require_once 'topbar.php' ?>
            <div class="containter">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Generisanje izveštaja</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $crud = new CRUD('srnalozi_a1_raspored');
                        $crud->table = 'glavna_tabela';
                        
                        // Fetch distinct Current state values
                        try {
                            $statesStmt = $crud->prepare("SELECT DISTINCT `Current state` FROM `glavna_tabela` ORDER BY `Current state` ASC");
                            $statesStmt->execute();
                            $states = $statesStmt->fetchAll(PDO::FETCH_COLUMN);
                        } catch (PDOException $e) {
                            $states = [];
                            // Handle error silently or display alert
                        }
                        ?>
                        <form method="GET" action="">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="od">Od datuma:</label>
                                    <input type="date" class="form-control" id="od" name="od" value="<?php echo isset($_GET['od']) ? htmlspecialchars($_GET['od']) : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="do">Do datuma:</label>
                                    <input type="date" class="form-control" id="do" name="do" value="<?php echo isset($_GET['do']) ? htmlspecialchars($_GET['do']) : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="tip_naloga">Tip naloga (Current state):</label>
                                    <select class="form-control select2" id="tip_naloga" name="tip_naloga" required>
                                        <option value="">Izaberite...</option>
                                        <?php foreach ($states as $state): ?>
                                            <?php if ($state): // Skip empty if any ?>
                                                <option value="<?php echo htmlspecialchars($state); ?>" <?php echo (isset($_GET['tip_naloga']) && $_GET['tip_naloga'] == $state) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($state); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success mr-2"><i class="fas fa-search"></i> Prikaži</button>
                                    <button type="submit" name="export" value="excel" class="btn btn-info"><i class="fas fa-file-excel"></i> Export Excel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (isset($_GET['od']) && isset($_GET['do']) && isset($_GET['tip_naloga'])): ?>
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-table"></i> Rezultati izveštaja</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $od = $_GET['od'];
                            $do = $_GET['do'];
                            $tip_naloga = $_GET['tip_naloga'];

                            // Add time to dates for full day range coverage
                            $od_datetime = $od . ' 00:00:00';
                            $do_datetime = $do . ' 23:59:59';

                            // Columns to display (same as glavni.php)
                            $columns = [
                                'Scheduled to',
                                'Woid',
                                'Adapter ID',
                                'Customer Name',
                                'Contact Phone On Location',
                                'City',
                                'Address',
                                'House Number',
                                'Scheduled start',
                                'Current state' // Added to see the filtered state
                            ];

                            try {
                                $sql = "SELECT * FROM `glavna_tabela` WHERE `Scheduled start` >= ? AND `Scheduled start` <= ? AND `Current state` = ?";
                                $stmt = $crud->prepare($sql);
                                $stmt->execute([$od_datetime, $do_datetime, $tip_naloga]);
                                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $totalRows = count($data);
                                ?>

                                <div class="mb-3 text-right">
                                    <span>Prikazano zapisa: <strong><?php echo $totalRows; ?></strong></span>
                                </div>

                                <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped table-hover table-sm text-nowrap">
                                        <thead class="thead-dark sticky-top">
                                            <tr>
                                                <?php foreach ($columns as $col): ?>
                                                    <th><?php echo $col; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($data)): ?>
                                                <tr>
                                                    <td colspan="<?php echo count($columns); ?>" class="text-center">Nema podataka za izabrane kriterijume.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($data as $row): ?>
                                                    <tr>
                                                        <?php foreach ($columns as $col): ?>
                                                            <td>
                                                                <?php 
                                                                if ($col === 'Scheduled start' && !empty($row[$col])) {
                                                                    try {
                                                                        $dateVal = new DateTime($row[$col]);
                                                                        echo $dateVal->format('d.m.Y H:i');
                                                                    } catch (Exception $e) {
                                                                        echo htmlspecialchars($row[$col] ?? '');
                                                                    }
                                                                } else {
                                                                    echo htmlspecialchars($row[$col] ?? ''); 
                                                                }
                                                                ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php
                            } catch (PDOException $e) {
                                echo '<div class="alert alert-danger">Greška prilikom učitavanja podataka: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        });
    </script>
</body>

</html>