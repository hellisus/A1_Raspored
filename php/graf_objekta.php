<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

$crud = new CRUD($_SESSION['godina']);

// Proveri da li je prosleđen ID objekta i raspon meseci
if (!isset($_GET['id']) || !isset($_GET['naziv']) || !isset($_GET['od_mesec']) || !isset($_GET['od_godina']) || !isset($_GET['do_mesec']) || !isset($_GET['do_godina'])) {
  header("location:uplate_po_mesecima.php");
  exit;
}

$objekat_id = (int)$_GET['id'];
$objekat_naziv = htmlspecialchars($_GET['naziv']);
$od_mesec = $_GET['od_mesec'];
$od_godina = (int)$_GET['od_godina'];
$do_mesec = $_GET['do_mesec'];
$do_godina = (int)$_GET['do_godina'];

// Učitaj podatke za objekat ili sve objekte
$crud->table = 'objekti';
if ($objekat_id == 0) {
  // Specijalni slučaj - svi objekti
  $objekat = ['id' => 0, 'naziv' => 'SVI OBJEKTI'];
} else {
  $objekat = $crud->select(['*'], ['id' => $objekat_id]);
  if (empty($objekat)) {
    header("location:uplate_po_mesecima.php");
    exit;
  }
  $objekat = $objekat[0];
}

// Generiši mesece u izabranom rasponu
$meseci = [];
$datum_od = new DateTime($od_godina . '-' . $od_mesec . '-01');
$datum_do = new DateTime($do_godina . '-' . $do_mesec . '-01');

$trenutni = clone $datum_od;
while ($trenutni <= $datum_do) {
  $mesec_broj = $trenutni->format('n');
  $godina = $trenutni->format('Y');
  
  $srpski_meseci = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'Maj', 6 => 'Jun', 7 => 'Jul', 8 => 'Avg',
    9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dec'
  ];
  
  $meseci[] = [
    'key' => $trenutni->format('Y-m'),
    'label' => $srpski_meseci[$mesec_broj] . ' ' . $godina
  ];
  $trenutni->modify('+1 month');
}

// Učitaj planirane uplate
if ($objekat_id == 0) {
  // Za sve objekte
  $planovi_otplate = $crud->query("
    SELECT po.*, 
           CASE 
             WHEN po.tip_jedinice = 'stan' THEN s.objekat_id
             WHEN po.tip_jedinice = 'lokal' THEN l.objekat_id
             WHEN po.tip_jedinice = 'garaza' THEN g.objekat_id
             WHEN po.tip_jedinice = 'parking' THEN p.objekat_id
           END as objekat_id
    FROM planovi_otplate po
    LEFT JOIN stanovi s ON po.tip_jedinice = 'stan' AND po.jedinica_id = s.id
    LEFT JOIN lokali l ON po.tip_jedinice = 'lokal' AND po.jedinica_id = l.id
    LEFT JOIN garaze g ON po.tip_jedinice = 'garaza' AND po.jedinica_id = g.id
    LEFT JOIN parking_mesta p ON po.tip_jedinice = 'parking' AND po.jedinica_id = p.id
    ORDER BY po.datum_rate ASC
  ")->fetchAll();
} else {
  // Za određeni objekat
  $planovi_otplate = $crud->query("
    SELECT po.*, 
           CASE 
             WHEN po.tip_jedinice = 'stan' THEN s.objekat_id
             WHEN po.tip_jedinice = 'lokal' THEN l.objekat_id
             WHEN po.tip_jedinice = 'garaza' THEN g.objekat_id
             WHEN po.tip_jedinice = 'parking' THEN p.objekat_id
           END as objekat_id
    FROM planovi_otplate po
    LEFT JOIN stanovi s ON po.tip_jedinice = 'stan' AND po.jedinica_id = s.id
    LEFT JOIN lokali l ON po.tip_jedinice = 'lokal' AND po.jedinica_id = l.id
    LEFT JOIN garaze g ON po.tip_jedinice = 'garaza' AND po.jedinica_id = g.id
    LEFT JOIN parking_mesta p ON po.tip_jedinice = 'parking' AND po.jedinica_id = p.id
    WHERE CASE 
             WHEN po.tip_jedinice = 'stan' THEN s.objekat_id
             WHEN po.tip_jedinice = 'lokal' THEN l.objekat_id
             WHEN po.tip_jedinice = 'garaza' THEN g.objekat_id
             WHEN po.tip_jedinice = 'parking' THEN p.objekat_id
           END = $objekat_id
    ORDER BY po.datum_rate ASC
  ")->fetchAll();
}

// Učitaj stvarne uplate
if ($objekat_id == 0) {
  // Za sve objekte
  $uplate = $crud->query("
    SELECT DATE_FORMAT(u.datum_uplate, '%Y-%m') AS mesec, SUM(u.iznos_uplate) AS ukupno
    FROM uplata u
    GROUP BY mesec
  ")->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
  // Za određeni objekat
  $uplate = $crud->query("
    SELECT DATE_FORMAT(u.datum_uplate, '%Y-%m') AS mesec, SUM(u.iznos_uplate) AS ukupno
    FROM uplata u
    LEFT JOIN stanovi s ON u.tip_jedinice = 'stan' AND u.id_jedinice = s.id
    LEFT JOIN lokali l ON u.tip_jedinice = 'lokal' AND u.id_jedinice = l.id
    LEFT JOIN garaze g ON u.tip_jedinice = 'garaza' AND u.id_jedinice = g.id
    LEFT JOIN parking_mesta p ON u.tip_jedinice = 'parking' AND u.id_jedinice = p.id
    WHERE COALESCE(s.objekat_id, l.objekat_id, g.objekat_id, p.objekat_id) = $objekat_id
    GROUP BY mesec
  ")->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Pripremi podatke za grafikon
$plan_data = [];
$stvarno_data = [];
$labels = [];

// Kumulativni podaci
$kumulativni_plan_data = [];
$kumulativni_stvarno_data = [];

$kumulativni_plan = 0;
$kumulativni_stvarno = 0;

foreach ($meseci as $mesec) {
  $labels[] = $mesec['label'];
  
  // Planirane uplate
  $plan_sum = 0;
  foreach ($planovi_otplate as $plan) {
    if (substr($plan['datum_rate'], 0, 7) === $mesec['key']) {
      $plan_sum += (float)$plan['suma'];
    }
  }
  $plan_data[] = $plan_sum;
  
  // Stvarne uplate
  $stvarno_sum = (float)($uplate[$mesec['key']] ?? 0);
  $stvarno_data[] = $stvarno_sum;
  
  // Kumulativni izračun
  $kumulativni_plan += $plan_sum;
  $kumulativni_stvarno += $stvarno_sum;
  
  $kumulativni_plan_data[] = $kumulativni_plan;
  $kumulativni_stvarno_data[] = $kumulativni_stvarno;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Grafikon objekta: <?= $objekat_naziv ?></title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/funkcije.js"></script>
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    .chart-container {
      position: relative;
      height: 400px;
      margin: 20px 0;
    }
    .back-button {
      margin-bottom: 20px;
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

        <div class="d-flex flex-column justify-content-center align-items-center">

          <h3 class="center sekcija">GRAFIKON OBJEKTA: <?= $objekat_naziv ?> <i class="fas fa-chart-line"></i></h3> <br>

          <div class="back-button">
            <a href="uplate_po_mesecima.php?od_mesec=<?= $od_mesec ?>&od_godina=<?= $od_godina ?>&do_mesec=<?= $do_mesec ?>&do_godina=<?= $do_godina ?>" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> Nazad na tabelu
            </a>
          </div>

          <div class="card w-100 mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-chart-line"></i> Mesečne uplate - <?= $od_mesec ?>/<?= $od_godina ?> do <?= $do_mesec ?>/<?= $do_godina ?></h5>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="uplataChart"></canvas>
              </div>
            </div>
          </div>

          <div class="card w-100">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-chart-area"></i> Kumulativne uplate - <?= $od_mesec ?>/<?= $od_godina ?> do <?= $do_mesec ?>/<?= $do_godina ?></h5>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="kumulativniChart"></canvas>
              </div>
            </div>
          </div>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

<script>
$(document).ready(function() {
  // Prvi grafikon - Mesečne uplate
  const ctx1 = document.getElementById('uplataChart').getContext('2d');
  
  const chart1 = new Chart(ctx1, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Planirane uplate',
        data: <?= json_encode($plan_data) ?>,
        borderColor: '#007bff',
        backgroundColor: 'rgba(0, 123, 255, 0.1)',
        borderWidth: 2,
        fill: false,
        tension: 0.1
      }, {
        label: 'Stvarne uplate',
        data: <?= json_encode($stvarno_data) ?>,
        borderColor: '#28a745',
        backgroundColor: 'rgba(40, 167, 69, 0.1)',
        borderWidth: 2,
        fill: false,
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return new Intl.NumberFormat('sr-RS', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
              }).format(value) + ' €';
            }
          }
        }
      },
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ' + 
                     new Intl.NumberFormat('sr-RS', {
                       minimumFractionDigits: 0,
                       maximumFractionDigits: 0
                     }).format(context.parsed.y) + ' €';
            }
          }
        }
      }
    }
  });

  // Drugi grafikon - Kumulativne uplate
  const ctx2 = document.getElementById('kumulativniChart').getContext('2d');
  
  const chart2 = new Chart(ctx2, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Kumulativno planirane uplate',
        data: <?= json_encode($kumulativni_plan_data) ?>,
        borderColor: '#007bff',
        backgroundColor: 'rgba(0, 123, 255, 0.2)',
        borderWidth: 3,
        fill: true,
        tension: 0.1
      }, {
        label: 'Kumulativno stvarne uplate',
        data: <?= json_encode($kumulativni_stvarno_data) ?>,
        borderColor: '#28a745',
        backgroundColor: 'rgba(40, 167, 69, 0.2)',
        borderWidth: 3,
        fill: true,
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return new Intl.NumberFormat('sr-RS', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
              }).format(value) + ' €';
            }
          }
        }
      },
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ' + 
                     new Intl.NumberFormat('sr-RS', {
                       minimumFractionDigits: 0,
                       maximumFractionDigits: 0
                     }).format(context.parsed.y) + ' €';
            }
          }
        }
      }
    }
  });
});
</script>

</html>
