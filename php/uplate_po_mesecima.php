<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

$crud = new CRUD($_SESSION['godina']);

// 1. Učitaj sve objekte
$objekti = $crud->query("SELECT * FROM objekti ORDER BY naziv ASC")->fetchAll();

// 2. Generiši opcije za mesece i godine
$mesec_opcije = [
  ['value' => '01', 'label' => 'Januar'],
  ['value' => '02', 'label' => 'Februar'],
  ['value' => '03', 'label' => 'Mart'],
  ['value' => '04', 'label' => 'April'],
  ['value' => '05', 'label' => 'Maj'],
  ['value' => '06', 'label' => 'Jun'],
  ['value' => '07', 'label' => 'Jul'],
  ['value' => '08', 'label' => 'Avgust'],
  ['value' => '09', 'label' => 'Septembar'],
  ['value' => '10', 'label' => 'Oktobar'],
  ['value' => '11', 'label' => 'Novembar'],
  ['value' => '12', 'label' => 'Decembar']
];

$godina_opcije = [];
$trenutna_godina = (int)date('Y');
for ($i = $trenutna_godina - 5; $i <= $trenutna_godina + 5; $i++) {
  $godina_opcije[] = [
    'value' => $i,
    'label' => $i
  ];
}

// 3. Proveri da li je forma poslata ili da li su prosleđeni GET parametri
$prikazi_tabelu = false;
$meseci = [];
$podaci = [];

// Proveri da li je forma poslata (POST ima prioritet nad GET)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['od_mesec']) && isset($_POST['od_godina']) && isset($_POST['do_mesec']) && isset($_POST['do_godina'])) {
  $od_mesec = $_POST['od_mesec'];
  $od_godina = $_POST['od_godina'];
  $do_mesec = $_POST['do_mesec'];
  $do_godina = $_POST['do_godina'];
  $iz_get = false;
} elseif (isset($_GET['od_mesec']) && isset($_GET['od_godina']) && isset($_GET['do_mesec']) && isset($_GET['do_godina'])) {
  // GET parametri (povratak sa grafikona) - samo ako nema POST
  $od_mesec = $_GET['od_mesec'];
  $od_godina = $_GET['od_godina'];
  $do_mesec = $_GET['do_mesec'];
  $do_godina = $_GET['do_godina'];
  $iz_get = true;
} else {
  $iz_get = false;
}

if (isset($od_mesec) && isset($od_godina) && isset($do_mesec) && isset($do_godina)) {
  
  // Generiši mesece u rasponu
  $datum_od = new DateTime($od_godina . '-' . $od_mesec . '-01');
  $datum_do = new DateTime($do_godina . '-' . $do_mesec . '-01');
  
  $meseci = [];
  $trenutni = clone $datum_od;
  while ($trenutni <= $datum_do) {
    $mesec_broj = $trenutni->format('n'); // 1-12
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
  
  // Za svaki objekat računaj planirane i stvarne uplate
  $podaci = [];
  foreach ($objekti as $objekat) {
    $objekat_id = $objekat['id'];
    $plan = izracunajPlaniraneUplate($objekat_id, $meseci, $objekat, $crud);
    $stvarno = izracunajStvarneUplate($objekat_id, $meseci, $crud);
    
    $podaci[$objekat_id] = [
      'naziv' => $objekat['naziv'],
      'plan' => $plan,
      'stvarno' => $stvarno
    ];
  }
  
  $prikazi_tabelu = true;
}

function izracunajPlaniraneUplate($objekat_id, $meseci, $objekat, $crud) {
  $plan = array_fill_keys(array_column($meseci, 'key'), 0);
  
  // Učitaj sve planove otplate za objekat
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
  
  foreach ($planovi_otplate as $plan_otplate) {
    $mesec = substr($plan_otplate['datum_rate'], 0, 7); // YYYY-MM
    if (isset($plan[$mesec])) {
      $plan[$mesec] += (float)$plan_otplate['suma'];
    }
  }
  
  return $plan;
}

function izracunajStvarneUplate($objekat_id, $meseci, $crud) {
  // SQL join: uplata -> jedinice -> objekti
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
  
  return $uplate;
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Uplate po mesecima</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/funkcije.js"></script>
  
   <script>
     $(document).ready(function() {
       // Validacija da "do" datum bude nakon "od" datuma
       $('#od_mesec, #od_godina, #do_mesec, #do_godina').on('change', function() {
         var odMesec = $('#od_mesec').val();
         var odGodina = $('#od_godina').val();
         var doMesec = $('#do_mesec').val();
         var doGodina = $('#do_godina').val();
         
         if (odMesec && odGodina && doMesec && doGodina) {
           var odDatum = new Date(odGodina, odMesec - 1, 1);
           var doDatum = new Date(doGodina, doMesec - 1, 1);
           
           if (odDatum > doDatum) {
             alert('Datum "Do" mora biti nakon datuma "Od"!');
             $(this).val('');
           }
         }
       });
     });
   </script>

  <style>
    .table-uplate { 
      font-size: 0.85rem; 
    }
    .table-uplate td, .table-uplate th { 
      padding: 8px; 
      text-align: right; 
      vertical-align: middle;
    }
    .table-uplate th {
      background-color: #343a40;
      color: white;
      font-weight: bold;
    }
     .plan { 
       color: #000000; 
       font-weight: bold;
     }
     .stvarno { 
       color: #000000; 
       font-weight: bold;
     }
     .razlika { 
       color: #000000; 
       font-weight: bold;
     }
     .negativno { 
       color: #dc3545; 
       font-weight: bold;
     }
    .table-responsive {
      max-height: 80vh;
      overflow-y: auto;
    }
     .objekat-naziv {
       font-weight: bold;
       color: #495057;
       min-width: 150px;
     }
     .clickable-row:hover {
       background-color: #f8f9fa;
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

          <h3 class="center sekcija">UPLATE PO MESECIMA <i class="fas fa-calendar-alt"></i></h3> <br>

          <!-- Forma za selektor meseci -->
          <div class="card mb-4 w-100">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-filter"></i> Izbor perioda</h5>
            </div>
            <div class="card-body">
               <form method="POST" class="row">
                 <!-- OD period -->
                 <div class="col-md-2">
                   <label for="od_mesec" class="form-label">Od mesec:</label>
                   <select class="form-control" id="od_mesec" name="od_mesec" required>
                     <option value="">Mesec...</option>
                     <?php foreach ($mesec_opcije as $mesec): ?>
                       <option value="<?= $mesec['value'] ?>" <?= (isset($_POST['od_mesec']) && $_POST['od_mesec'] == $mesec['value']) ? 'selected' : '' ?>>
                         <?= $mesec['label'] ?>
                       </option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="col-md-2">
                   <label for="od_godina" class="form-label">Od godina:</label>
                   <select class="form-control" id="od_godina" name="od_godina" required>
                     <option value="">Godina...</option>
                     <?php foreach ($godina_opcije as $godina): ?>
                       <option value="<?= $godina['value'] ?>" <?= (isset($_POST['od_godina']) && $_POST['od_godina'] == $godina['value']) ? 'selected' : '' ?>>
                         <?= $godina['label'] ?>
                       </option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 
                 <!-- DO period -->
                 <div class="col-md-2">
                   <label for="do_mesec" class="form-label">Do mesec:</label>
                   <select class="form-control" id="do_mesec" name="do_mesec" required>
                     <option value="">Mesec...</option>
                     <?php foreach ($mesec_opcije as $mesec): ?>
                       <option value="<?= $mesec['value'] ?>" <?= (isset($_POST['do_mesec']) && $_POST['do_mesec'] == $mesec['value']) ? 'selected' : '' ?>>
                         <?= $mesec['label'] ?>
                       </option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="col-md-2">
                   <label for="do_godina" class="form-label">Do godina:</label>
                   <select class="form-control" id="do_godina" name="do_godina" required>
                     <option value="">Godina...</option>
                     <?php foreach ($godina_opcije as $godina): ?>
                       <option value="<?= $godina['value'] ?>" <?= (isset($_POST['do_godina']) && $_POST['do_godina'] == $godina['value']) ? 'selected' : '' ?>>
                         <?= $godina['label'] ?>
                       </option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 
                 <!-- Dugme -->
                 <div class="col-md-4 d-flex align-items-end">
                   <button type="submit" class="btn btn-primary btn-block">
                     <i class="fas fa-search"></i> Prikaži tabelu
                   </button>
                 </div>
               </form>
            </div>
          </div>

          <?php if ($prikazi_tabelu): ?>
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-uplate">
              <thead class="thead-dark">
                <tr>
                  <th class="objekat-naziv">Objekat</th>
                  <?php foreach ($meseci as $mesec): ?>
                    <th><?= $mesec['label'] ?></th>
                  <?php endforeach; ?>
                  <th>UKUPNO</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($podaci as $objekat_id => $red): ?>
                  <!-- Planirane uplate red -->
                  <tr class="clickable-row" onclick="window.location.href='graf_objekta.php?id=<?= $objekat_id ?>&naziv=<?= urlencode($red['naziv']) ?>&od_mesec=<?= $od_mesec ?>&od_godina=<?= $od_godina ?>&do_mesec=<?= $do_mesec ?>&do_godina=<?= $do_godina ?>'" style="cursor: pointer;">
                    <td class="objekat-naziv"><strong><?= htmlspecialchars($red['naziv']) ?> - Planirane</strong></td>
                     <?php 
                     $ukupno_plan = 0;
                     foreach ($meseci as $mesec): 
                       $plan = $red['plan'][$mesec['key']] ?? 0;
                       $ukupno_plan += $plan;
                       $plan_class = $plan < 0 ? 'negativno' : 'plan';
                     ?>
                       <td class="<?= $plan_class ?>"><?= number_format($plan, 0, ',', '.') ?> €</td>
                     <?php endforeach; ?>
                     <?php $ukupno_plan_class = $ukupno_plan < 0 ? 'negativno' : 'plan'; ?>
                     <td class="<?= $ukupno_plan_class ?>"><strong><?= number_format($ukupno_plan, 0, ',', '.') ?> €</strong></td>
                  </tr>
                  
                  <!-- Stvarne uplate red -->
                  <tr class="clickable-row" onclick="window.location.href='graf_objekta.php?id=<?= $objekat_id ?>&naziv=<?= urlencode($red['naziv']) ?>&od_mesec=<?= $od_mesec ?>&od_godina=<?= $od_godina ?>&do_mesec=<?= $do_mesec ?>&do_godina=<?= $do_godina ?>'" style="cursor: pointer;">
                    <td class="objekat-naziv"><strong><?= htmlspecialchars($red['naziv']) ?> - Stvarne</strong></td>
                     <?php 
                     $ukupno_stvarno = 0;
                     foreach ($meseci as $mesec): 
                       $stvarno = $red['stvarno'][$mesec['key']] ?? 0;
                       $ukupno_stvarno += $stvarno;
                       $stvarno_class = $stvarno < 0 ? 'negativno' : 'stvarno';
                     ?>
                       <td class="<?= $stvarno_class ?>"><?= number_format($stvarno, 0, ',', '.') ?> €</td>
                     <?php endforeach; ?>
                     <?php $ukupno_stvarno_class = $ukupno_stvarno < 0 ? 'negativno' : 'stvarno'; ?>
                     <td class="<?= $ukupno_stvarno_class ?>"><strong><?= number_format($ukupno_stvarno, 0, ',', '.') ?> €</strong></td>
                  </tr>
                  
                  <!-- Razlika red -->
                  <tr class="clickable-row" onclick="window.location.href='graf_objekta.php?id=<?= $objekat_id ?>&naziv=<?= urlencode($red['naziv']) ?>&od_mesec=<?= $od_mesec ?>&od_godina=<?= $od_godina ?>&do_mesec=<?= $do_mesec ?>&do_godina=<?= $do_godina ?>'" style="cursor: pointer;">
                    <td class="objekat-naziv"><strong><?= htmlspecialchars($red['naziv']) ?> - Razlika</strong></td>
                     <?php 
                     foreach ($meseci as $mesec): 
                       $plan = $red['plan'][$mesec['key']] ?? 0;
                       $stvarno = $red['stvarno'][$mesec['key']] ?? 0;
                       $razlika = $stvarno - $plan; // Stvarno - Planirano
                       $razlika_class = $razlika < 0 ? 'negativno' : 'razlika';
                     ?>
                       <td class="<?= $razlika_class ?>"><?= number_format($razlika, 0, ',', '.') ?> €</td>
                     <?php endforeach; ?>
                     <?php 
                     $ukupna_razlika = $ukupno_stvarno - $ukupno_plan; // Stvarno - Planirano
                     $ukupna_razlika_class = $ukupna_razlika < 0 ? 'negativno' : 'razlika';
                     ?>
                     <td class="<?= $ukupna_razlika_class ?>"><strong><?= number_format($ukupna_razlika, 0, ',', '.') ?> €</strong></td>
                  </tr>
                  
                  <!-- Prazan red za razmak -->
                  <tr>
                    <td colspan="<?= count($meseci) + 2 ?>" style="height: 10px; border: none;"></td>
                  </tr>
                <?php endforeach; ?>
                
                <!-- UKUPNI REDOVI -->
                <tr class="clickable-row" onclick="window.location.href='graf_objekta.php?id=0&naziv=SVI OBJEKTI&od_mesec=<?= $od_mesec ?>&od_godina=<?= $od_godina ?>&do_mesec=<?= $do_mesec ?>&do_godina=<?= $do_godina ?>'" style="cursor: pointer; border-top: 3px solid #343a40;">
                  <td class="objekat-naziv" style="background-color: #f8f9fa;"><strong>UKUPNO - Planirane</strong></td>
                  <?php 
                  $ukupno_plan_sve = 0;
                  foreach ($meseci as $mesec): 
                    $mesec_plan = 0;
                    foreach ($podaci as $red) {
                      $mesec_plan += $red['plan'][$mesec['key']] ?? 0;
                    }
                    $ukupno_plan_sve += $mesec_plan;
                    $plan_class = $mesec_plan < 0 ? 'negativno' : 'plan';
                  ?>
                    <td class="<?= $plan_class ?>" style="background-color: #f8f9fa;"><strong><?= number_format($mesec_plan, 0, ',', '.') ?> €</strong></td>
                  <?php endforeach; ?>
                  <?php $ukupno_plan_class = $ukupno_plan_sve < 0 ? 'negativno' : 'plan'; ?>
                  <td class="<?= $ukupno_plan_class ?>" style="background-color: #f8f9fa;"><strong><?= number_format($ukupno_plan_sve, 0, ',', '.') ?> €</strong></td>
                </tr>
                
                <tr class="clickable-row" onclick="window.location.href='graf_objekta.php?id=0&naziv=SVI OBJEKTI&od_mesec=<?= $od_mesec ?>&od_godina=<?= $od_godina ?>&do_mesec=<?= $do_mesec ?>&do_godina=<?= $do_godina ?>'" style="cursor: pointer; border-top: 2px solid #343a40;">
                  <td class="objekat-naziv" style="background-color: #f8f9fa;"><strong>UKUPNO - Stvarne</strong></td>
                  <?php 
                  $ukupno_stvarno_sve = 0;
                  foreach ($meseci as $mesec): 
                    $mesec_stvarno = 0;
                    foreach ($podaci as $red) {
                      $mesec_stvarno += $red['stvarno'][$mesec['key']] ?? 0;
                    }
                    $ukupno_stvarno_sve += $mesec_stvarno;
                    $stvarno_class = $mesec_stvarno < 0 ? 'negativno' : 'stvarno';
                  ?>
                    <td class="<?= $stvarno_class ?>" style="background-color: #f8f9fa;"><strong><?= number_format($mesec_stvarno, 0, ',', '.') ?> €</strong></td>
                  <?php endforeach; ?>
                  <?php $ukupno_stvarno_class = $ukupno_stvarno_sve < 0 ? 'negativno' : 'stvarno'; ?>
                  <td class="<?= $ukupno_stvarno_class ?>" style="background-color: #f8f9fa;"><strong><?= number_format($ukupno_stvarno_sve, 0, ',', '.') ?> €</strong></td>
                </tr>
                
                <tr class="clickable-row" onclick="window.location.href='graf_objekta.php?id=0&naziv=SVI OBJEKTI&od_mesec=<?= $od_mesec ?>&od_godina=<?= $od_godina ?>&do_mesec=<?= $do_mesec ?>&do_godina=<?= $do_godina ?>'" style="cursor: pointer; border-top: 2px solid #343a40;">
                  <td class="objekat-naziv" style="background-color: #f8f9fa;"><strong>UKUPNO - Razlika</strong></td>
                  <?php 
                  foreach ($meseci as $mesec): 
                    $mesec_plan = 0;
                    $mesec_stvarno = 0;
                    foreach ($podaci as $red) {
                      $mesec_plan += $red['plan'][$mesec['key']] ?? 0;
                      $mesec_stvarno += $red['stvarno'][$mesec['key']] ?? 0;
                    }
                    $mesec_razlika = $mesec_stvarno - $mesec_plan;
                    $razlika_class = $mesec_razlika < 0 ? 'negativno' : 'razlika';
                  ?>
                    <td class="<?= $razlika_class ?>" style="background-color: #f8f9fa;"><strong><?= number_format($mesec_razlika, 0, ',', '.') ?> €</strong></td>
                  <?php endforeach; ?>
                  <?php 
                  $ukupna_razlika_sve = $ukupno_stvarno_sve - $ukupno_plan_sve;
                  $ukupna_razlika_class = $ukupna_razlika_sve < 0 ? 'negativno' : 'razlika';
                  ?>
                  <td class="<?= $ukupna_razlika_class ?>" style="background-color: #f8f9fa;"><strong><?= number_format($ukupna_razlika_sve, 0, ',', '.') ?> €</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Molimo izaberite period za prikaz tabele uplata.
          </div>
          <?php endif; ?>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

</html>
