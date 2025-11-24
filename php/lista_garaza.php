<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

$selektovanId = isset($_GET['objekat_id']) ? (int)$_GET['objekat_id'] : 0;

// Obrada raskidanja prodaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija']) && $_POST['akcija'] === 'raskini_prodaju') {
  try {
    $garaza_id = (int)($_POST['garaza_id'] ?? 0);
    $selektovanObjekatId = isset($_POST['selektovan_objekat_id']) ? (int)$_POST['selektovan_objekat_id'] : 0;
    
    if ($garaza_id <= 0) {
      $_SESSION['error_message'] = 'Greška: Neispravan ID garaže.';
    } else {
      $crud = new CRUD($_SESSION['godina']);
      $crud->table = "garaze";
      
      // Proveri da li garaža postoji i da li je prodata
      $garaza = $crud->select(['*'], ['id' => $garaza_id, 'prodat' => 1]);
      
      if (empty($garaza)) {
        $_SESSION['error_message'] = 'Greška: Garaža nije pronađena ili nije prodata.';
      } else {
        // Ažuriraj garažu - vrati prodat na 0 i obriši podatke o kupcu
        $crud->update([
          'prodat' => 0,
          'kupac_id' => null,
          'datum_prodaje' => null
        ], ['id' => $garaza_id]);
        
        $_SESSION['success_message'] = 'Prodaja garaže je uspešno raskinuta.';
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

$garaze = [];
$selektovanObjekatNaziv = '';

if ($selektovanId > 0) {
  $objekat = $objektiCrud->select(['naziv'], ['id' => $selektovanId]);
  if (!empty($objekat)) {
    $selektovanObjekatNaziv = $objekat[0]['naziv'];
  }
  
  $garazeCrud = new CRUD($_SESSION['godina']);
  $garazeCrud->table = "garaze";
  $garaze = $garazeCrud->select(['*'], [], "SELECT g.*, o.naziv as objekat_naziv, k.ime AS kupac_ime, k.prezime AS kupac_prezime FROM garaze g LEFT JOIN objekti o ON g.objekat_id = o.id LEFT JOIN kupci k ON g.kupac_id = k.id WHERE g.objekat_id = {$selektovanId} ORDER BY CAST(SUBSTRING(g.naziv, 2) AS UNSIGNED) ASC, g.naziv ASC");
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Lista garaža za izmenu</title>

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

          <h3 class="center sekcija">LISTA GARAŽA ZA IZMENU <i class="fas fa-edit"></i></h3> <br>

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
              Molimo prvo izaberite objekat kako biste videli listu garaža.
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
                    <th scope="col">Objekat</th>
                    <th scope="col">Cena sa PDV</th>
                    <th scope="col">Status</th>
                    <th scope="col">Akcije</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($garaze) === 0): ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted">Nema unetih garaža za izabrani objekat.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($garaze as $garaza): ?>
                      <tr>
                        <td><?= htmlspecialchars($garaza['naziv'] ?? ('Garaža #' . (int)$garaza['id'])) ?></td>
                        <td><?= htmlspecialchars($garaza['objekat_naziv'] ?? 'Nepoznat objekat') ?></td>
                        <td><?= number_format((float)($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0), 2, ',', '.') ?> €</td>
                        <td>
                          <?php $garazaProdata = ((int)($garaza['prodat'] ?? 0)) === 1; ?>
                          <?php if ($garazaProdata): ?>
                            <?php
                            $kupacId = (int)($garaza['kupac_id'] ?? 0);
                            $kupacNaziv = trim(($garaza['kupac_ime'] ?? '') . ' ' . ($garaza['kupac_prezime'] ?? ''));
                            if ($kupacNaziv === '' && $kupacId > 0) {
                              $kupacNaziv = 'Kupac #' . $kupacId;
                            }
                            $tipProdaje = $garaza['tip_prodaje'] ?? null;
                            $badgeClass = 'badge badge-success';
                            $linkStyle = 'text-decoration: none; color: #fff !important;';

                            if ($tipProdaje === 'lokacija') {
                              $badgeClass = 'badge badge-danger';
                            } elseif ($tipProdaje === 'kompenzacija') {
                              $badgeClass = 'badge badge-warning';
                              $linkStyle = 'text-decoration: none; color: #212529 !important;';
                            }
                            ?>
                            <?php if ($kupacId > 0 && $kupacNaziv !== ''): ?>
                              <a href="kupci_detaljno.php?id=<?= $kupacId ?>" class="<?= $badgeClass ?>" style="<?= $linkStyle ?>"><?= htmlspecialchars($kupacNaziv) ?></a>
                            <?php else: ?>
                              <span class="<?= $badgeClass ?>" style="<?= $linkStyle ?>">Prodata</span>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="badge badge-light">Na prodaju</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="izmena_garaze.php?id=<?= $garaza['id'] ?>" class="btn btn-primary btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                            <i class="fas fa-edit"></i> Izmeni
                          </a>
                          <?php if ((int)($garaza['prodat'] ?? 0) === 1): ?>
                            <form method="POST" style="display: inline-block;" class="ml-2" onsubmit="return confirm('Da li ste sigurni da želite da raskinete prodaju ove garaže?')">
                              <input type="hidden" name="akcija" value="raskini_prodaju">
                              <input type="hidden" name="garaza_id" value="<?= $garaza['id'] ?>">
                              <input type="hidden" name="selektovan_objekat_id" value="<?= $selektovanId ?>">
                              <button type="submit" class="btn btn-warning btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                                <i class="fas fa-undo"></i> Raskini prodaju
                              </button>
                            </form>
                          <?php endif; ?>
                          <a href="obrisi_garazu.php?id=<?= $garaza['id'] ?>" class="btn btn-danger btn-md ml-2" style="min-width: 100px; padding: 8px 16px; color: white !important;" onclick="return confirm('Da li ste sigurni da želite da obrišete ovu garažu?')">
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
