<?php
require  'config.php';

if (!isset($_SESSION['Ime'])) {
    header("location:../index.php");
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

    <title>A1 raspored - Import baze</title>

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
                <div class="card">
                    <div class="card-header">
                        Import CSV / XLSX Fajla
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_POST['import_csv'])) {
                            $stat_inserted = 0;
                            $stat_updated = 0;

                            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                                $fileTmpPath = $_FILES['csv_file']['tmp_name'];
                                $fileName = $_FILES['csv_file']['name'];
                                $fileNameCmps = explode(".", $fileName);
                                $fileExtension = strtolower(end($fileNameCmps));

                                if ($fileExtension === 'csv' || $fileExtension === 'xlsx') {
                                    try {
                                        // Instanciranje CRUD-a, pretpostavljamo da je baza 'A1_Raspored'
                                        $crud = new CRUD('A1_Raspored');
                                        $crud->table = 'glavna_tabela';

                                        $headers = null;
                                        $rows = [];
                                        $handle = null;
                                        $is_csv = ($fileExtension === 'csv');

                                        if ($is_csv) {
                                            if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
                                                // Provera i preskakanje BOM-a ako postoji
                                                $bom = fread($handle, 3);
                                                if ($bom !== "\xEF\xBB\xBF") {
                                                    rewind($handle);
                                                }
                                                // Čitanje zaglavlja
                                                $headers = fgetcsv($handle, 0, ";");
                                            }
                                        } else {
                                            // XLSX logika
                                            if (file_exists('SimpleXLSX.php')) {
                                                require_once 'SimpleXLSX.php';
                                            } elseif (file_exists('lib/SimpleXLSX.php')) {
                                                require_once 'lib/SimpleXLSX.php';
                                            } else {
                                                throw new Exception("Biblioteka SimpleXLSX.php nije pronađena. Molimo preuzmite je sa https://github.com/shuchkin/simplexlsx i postavite u php folder.");
                                            }

                                            if ($xlsx = Shuchkin\SimpleXLSX::parse($fileTmpPath)) {
                                                $rows = $xlsx->rows();
                                                if (!empty($rows)) {
                                                    $headers = array_shift($rows); // Prvi red je zaglavlje
                                                }
                                            } else {
                                                throw new Exception(Shuchkin\SimpleXLSX::parseError());
                                            }
                                        }

                                        if ($headers) {
                                            // Mapiranje prazne kolone (index 23 u primeru)
                                            // Proveravamo da li postoji prazan header i dodeljujemo mu ime iz baze
                                            foreach ($headers as $k => $v) {
                                                if (trim($v) === '' && $k == 23) {
                                                    $headers[$k] = 'Empty_Column_1';
                                                }
                                            }

                                            $dateColumns = ['Accept date', 'Create date', 'Job Creation Date', 'Scheduled start'];
                                            $numericColumns = ['Proposal (New) Amount', 'Proposal (Rejected) Amount', 'Proposal (Accepted) Amount', 'Adapter ID'];
                                            $numericColumnSet = array_flip($numericColumns);
                                            $dateColumnSet = array_flip($dateColumns);
                                            
                                            // Dobijamo listu kolona iz baze dinamički
                                            $stmtCols = $crud->prepare("SHOW COLUMNS FROM `glavna_tabela`");
                                            $stmtCols->execute();
                                            $dbColumns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
                                            $dbColumnSet = array_flip($dbColumns);
                                            $emptyColumnFallback = in_array('Empty_Column_1', $dbColumns, true) ? 'Empty_Column_1' : null;
                                            
                                            // Pripremimo statement za proveru postojanja ID-ja
                                            $stmtExists = $crud->prepare("SELECT 1 FROM `glavna_tabela` WHERE `ID` = ? LIMIT 1");
                                            
                                            $rowIndex = 0;
                                            while (true) {
                                                if ($is_csv) {
                                                    $row = fgetcsv($handle, 0, ";");
                                                    if ($row === FALSE) break;
                                                } else {
                                                    if (!isset($rows[$rowIndex])) break;
                                                    $row = $rows[$rowIndex];
                                                    $rowIndex++;
                                                }

                                                // XLSX može vratiti redove sa manje kolona, dopunimo ih null-ovima
                                                if (count($row) < count($headers)) {
                                                    $row = array_pad($row, count($headers), null);
                                                }
                                                // Ako ima više kolona nego u header-u, odsecamo višak
                                                if (count($row) > count($headers)) {
                                                    $row = array_slice($row, 0, count($headers));
                                                }

                                                // Skip prazne redove
                                                if (empty(array_filter($row, function($v) { return $v !== null && $v !== ''; }))) {
                                                    continue;
                                                }

                                                $data = array_combine($headers, $row);
                                                $dbData = [];

                                                foreach ($data as $key => $value) {
                                                    $key = trim($key);
                                                    if ($key === '' && $emptyColumnFallback !== null) {
                                                        $key = $emptyColumnFallback;
                                                    }
                                                    
                                                    if (!isset($dbColumnSet[$key])) {
                                                        continue;
                                                    }
                                                    
                                                    // Convert object to string if necessary (SimpleXLSX might return mixed types)
                                                    if (is_object($value)) {
                                                        // Fallback, shouldn't happen with standard SimpleXLSX::rows() which returns scalar
                                                        $value = (string)$value;
                                                    }
                                                    
                                                    $value = trim((string)$value);
                                                    
                                                    // Konverzija 'null' stringa u pravi NULL
                                                    if ($value === '' || strtolower($value) === 'null') {
                                                        $value = null;
                                                    }
                                                    
                                                    // Konverzija datuma (d.m.Y H:i -> Y-m-d H:i:s)
                                                    if ($value !== null && isset($dateColumnSet[$key])) {
                                                        $dateTime = DateTime::createFromFormat('d.m.Y H:i', $value);
                                                        if ($dateTime) {
                                                            $value = $dateTime->format('Y-m-d H:i:s');
                                                        } else {
                                                            // Pokušaj sa drugim formatima ako je XLSX vratio nešto drugo
                                                            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
                                                            if (!$dateTime) {
                                                                $dateTime = date_create($value); 
                                                                if ($dateTime) $value = $dateTime->format('Y-m-d H:i:s');
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Saniranje brojeva (zarez u tačku i naučna notacija)
                                                    if ($value !== null && isset($numericColumnSet[$key])) {
                                                        $value = str_replace(',', '.', $value);
                                                        
                                                        // Provera naučne notacije (npr. 3.81E+11)
                                                        if (stripos($value, 'e') !== false) {
                                                            $floatVal = floatval($value);
                                                            if ($key === 'Adapter ID') {
                                                                // BIGINT - bez decimala, pun zapis
                                                                $value = number_format($floatVal, 0, '', '');
                                                            } else {
                                                                // DECIMAL 
                                                                $value = (string)$floatVal;
                                                            }
                                                        }
                                                        // Ako je prazan string za numeričku kolonu, pretvori u null
                                                        if ($value === '') {
                                                            $value = null;
                                                        }
                                                    }

                                                    $dbData[$key] = $value;
                                                }

                                                // Provera da li ID postoji i nije prazan
                                                if (!empty($dbData['ID'])) {
                                                    $id = $dbData['ID'];
                                                    
                                                    $stmtExists->execute([$id]);
                                                    $existing = $stmtExists->fetchColumn();
                                                    $stmtExists->closeCursor();

                                                    if (!empty($existing)) {
                                                        // UPDATE sa ? placeholderima
                                                        $updateData = $dbData;
                                                        unset($updateData['ID']); // Ne ažuriramo ID
                                                        
                                                        $updateSet = [];
                                                        $values = [];
                                                        
                                                        foreach ($updateData as $col => $val) {
                                                            $updateSet[] = "`$col` = ?";
                                                            $values[] = $val;
                                                        }
                                                        
                                                        // Ako nema ničega za ažuriranje (samo ID), preskačemo ovaj red
                                                        if (empty($updateSet)) {
                                                            continue;
                                                        }
                                                        
                                                        // Dodajemo ID na kraj za WHERE
                                                        $values[] = $id;
                                                        
                                                        $sql = "UPDATE `glavna_tabela` SET " . implode(', ', $updateSet) . " WHERE `ID` = ?";
                                                        
                                                        try {
                                                            $stmt = $crud->prepare($sql);
                                                            $stmt->execute($values);
                                                            $stat_updated++;
                                                        } catch (PDOException $e) {
                                                            echo "<div class='alert alert-danger'>SQL Greška (Update) na ID $id: " . $e->getMessage() . "<br>";
                                                            echo "SQL: $sql <br>";
                                                            echo "Params: " . json_encode($values) . "</div>";
                                                            die();
                                                        }
                                                        
                                                    } else {
                                                        // INSERT sa ? placeholderima
                                                        $cols = [];
                                                        $placeholders = [];
                                                        $values = [];
                                                        
                                                        foreach ($dbData as $col => $val) {
                                                            $cols[] = "`$col`";
                                                            $placeholders[] = "?";
                                                            $values[] = $val;
                                                        }
                                                        
                                                        $sql = "INSERT INTO `glavna_tabela` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                                                        
                                                        try {
                                                            $stmt = $crud->prepare($sql);
                                                            $stmt->execute($values);
                                                            $stat_inserted++;
                                                        } catch (PDOException $e) {
                                                            echo "<div class='alert alert-danger'>SQL Greška (Insert) na ID $id: " . $e->getMessage() . "<br>";
                                                            echo "SQL: $sql <br>";
                                                            echo "Params: " . json_encode($values) . "</div>";
                                                            die();
                                                        }
                                                    }
                                                }
                                            }
                                            if ($is_csv && $handle) {
                                                fclose($handle);
                                            }
                                            echo "<div class='alert alert-success'>
                                                    <strong>Uspešan import!</strong><br>
                                                    Uvezeno novih naloga: $stat_inserted<br>
                                                    Ažurirano postojećih naloga: $stat_updated
                                                  </div>";
                                        } else {
                                            echo "<div class='alert alert-warning'>Fajl je prazan ili neispravan format zaglavlja.</div>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<div class='alert alert-danger'>Greška prilikom importa: " . $e->getMessage() . "</div>";
                                    }
                                } else {
                                    echo "<div class='alert alert-warning'>Molimo otpremite fajl sa .csv ili .xlsx ekstenzijom.</div>";
                                }
                            } else {
                                echo "<div class='alert alert-danger'>Došlo je do greške prilikom otpremanja fajla.</div>";
                            }
                        }
                        ?>

                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="csv_file">Izaberite CSV ili XLSX fajl</label>
                                <input type="file" name="csv_file" id="csv_file" class="form-control-file" required accept=".csv, .xlsx">
                            </div>
                            <button type="submit" name="import_csv" class="btn btn-primary">
                                <i class="fas fa-file-import"></i> Uvezi podatke
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>



</body>

</html>
