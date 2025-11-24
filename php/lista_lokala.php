<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

$selektovanId = isset($_GET['objekat_id']) ? (int)$_GET['objekat_id'] : 0;

// Obrada raskidanja prodaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija']) && $_POST['akcija'] === 'raskini_prodaju') {
  try {
    $lokal_id = (int)($_POST['lokal_id'] ?? 0);
    $selektovanObjekatId = isset($_POST['selektovan_objekat_id']) ? (int)$_POST['selektovan_objekat_id'] : 0;
    
    if ($lokal_id <= 0) {
      $_SESSION['error_message'] = 'Greška: Neispravan ID lokala.';
    } else {
      $crud = new CRUD($_SESSION['godina']);
      $crud->table = "lokali";
      
      // Proveri da li lokal postoji i da li je prodat
      $lokal = $crud->select(['*'], ['id' => $lokal_id, 'prodat' => 1]);
      
      if (empty($lokal)) {
        $_SESSION['error_message'] = 'Greška: Lokal nije pronađen ili nije prodat.';
      } else {
        // Ažuriraj lokal - vrati prodat na 0 i obriši podatke o kupcu
        $crud->update([
          'prodat' => 0,
          'kupac_id' => null,
          'datum_prodaje' => null
        ], ['id' => $lokal_id]);
        
        $_SESSION['success_message'] = 'Prodaja lokala je uspešno raskinuta.';
      }
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = 'Greška: ' . $e->getMessage();
  }
  
  // Redirect da se osveži stranica
  $redirectUrl = $_SERVER['PHP_SELF'];
  if ($selektovanObjekatId > 0) {
    $redirectUrl .= '?objekat_id=' . $selektovanObjekatId;
  }
  header('Location: ' . $redirectUrl);
  exit;
}

$objektiCrud = new CRUD($_SESSION['godina']);
$objektiCrud->table = 'objekti';
$objekti = $objektiCrud->select(['*'], [], 'SELECT * FROM objekti ORDER BY naziv ASC');

$lokali = [];
$selektovanObjekatNaziv = '';

if ($selektovanId > 0) {
  $objekat = $objektiCrud->select(['naziv'], ['id' => $selektovanId]);
  if (!empty($objekat)) {
    $selektovanObjekatNaziv = $objekat[0]['naziv'];
  }
  
  $lokaliCrud = new CRUD($_SESSION['godina']);
  $lokaliCrud->table = "lokali";
  $lokali = $lokaliCrud->select(['*'], [], "SELECT l.*, o.naziv as objekat_naziv FROM lokali l LEFT JOIN objekti o ON l.objekat_id = o.id WHERE l.objekat_id = {$selektovanId} ORDER BY CAST(SUBSTRING(l.naziv, 2) AS UNSIGNED) ASC, l.naziv ASC");
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Lista lokala za izmenu</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

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

          <h3 class="center sekcija">LISTA LOKALA ZA IZMENU <i class="fas fa-edit"></i></h3> <br>

          <form method="get" class="mb-4 w-100">
            <div class="form-row justify-content-center">
              <div class="col-md-6 col-sm-12 mb-2">
                <label for="objekat_id"><i class="far fa-building"></i> Izaberite objekat</label>
                <select class="form-control" id="objekat_id" name="objekat_id" required onchange="this.form.submit()">
                  <option value="">Izaberite objekat</option>
                  <?php foreach ($objekti as $objekatOpcija): ?>
                    <option value="<?= (int)$objekatOpcija['id'] ?>" <?= $selektovanId === (int)$objekatOpcija['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($objekatOpcija['naziv']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </form>

          <?php if ($selektovanId === 0): ?>
            <div class="alert alert-info text-center w-100" role="alert">
              Molimo prvo izaberite objekat kako biste videli listu lokala.
            </div>
          <?php else: ?>
            <?php if (!empty($selektovanObjekatNaziv)): ?>
              <h4 class="mb-3 text-center"><?= htmlspecialchars($selektovanObjekatNaziv) ?></h4>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th scope="col">Naziv</th>
                    <th scope="col">Kvadratura</th>
                    <th scope="col">Cena po m²</th>
                    <th scope="col">PDV</th>
                    <th scope="col">Status</th>
                    <th scope="col">Akcije</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($lokali) === 0): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted">Nema unetih lokala za izabrani objekat.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($lokali as $lokal): ?>
                      <tr>
                        <td><?= htmlspecialchars($lokal['naziv'] ?? ('Lokal #' . $lokal['id'])) ?></td>
                        <td><?= number_format((float)$lokal['kvadratura'], 2, ',', '.') ?> m²</td>
                        <td><?= number_format((float)$lokal['cena_po_m2'], 2, ',', '.') ?> €</td>
                        <td><?= number_format((float)($lokal['pdv'] ?? $lokal['pdv_suma'] ?? 0), 2, ',', '.') ?> €</td>
                        <td><?= ((int)($lokal['prodat'] ?? 0)) === 1 ? '<span class="badge badge-success">Prodat</span>' : '<span class="badge badge-light">Na prodaju</span>' ?></td>
                        <td>
                          <a href="izmena_lokala.php?id=<?= $lokal['id'] ?>" class="btn btn-primary btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                            <i class="fas fa-edit"></i> Izmeni
                          </a>
                          <?php if ((int)($lokal['prodat'] ?? 0) === 1): ?>
                            <form method="POST" style="display: inline-block;" class="ml-2" onsubmit="return confirm('Da li ste sigurni da želite da raskinete prodaju ovog lokala?')">
                              <input type="hidden" name="akcija" value="raskini_prodaju">
                              <input type="hidden" name="lokal_id" value="<?= $lokal['id'] ?>">
                              <input type="hidden" name="selektovan_objekat_id" value="<?= $selektovanId ?>">
                              <button type="submit" class="btn btn-warning btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                                <i class="fas fa-undo"></i> Raskini prodaju
                              </button>
                            </form>
                          <?php endif; ?>
                          <a href="obrisi_lokal.php?id=<?= $lokal['id'] ?>" class="btn btn-danger btn-md ml-2" style="min-width: 100px; padding: 8px 16px; color: white !important;" onclick="return confirm('Da li ste sigurni da želite da obrišete ovaj lokal?')">
                            <i class="fas fa-trash"></i> Obriši
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

</html>
