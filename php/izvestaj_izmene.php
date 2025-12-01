<?php
require 'config.php';

if (!isset($_SESSION['Ime'])) {
    header("location:../index.php");
}

$crud = new CRUD('srnalozi_a1_raspored');
$crud->table = 'istorija_izmena'; // Explicitly setting table context, though query uses it directly

// Fetch distinct users for filter
try {
    $usersStmt = $crud->prepare("SELECT DISTINCT user FROM istorija_izmena ORDER BY user ASC");
    $usersStmt->execute();
    $users = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $users = [];
}

// Excel Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_GET['od']) && isset($_GET['do'])) {
    $od = $_GET['od'];
    $do = $_GET['do'];
    $user_filter = isset($_GET['user']) ? $_GET['user'] : '';
    
    $od_datetime = $od . ' 00:00:00';
    $do_datetime = $do . ' 23:59:59';

    try {
        $sql = "SELECT h.*, l.Woid, l.`Customer Name` 
                FROM istorija_izmena h 
                LEFT JOIN lokalna_tabela l ON h.job_id = l.ID 
                WHERE h.change_date >= ? AND h.change_date <= ?";
        
        $params = [$od_datetime, $do_datetime];
        
        if (!empty($user_filter)) {
            $sql .= " AND h.user = ?";
            $params[] = $user_filter;
        }
        
        $sql .= " ORDER BY h.change_date DESC";

        $stmt = $crud->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filename construction
        $date_od_fmt = date('dmY', strtotime($od));
        $date_do_fmt = date('dmY', strtotime($do));
        $filename = "Izvestaj_izmene_od_{$date_od_fmt}_do_{$date_do_fmt}.xlsx";

        require_once 'SimpleXLSXGen.php';

        $columns = ['Datum i vreme', 'Korisnik', 'Woid', 'Kupac', 'Polje', 'Stara vrednost', 'Nova vrednost', 'ID Naloga'];
        
        $rows = [];
        $rows[] = $columns; // Header row

        foreach ($data as $row) {
            $dateVal = new DateTime($row['change_date']);
            
            // Translate field names
            $fieldLabel = $row['field_name'];
            switch ($row['field_name']) {
                case 'Comment': $fieldLabel = 'Komentar'; break;
                case 'Assignees': $fieldLabel = 'Tim'; break;
                case 'Scheduled start': $fieldLabel = 'Termin'; break;
            }

            $rows[] = [
                $dateVal->format('d.m.Y H:i'),
                $row['user'],
                $row['Woid'] ?? '',
                $row['Customer Name'] ?? '',
                $fieldLabel,
                $row['old_value'],
                $row['new_value'],
                $row['job_id']
            ];
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

    <title>A1 raspored - Izveštaj izmena</title>

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
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Pregled istorije izmena</h5>
                    </div>
                    <div class="card-body">
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
                                    <label for="user">Korisnik:</label>
                                    <select class="form-control select2" id="user" name="user">
                                        <option value="">Svi korisnici</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?php echo htmlspecialchars($u); ?>" <?php echo (isset($_GET['user']) && $_GET['user'] == $u) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($u); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Prikaži</button>
                                    <button type="submit" name="export" value="excel" class="btn btn-success"><i class="fas fa-file-excel"></i> Export Excel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (isset($_GET['od']) && isset($_GET['do'])): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Rezultati</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $od = $_GET['od'];
                            $do = $_GET['do'];
                            $user_filter = isset($_GET['user']) ? $_GET['user'] : '';

                            $od_datetime = $od . ' 00:00:00';
                            $do_datetime = $do . ' 23:59:59';

                            try {
                                // Join with lokalna_tabela to get context like Woid and Customer Name if available
                                $sql = "SELECT h.*, l.Woid, l.`Customer Name` 
                                        FROM istorija_izmena h 
                                        LEFT JOIN lokalna_tabela l ON h.job_id = l.ID 
                                        WHERE h.change_date >= ? AND h.change_date <= ?";
                                
                                $params = [$od_datetime, $do_datetime];
                                
                                if (!empty($user_filter)) {
                                    $sql .= " AND h.user = ?";
                                    $params[] = $user_filter;
                                }
                                
                                $sql .= " ORDER BY h.change_date DESC";

                                $stmt = $crud->prepare($sql);
                                $stmt->execute($params);
                                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $totalRows = count($data);
                                ?>

                                <div class="mb-3 text-right">
                                    <span>Ukupno izmena: <strong><?php echo $totalRows; ?></strong></span>
                                </div>

                                <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped table-hover table-sm text-nowrap">
                                        <thead class="thead-light sticky-top">
                                            <tr>
                                                <th>Datum i vreme</th>
                                                <th>Korisnik</th>
                                                <th>Nalog (Woid)</th>
                                                <th>Kupac</th>
                                                <th>Polje</th>
                                                <th>Stara vrednost</th>
                                                <th>Nova vrednost</th>
                                                <th>Detalji</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($data)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">Nema zabeleženih izmena za izabrani period.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($data as $row): ?>
                                                    <tr>
                                                        <td>
                                                            <?php 
                                                            $dateVal = new DateTime($row['change_date']);
                                                            echo $dateVal->format('d.m.Y H:i');
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['user']); ?></td>
                                                        <td>
                                                            <span title="Job ID: <?php echo $row['job_id']; ?>">
                                                                <?php echo htmlspecialchars($row['Woid'] ?? 'N/A'); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($row['Customer Name'] ?? ''); ?>">
                                                            <?php echo htmlspecialchars($row['Customer Name'] ?? ''); ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $label = $row['field_name'];
                                                            switch ($row['field_name']) {
                                                                case 'Comment': $label = 'Komentar'; break;
                                                                case 'Assignees': $label = 'Tim'; break;
                                                                case 'Scheduled start': $label = 'Termin'; break;
                                                            }
                                                            echo htmlspecialchars($label); 
                                                            ?>
                                                        </td>
                                                        <td class="text-danger text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($row['old_value']); ?>">
                                                            <?php echo htmlspecialchars($row['old_value']); ?>
                                                        </td>
                                                        <td class="text-success text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($row['new_value']); ?>">
                                                            <?php echo htmlspecialchars($row['new_value']); ?>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">ID: <?php echo $row['job_id']; ?></small>
                                                        </td>
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
