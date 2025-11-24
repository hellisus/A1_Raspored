<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

$selektovanId = isset($_GET['objekat_id']) ? (int)$_GET['objekat_id'] : 0;

$objektiCrud = new CRUD($_SESSION['godina']);
$objektiCrud->table = 'objekti';
$objekti = $objektiCrud->select(['*'], [], 'SELECT * FROM objekti ORDER BY naziv ASC');

// Obrada raskidanja prodaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija']) && $_POST['akcija'] === 'raskini_prodaju') {
  try {
    $parking_id = (int)($_POST['parking_id'] ?? 0);
    $selektovanObjekatId = isset($_POST['selektovan_objekat_id']) ? (int)$_POST['selektovan_objekat_id'] : 0;

    if ($parking_id <= 0) {
      $_SESSION['error_message'] = 'Greška: Neispravan ID parking mesta.';
    } else {
      $parkingCrud = new CRUD($_SESSION['godina']);
      $parkingCrud->table = "parking_mesta";

      $parking = $parkingCrud->select(['*'], ['id' => $parking_id, 'prodat' => 1]);

      if (empty($parking)) {
        $_SESSION['error_message'] = 'Greška: Parking mesto nije pronađeno ili nije prodato.';
      } else {
        $parkingCrud->update([
          'prodat' => 0,
          'kupac_id' => null,
          'datum_prodaje' => null,
          'datum_predugovora' => null,
          'kanal_prodaje_id' => null,
          'tip_prodaje' => null
        ], ['id' => $parking_id]);

        $_SESSION['success_message'] = 'Prodaja parking mesta je uspešno raskinuta.';
      }
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = 'Greška: ' . $e->getMessage();
  }

  $redirectUrl = $_SERVER['PHP_SELF'];
  if ($selektovanObjekatId > 0) {
    $redirectUrl .= '?objekat_id=' . $selektovanObjekatId;
  }
  header('Location: ' . $redirectUrl);
  exit;
}

$parkingMesta = [];
$selektovanObjekatNaziv = '';

if ($selektovanId > 0) {
  $objekat = $objektiCrud->select(['naziv'], ['id' => $selektovanId]);
  if (!empty($objekat)) {
    $selektovanObjekatNaziv = $objekat[0]['naziv'];
  }
  
  $parkingCrud = new CRUD($_SESSION['godina']);
  $parkingCrud->table = "parking_mesta";
  $parkingMestaQuery = <<<SQL
  SELECT
    pm.*,
    o.naziv AS objekat_naziv,
    s.naziv AS stan_naziv,
    s.kupac_id AS stan_kupac_id,
    k.ime AS kupac_ime,
    k.prezime AS kupac_prezime,
    ks.ime AS stan_kupac_ime,
    ks.prezime AS stan_kupac_prezime,
    upl.id_kupca AS uplata_kupac_id,
    ku.ime AS uplata_kupac_ime,
    ku.prezime AS uplata_kupac_prezime
  FROM parking_mesta pm
  LEFT JOIN objekti o ON pm.objekat_id = o.id
  LEFT JOIN stanovi s ON pm.stan_id = s.id
  LEFT JOIN kupci k ON pm.kupac_id = k.id
  LEFT JOIN kupci ks ON s.kupac_id = ks.id
  LEFT JOIN (
    SELECT ul.id_jedinice, ul.id_kupca
    FROM uplata ul
    INNER JOIN (
      SELECT id_jedinice, MAX(id) AS max_uplata_id
      FROM uplata
      WHERE tip_jedinice = 'parking' AND kes = 1
      GROUP BY id_jedinice
    ) AS lu ON lu.id_jedinice = ul.id_jedinice AND lu.max_uplata_id = ul.id
    WHERE ul.tip_jedinice = 'parking' AND ul.kes = 1
  ) AS upl ON upl.id_jedinice = pm.id
  LEFT JOIN kupci ku ON upl.id_kupca = ku.id
  WHERE pm.objekat_id = {$selektovanId}
  ORDER BY CAST(SUBSTRING(pm.naziv, 2) AS UNSIGNED) ASC, pm.naziv ASC
SQL;
  $parkingMesta = $parkingCrud->select(['*'], [], $parkingMestaQuery);
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Lista parking mesta za izmenu</title>

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

          <h3 class="center sekcija">LISTA PARKING MESTA ZA IZMENU <i class="fas fa-edit"></i></h3> <br>

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
              Molimo prvo izaberite objekat kako biste videli listu parking mesta.
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
                    <th scope="col">Cena</th>
                    <th scope="col">Povezani stan</th>
                    <th scope="col">Status</th>
                    <th scope="col">Napomena</th>
                    <th scope="col">Akcije</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($parkingMesta) === 0): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted">Nema unetih parking mesta za izabrani objekat.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($parkingMesta as $parking): ?>
                      <?php
                      $parkingProdat = ((int)($parking['prodat'] ?? 0)) === 1;
                      ?>
                      <tr>
                        <td><?= htmlspecialchars($parking['naziv'] ?? ('PM #' . (int)$parking['id'])) ?></td>
                        <td><?= number_format((int)$parking['cena'], 0, ',', '.') ?> €</td>
                        <td><?= isset($parking['stan_naziv']) && $parking['stan_naziv'] ? htmlspecialchars($parking['stan_naziv']) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                          <?php if ($parkingProdat): ?>
                            <?php
                            $kupacId = (int)($parking['kupac_id'] ?? 0);
                            $kupacNaziv = trim(($parking['kupac_ime'] ?? '') . ' ' . ($parking['kupac_prezime'] ?? ''));
                            if ($kupacNaziv === '' && $kupacId > 0) {
                              $kupacNaziv = 'Kupac #' . $kupacId;
                            }
                            if (($kupacId <= 0 || $kupacNaziv === '') && !empty($parking['uplata_kupac_id'])) {
                              $uplataKupacId = (int)$parking['uplata_kupac_id'];
                              $uplataKupacNaziv = trim(($parking['uplata_kupac_ime'] ?? '') . ' ' . ($parking['uplata_kupac_prezime'] ?? ''));
                              if ($uplataKupacNaziv === '' && $uplataKupacId > 0) {
                                $uplataKupacNaziv = 'Kupac #' . $uplataKupacId;
                              }
                              if ($uplataKupacNaziv !== '') {
                                $kupacId = $uplataKupacId;
                                $kupacNaziv = $uplataKupacNaziv;
                              }
                            }
                            if (($kupacId <= 0 || $kupacNaziv === '') && !empty($parking['stan_kupac_id'])) {
                              $stanKupacId = (int)$parking['stan_kupac_id'];
                              $stanKupacNaziv = trim(($parking['stan_kupac_ime'] ?? '') . ' ' . ($parking['stan_kupac_prezime'] ?? ''));
                              if ($stanKupacNaziv === '' && $stanKupacId > 0) {
                                $stanKupacNaziv = 'Kupac #' . $stanKupacId;
                              }
                              if ($stanKupacNaziv !== '') {
                                $kupacId = $stanKupacId;
                                $kupacNaziv = $stanKupacNaziv;
                              }
                            }
                            $tipProdaje = $parking['tip_prodaje'] ?? null;
                            $badgeClass = 'badge badge-success';
                            $linkStyle = 'text-decoration: none; color: #fff !important;';

                            if ($tipProdaje === 'lokacija') {
                              $badgeClass = 'badge badge-danger';
                            } elseif ($tipProdaje === 'kompenzacija') {
                              $badgeClass = 'badge badge-warning';
                              $linkStyle = 'text-decoration: none; color: #212529 !important;';
                            }
                            ?>
                            <?php if ($kupacNaziv !== ''): ?>
                              <?php if ($kupacId > 0): ?>
                                <a href="kupci_detaljno.php?id=<?= $kupacId ?>" class="<?= $badgeClass ?>" style="<?= $linkStyle ?>"><?= htmlspecialchars($kupacNaziv) ?></a>
                              <?php else: ?>
                                <span class="<?= $badgeClass ?>" style="<?= $linkStyle ?>"><?= htmlspecialchars($kupacNaziv) ?></span>
                              <?php endif; ?>
                            <?php else: ?>
                              <span class="<?= $badgeClass ?>" style="<?= $linkStyle ?>">Prodato</span>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="badge badge-light">Na prodaju</span>
                          <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($parking['napomena'] ?? '') ?></td>
                        <td>
                          <a href="izmena_parking_mesta.php?id=<?= $parking['id'] ?>" class="btn btn-primary btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                            <i class="fas fa-edit"></i> Izmeni
                          </a>
                          <?php if ($parkingProdat): ?>
                            <form method="POST" style="display: inline-block;" class="ml-2" onsubmit="return confirm('Da li ste sigurni da želite da raskinete prodaju ovog parking mesta?')">
                              <input type="hidden" name="akcija" value="raskini_prodaju">
                              <input type="hidden" name="parking_id" value="<?= $parking['id'] ?>">
                              <input type="hidden" name="selektovan_objekat_id" value="<?= $selektovanId ?>">
                              <button type="submit" class="btn btn-warning btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                                <i class="fas fa-undo"></i> Raskini prodaju
                              </button>
                            </form>
                          <?php endif; ?>
                          <a href="obrisi_parking_mesto.php?id=<?= $parking['id'] ?>" class="btn btn-danger btn-md ml-2" style="min-width: 100px; padding: 8px 16px; color: white !important;" onclick="return confirm('Da li ste sigurni da želite da obrišete ovo parking mesto?')">
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
