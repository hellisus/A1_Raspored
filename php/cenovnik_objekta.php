<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada PDF generisanja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija']) && $_POST['akcija'] === 'generisi_pdf') {
  // Proveri da li TCPDF postoji
  if (!file_exists('tcpdf/tcpdf.php')) {
    $_SESSION['error_message'] = 'TCPDF biblioteka nije instalirana. Molimo kontaktirajte administratora.';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?objekat_id=' . $_POST['objekat_id']);
    exit;
  }
  
  require_once 'tcpdf/tcpdf.php';
  
  $objekatId = (int)$_POST['objekat_id'];
  
  // Pronađi objekat
  $objekatCrud = new CRUD($_SESSION['godina']);
  $objekatCrud->table = "objekti";
  $objekat = $objekatCrud->select(['*'], ['id' => $objekatId]);
  
  if (empty($objekat)) {
    $_SESSION['error_message'] = 'Objekat nije pronađen.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
  }
  
  $objekatNaziv = $objekat[0]['naziv'];
  
  // Kreiranje PDF-a
  $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
  
  // Informacije o dokumentu
  $pdf->SetCreator('GP RAZ');
  $pdf->SetAuthor('GP RAZ');
  $pdf->SetTitle('Cenovnik za objekat: ' . $objekatNaziv);
  $pdf->SetSubject('Cenovnik stambenih jedinica');
  
  // Uklanjanje default header/footer
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);
  
  // Margine
  $pdf->SetMargins(15, 20, 15);
  $pdf->SetAutoPageBreak(TRUE, 25);
  
  // Dodavanje stranice
  $pdf->AddPage();
  
  // Logo u zaglavlju (preskoči PNG sa alfa kanalom ako GD/Imagick nisu dostupni)
  $logoAdded = false;
  $pngSupported = extension_loaded('gd') || extension_loaded('imagick');
  $logoCandidates = [
    '../img/raz-invest-logo.png',
    '../img/raz-invest-logo.jpg',
    '../img/raz-invest-logo.jpeg',
    '../img/Raz.png',
    '../img/Raz.jpg'
  ];

  foreach ($logoCandidates as $candidate) {
    if ($logoAdded) {
      break;
    }

    if (!file_exists($candidate)) {
      continue;
    }

    $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    if ($extension === 'png' && !$pngSupported) {
      continue;
    }

    $type = strtoupper($extension);
    try {
      $pdf->Image($candidate, 15, 10, 30, 0, $type);
      $logoAdded = true;
    } catch (Exception $e) {
      $logoAdded = false;
    }
  }
  
  // Naslov
  $pdf->SetFont('helvetica', 'B', 16);
  $pdf->SetY(15);
  $pdf->Cell(0, 10, 'CENOVNIK ZA OBJEKAT', 0, 1, 'C');
  $pdf->SetFont('helvetica', 'B', 14);
  $pdf->Cell(0, 8, $objekatNaziv, 0, 1, 'C');
  $pdf->Ln(10);
  
  // Datum
  $pdf->SetFont('helvetica', '', 10);
  $pdf->Cell(0, 5, 'Datum: ' . formatirajDatum(new DateTimeImmutable()), 0, 1, 'R');
  $pdf->Ln(5);
  
  // Inicijalizuj varijable za ukupne sume
  $ukupnoStanovi = 0;
  $ukupnoLokali = 0;
  $ukupnoGaraze = 0;
  $ukupnoParking = 0;
  
  // Stanovi
  $stanoviCrud = new CRUD($_SESSION['godina']);
  $stanoviCrud->table = "stanovi";
  $stanovi = $stanoviCrud->select(['*'], [], "SELECT * FROM stanovi WHERE objekat_id = {$objekatId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
  
  if (count($stanovi) > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'STANOVI', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Tabela za stanove
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 8, 'Naziv', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Kvadratura', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Cena po m² sa PDV', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'PDV', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Cena po m² bez PDV', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Ukupna cena', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $ukupnoStanovi = 0;
    foreach ($stanovi as $stan) {
      $pdf->Cell(30, 6, $stan['naziv'] ?? 'Stan #' . $stan['id'], 1, 0, 'C');
      $pdf->Cell(25, 6, number_format((float)$stan['kvadratura'], 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(35, 6, number_format((float)($stan['realna_cena_po_m2'] ?? 0), 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(25, 6, number_format((float)($stan['pdv'] ?? 0), 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(30, 6, number_format((float)$stan['cena_po_m2'], 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(35, 6, number_format((float)$stan['ukupna_cena'], 2, ',', '.'), 1, 1, 'C');
      $ukupnoStanovi += (float)$stan['ukupna_cena'];
    }
    $pdf->Ln(5);
  }
  
  // Lokali
  $lokaliCrud = new CRUD($_SESSION['godina']);
  $lokaliCrud->table = "lokali";
  $lokali = $lokaliCrud->select(['*'], [], "SELECT * FROM lokali WHERE objekat_id = {$objekatId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
  
  if (count($lokali) > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'LOKALI', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Tabela za lokale
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 8, 'Naziv', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Kvadratura', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Cena po m²', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'PDV', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Ukupna cena', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $ukupnoLokali = 0;
    foreach ($lokali as $lokal) {
      $pdf->Cell(30, 6, $lokal['naziv'] ?? 'Lokal #' . $lokal['id'], 1, 0, 'C');
      $pdf->Cell(25, 6, number_format((float)$lokal['kvadratura'], 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(30, 6, number_format((float)$lokal['cena_po_m2'], 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(25, 6, number_format((float)($lokal['pdv'] ?? $lokal['pdv_suma'] ?? 0), 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(40, 6, number_format((float)($lokal['ukupna_cena'] ?? 0), 2, ',', '.'), 1, 1, 'C');
      $ukupnoLokali += (float)($lokal['ukupna_cena'] ?? 0);
    }
    $pdf->Ln(5);
  }
  
  // Garaže
  $garazeCrud = new CRUD($_SESSION['godina']);
  $garazeCrud->table = "garaze";
  $garaze = $garazeCrud->select(['*'], [], "SELECT * FROM garaze WHERE objekat_id = {$objekatId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
  
  if (count($garaze) > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'GARAŽE', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Tabela za garaže
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(40, 8, 'Naziv', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Cena', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'PDV', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Ukupna cena', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $ukupnoGaraze = 0;
    foreach ($garaze as $garaza) {
      $pdf->Cell(40, 6, $garaza['naziv'] ?? 'Garaža #' . $garaza['id'], 1, 0, 'C');
      $pdf->Cell(35, 6, number_format((float)($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0), 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(30, 6, number_format((float)($garaza['pdv'] ?? 0), 2, ',', '.'), 1, 0, 'C');
      $pdf->Cell(35, 6, number_format((float)($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0), 2, ',', '.'), 1, 1, 'C');
      $ukupnoGaraze += (float)($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0);
    }
    $pdf->Ln(5);
  }
  
  // Parking mesta
  $parkingCrud = new CRUD($_SESSION['godina']);
  $parkingCrud->table = "parking_mesta";
  $parking = $parkingCrud->select(['*'], [], "SELECT * FROM parking_mesta WHERE objekat_id = {$objekatId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
  
  if (count($parking) > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'PARKING MESTA', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Tabela za parking mesta
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(80, 8, 'Naziv', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Cena', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $ukupnoParking = 0;
    foreach ($parking as $pm) {
      $pdf->Cell(80, 6, $pm['naziv'] ?? 'PM #' . $pm['id'], 1, 0, 'C');
      $pdf->Cell(40, 6, number_format((float)$pm['cena'], 2, ',', '.'), 1, 1, 'C');
      $ukupnoParking += (float)$pm['cena'];
    }
    $pdf->Ln(5);
  }
  
  // Očisti output buffer pre slanja PDF-a
  ob_clean();
  
  // Output PDF
  $filename = 'Cenovnik_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $objekatNaziv) . '_' . date('Y-m-d') . '.pdf';
  $pdf->Output($filename, 'D');
  exit;
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Cenovnik za objekat</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/funkcije.js"></script>
  
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Inicijalizacija Select2 za dropdown objekta
      $('#objekat_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Izaberite objekat',
        allowClear: true,
        width: '100%'
      });
      
      // Forsiranje širine nakon inicijalizacije
      setTimeout(function() {
        $('#objekat_id').next('.select2-container').css({
          'width': '100%',
          'max-width': '100%'
        });
        $('#objekat_id').next('.select2-container').find('.select2-selection').css({
          'width': '100%',
          'max-width': '100%'
        });
      }, 100);
    });
  </script>
  
  <style>
    /* Select2 širina - forsiranje */
    .select2-container {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    .select2-container--bootstrap-5 {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--single {
      width: 100% !important;
      max-width: 100% !important;
      height: calc(1.5em + 0.75rem + 2px) !important;
      padding: 0.375rem 0.75rem !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
      width: 100% !important;
      max-width: 100% !important;
      padding-left: 0 !important;
      padding-right: 20px !important;
    }
    
    /* Specifično za naš dropdown */
    #objekat_id + .select2-container {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    #objekat_id + .select2-container .select2-selection {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    /* Dropdown opcije širina */
    .select2-dropdown {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    .select2-results {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    /* Card širina */
    .card.w-100 {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    /* Container širina */
    .containter {
      width: 100% !important;
      max-width: 100% !important;
    }
    
    /* Flex container */
    .d-flex.flex-column.justify-content-center.align-items-center {
      width: 100% !important;
      max-width: 100% !important;
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
      <div class="containter">

        <div class="d-flex flex-column justify-content-center align-items-center">

          <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['error_message'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
          <?php endif; ?>

          <h3 class="center sekcija">CENOVNIK ZA OBJEKAT <i class="fas fa-file-invoice"></i></h3> <br>

          <!-- Objekat selekcija -->
          <div class="card mb-4 w-100">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="far fa-building"></i> Izbor objekta</h5>
            </div>
            <div class="card-body">
              <div class="form-group row">
                <div class="col-md-6 col-sm-12 mb-3">
                  <label for="objekat_id" class="col-form-label"><i class="far fa-building"></i> Izaberite objekat</label>
                  <select class="form-control" id="objekat_id" name="objekat_id" onchange="window.location.href='?objekat_id=' + this.value">
                    <option value="">Izaberite objekat</option>
                    <?php
                    $objCrud = new CRUD($_SESSION['godina']);
                    $objCrud->table = "objekti";
                    $objekti = $objCrud->select(['*'], [], "SELECT * FROM objekti ORDER BY naziv ASC");
                    $selektovanId = isset($_GET['objekat_id']) ? (int)$_GET['objekat_id'] : 0;
                    foreach ($objekti as $objekat): ?>
                      <option value="<?= $objekat['id'] ?>" <?= $selektovanId === (int)$objekat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($objekat['naziv']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php if ($selektovanId > 0): ?>
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label class="col-form-label"><i class="fas fa-download"></i> Akcije</label>
                    <div>
                      <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="akcija" value="generisi_pdf">
                        <input type="hidden" name="objekat_id" value="<?= $selektovanId ?>">
                        <button type="submit" class="btn btn-danger btn-lg">
                          <i class="fas fa-file-pdf"></i> Generiši PDF
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($selektovanId > 0): ?>
            <?php
            // Pronađi objekat
            $objekatCrud = new CRUD($_SESSION['godina']);
            $objekatCrud->table = "objekti";
            $objekat = $objekatCrud->select(['*'], ['id' => $selektovanId]);
            $objekatNaziv = !empty($objekat) ? $objekat[0]['naziv'] : 'Nepoznat objekat';
            ?>

            <div class="mb-4">
              <h2 class="text-center mb-4"><?= htmlspecialchars($objekatNaziv) ?></h2>
              
              <!-- Stanovi -->
              <?php
              $stanoviCrud = new CRUD($_SESSION['godina']);
              $stanoviCrud->table = "stanovi";
              $stanovi = $stanoviCrud->select(['*'], [], "SELECT * FROM stanovi WHERE objekat_id = {$selektovanId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
              ?>
              
              <?php if (count($stanovi) > 0): ?>
                <h4 class="mt-4 mb-3"><i class="fas fa-home"></i> Stanovi</h4>
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th scope="col">Naziv</th>
                        <th scope="col">Kvadratura (m²)</th>
                        <th scope="col">Cena po m² sa PDV (€)</th>
                        <th scope="col">PDV (€)</th>
                        <th scope="col">Cena po m² bez PDV (€)</th>
                        <th scope="col">Ukupna cena (€)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($stanovi as $stan): ?>
                        <tr>
                          <td><?= htmlspecialchars($stan['naziv'] ?? 'Stan #' . $stan['id']) ?></td>
                          <td><?= number_format((float)$stan['kvadratura'], 2, ',', '.') ?></td>
                          <td><?= number_format((float)($stan['realna_cena_po_m2'] ?? 0), 2, ',', '.') ?></td>
                          <td><?= number_format((float)($stan['pdv'] ?? 0), 2, ',', '.') ?></td>
                          <td><?= number_format((float)$stan['cena_po_m2'], 2, ',', '.') ?></td>
                          <td><strong><?= number_format((float)$stan['ukupna_cena'], 2, ',', '.') ?></strong></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <!-- Lokali -->
              <?php
              $lokaliCrud = new CRUD($_SESSION['godina']);
              $lokaliCrud->table = "lokali";
              $lokali = $lokaliCrud->select(['*'], [], "SELECT * FROM lokali WHERE objekat_id = {$selektovanId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
              ?>
              
              <?php if (count($lokali) > 0): ?>
                <h4 class="mt-4 mb-3"><i class="fas fa-store"></i> Lokali</h4>
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th scope="col">Naziv</th>
                        <th scope="col">Kvadratura (m²)</th>
                        <th scope="col">Cena po m² (€)</th>
                        <th scope="col">PDV (€)</th>
                        <th scope="col">Ukupna cena (€)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($lokali as $lokal): ?>
                        <tr>
                          <td><?= htmlspecialchars($lokal['naziv'] ?? 'Lokal #' . $lokal['id']) ?></td>
                          <td><?= number_format((float)$lokal['kvadratura'], 2, ',', '.') ?></td>
                          <td><?= number_format((float)$lokal['cena_po_m2'], 2, ',', '.') ?></td>
                          <td><?= number_format((float)($lokal['pdv'] ?? $lokal['pdv_suma'] ?? 0), 2, ',', '.') ?></td>
                          <td><strong><?= number_format((float)($lokal['ukupna_cena'] ?? 0), 2, ',', '.') ?></strong></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <!-- Garaže -->
              <?php
              $garazeCrud = new CRUD($_SESSION['godina']);
              $garazeCrud->table = "garaze";
              $garaze = $garazeCrud->select(['*'], [], "SELECT * FROM garaze WHERE objekat_id = {$selektovanId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
              ?>
              
              <?php if (count($garaze) > 0): ?>
                <h4 class="mt-4 mb-3"><i class="fas fa-car"></i> Garaže</h4>
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th scope="col">Naziv</th>
                        <th scope="col">Cena (€)</th>
                        <th scope="col">PDV (€)</th>
                        <th scope="col">Ukupna cena (€)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($garaze as $garaza): ?>
                        <tr>
                          <td><?= htmlspecialchars($garaza['naziv'] ?? 'Garaža #' . $garaza['id']) ?></td>
                          <td><?= number_format((float)($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0), 2, ',', '.') ?></td>
                          <td><?= number_format((float)($garaza['pdv'] ?? 0), 2, ',', '.') ?></td>
                          <td><strong><?= number_format((float)($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0), 2, ',', '.') ?></strong></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <!-- Parking mesta -->
              <?php
              $parkingCrud = new CRUD($_SESSION['godina']);
              $parkingCrud->table = "parking_mesta";
              $parking = $parkingCrud->select(['*'], [], "SELECT * FROM parking_mesta WHERE objekat_id = {$selektovanId} AND (prodat IS NULL OR prodat = 0) ORDER BY CAST(SUBSTRING(naziv, 2) AS UNSIGNED) ASC, naziv ASC");
              ?>
              
              <?php if (count($parking) > 0): ?>
                <h4 class="mt-4 mb-3"><i class="fas fa-parking"></i> Parking mesta</h4>
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th scope="col">Naziv</th>
                        <th scope="col">Cena (€)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($parking as $pm): ?>
                        <tr>
                          <td><?= htmlspecialchars($pm['naziv'] ?? 'PM #' . $pm['id']) ?></td>
                          <td><strong><?= number_format((float)$pm['cena'], 2, ',', '.') ?></strong></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <!-- Ukupno -->
              <?php
              $ukupnoStanovi = array_sum(array_column($stanovi, 'ukupna_cena'));
              $ukupnoLokali = array_sum(array_column($lokali, 'ukupna_cena'));
              $ukupnoGaraze = array_sum(array_column($garaze, 'ukupna_cena'));
              $ukupnoParking = array_sum(array_column($parking, 'cena'));
              $ukupnoSve = $ukupnoStanovi + $ukupnoLokali + $ukupnoGaraze + $ukupnoParking;
              ?>

              <?php if ($ukupnoSve > 0): ?>
                <div class="mt-4 p-4 bg-light rounded">
                  <h4 class="text-center mb-3"><i class="fas fa-calculator"></i> Ukupna vrednost objekta</h4>
                  <div class="row text-center">
                    <div class="col-md-3 col-sm-6 mb-2">
                      <strong>Stanovi:</strong><br>
                      <span class="text-primary"><?= number_format($ukupnoStanovi, 2, ',', '.') ?> €</span>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                      <strong>Lokali:</strong><br>
                      <span class="text-primary"><?= number_format($ukupnoLokali, 2, ',', '.') ?> €</span>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                      <strong>Garaže:</strong><br>
                      <span class="text-primary"><?= number_format($ukupnoGaraze, 2, ',', '.') ?> €</span>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                      <strong>Parking:</strong><br>
                      <span class="text-primary"><?= number_format($ukupnoParking, 2, ',', '.') ?> €</span>
                    </div>
                  </div>
                  <hr>
                  <div class="text-center">
                    <h3 class="text-success">UKUPNO: <?= number_format($ukupnoSve, 2, ',', '.') ?> €</h3>
                  </div>
                </div>
              <?php endif; ?>

            </div>
          <?php endif; ?>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

</html>

