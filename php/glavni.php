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

    <title>A1 raspored - Glavna stranica</title>

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
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-table"></i> Pregled Glavne Tabele</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Inicijalizacija CRUD objekta
                        $crud = new CRUD('srnalozi_a1_raspored');
                        $crud->table = 'glavna_tabela';

                        // Definisane kolone za prikaz
                        $columns = [
                            'Scheduled to',
                            'Woid',
                            'Adapter ID',
                            'Customer Name',
                            'Contact Phone On Location',
                            'City',
                            'Address',
                            'House Number',
                            'Scheduled start'
                        ];

                        // Procesiranje filtera i sortiranja
                        $where = [];
                        $params = [];
                        $orderBy = "ID DESC"; // Default sort

                        // Sortiranje
                        if (isset($_GET['sort']) && in_array($_GET['sort'], $columns)) {
                            $direction = isset($_GET['dir']) && strtoupper($_GET['dir']) === 'ASC' ? 'ASC' : 'DESC';
                            $orderBy = "`" . $_GET['sort'] . "` " . $direction;
                        }

                        // Filtriranje
                        foreach ($columns as $col) {
                            // Zamena razmaka donjom crtom u imenu input polja, jer PHP automatski konvertuje razmake u tačke ili donje crte u $_GET ključevima
                            $inputName = 'filter_' . str_replace(' ', '_', $col);
                            
                            if (isset($_GET[$inputName]) && $_GET[$inputName] !== '') {
                                $where[] = "`$col` LIKE ?";
                                $params[] = "%" . $_GET[$inputName] . "%";
                            }
                        }

                        $whereClause = "";
                        if (!empty($where)) {
                            $whereClause = "WHERE " . implode(" AND ", $where);
                        }

                        // Paginacija
                        $limit = 50;
                        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        $offset = ($page - 1) * $limit;

                        // Brojanje ukupnih zapisa za paginaciju
                        $countSql = "SELECT COUNT(*) FROM `glavna_tabela` $whereClause";
                        $stmtCount = $crud->prepare($countSql);
                        $stmtCount->execute($params);
                        $totalRows = $stmtCount->fetchColumn();
                        $totalPages = ceil($totalRows / $limit);

                        // Glavni upit
                        $sql = "SELECT * FROM `glavna_tabela` $whereClause ORDER BY $orderBy LIMIT $limit OFFSET $offset";
                        $stmt = $crud->prepare($sql);
                        $stmt->execute($params);
                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <!-- Forma za reset filtera -->
                        <div class="mb-3 text-right">
                            <a href="glavni.php" class="btn btn-secondary btn-sm"><i class="fas fa-sync-alt"></i> Resetuj filtere</a>
                            <span class="ml-2">Ukupno zapisa: <strong><?php echo $totalRows; ?></strong></span>
                        </div>

                        <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                            <form action="" method="GET" id="filterForm">
                                <table class="table table-bordered table-striped table-hover table-sm text-nowrap">
                                    <thead class="thead-dark sticky-top">
                                        <tr>
                                            <?php foreach ($columns as $col): 
                                                // Logika za sortiranje linkova
                                                $newDir = 'ASC';
                                                $icon = 'fa-sort';
                                                if (isset($_GET['sort']) && $_GET['sort'] === $col) {
                                                    if (!isset($_GET['dir']) || $_GET['dir'] === 'DESC') {
                                                        $icon = 'fa-sort-down';
                                                    } else {
                                                        $icon = 'fa-sort-up';
                                                        $newDir = 'DESC';
                                                    }
                                                }
                                                
                                                // Očuvanje ostalih GET parametara u sort linkovima
                                                $queryParams = $_GET;
                                                $queryParams['sort'] = $col;
                                                $queryParams['dir'] = $newDir;
                                                unset($queryParams['page']); // Reset page na sort
                                                $sortUrl = '?' . http_build_query($queryParams);
                                            ?>
                                                <th style="min-width: 150px;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <a href="<?php echo $sortUrl; ?>" class="text-white text-decoration-none">
                                                            <?php echo $col; ?> <i class="fas <?php echo $icon; ?>"></i>
                                                        </a>
                                                    </div>
                                                    <div class="mt-1">
                                                        <input type="text" 
                                                               name="filter_<?php echo str_replace(' ', '_', $col); ?>" 
                                                               class="form-control form-control-sm" 
                                                               value="<?php echo isset($_GET['filter_' . str_replace(' ', '_', $col)]) ? htmlspecialchars($_GET['filter_' . str_replace(' ', '_', $col)]) : ''; ?>" 
                                                               onkeydown="if(event.key === 'Enter'){ this.form.submit(); }">
                                                    </div>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data)): ?>
                                            <tr>
                                                <td colspan="<?php echo count($columns); ?>" class="text-center">Nema podataka za prikaz.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data as $row): ?>
                                                <tr>
                                                    <?php foreach ($columns as $col): ?>
                                                        <td>
                                                            <?php 
                                                            if ($col === 'Scheduled start' && !empty($row[$col])) {
                                                                $dateVal = new DateTime($row[$col]);
                                                                echo $dateVal->format('d.m.Y H:i'); // Prikaz datuma i vremena
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
                                <!-- Hidden inputs za čuvanje sorta prilikom submitovanja forme pritiskom na Enter u filteru -->
                                <?php if(isset($_GET['sort'])): ?>
                                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
                                <?php endif; ?>
                                <?php if(isset($_GET['dir'])): ?>
                                    <input type="hidden" name="dir" value="<?php echo htmlspecialchars($_GET['dir']); ?>">
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Paginacija -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    // Očuvanje filtera u paginaciji
                                    $paginationParams = $_GET;
                                    
                                    // Prva strana
                                    $paginationParams['page'] = 1;
                                    echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($paginationParams) . '">Prva</a></li>';

                                    // Prethodna
                                    $paginationParams['page'] = max(1, $page - 1);
                                    echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($paginationParams) . '">&laquo;</a></li>';

                                    // Stranice (prikazujemo npr. trenutnu, 2 pre i 2 posle)
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);

                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        $paginationParams['page'] = $i;
                                        $active = $i == $page ? 'active' : '';
                                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . http_build_query($paginationParams) . '">' . $i . '</a></li>';
                                    }

                                    // Sledeća
                                    $paginationParams['page'] = min($totalPages, $page + 1);
                                    echo '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($paginationParams) . '">&raquo;</a></li>';

                                    // Poslednja
                                    $paginationParams['page'] = $totalPages;
                                    echo '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="?' . http_build_query($paginationParams) . '">Poslednja</a></li>';
                                    ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


</body>

</html>