<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada raskidanja prodaje parking mesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija']) && $_POST['akcija'] === 'raskini_prodaju') {
  try {
    $parking_id = (int)($_POST['parking_id'] ?? 0);
    
    if ($parking_id <= 0) {
      $_SESSION['error_message'] = 'Greška: Neispravan ID parking mesta.';
    } else {
      $crud = new CRUD($_SESSION['godina']);
      $crud->table = "parking_mesta";
      
      // Proveri da li parking mesto postoji i da li je prodato
      $parking = $crud->select(['*'], ['id' => $parking_id, 'prodat' => 1]);
      
      if (empty($parking)) {
        $_SESSION['error_message'] = 'Greška: Parking mesto nije pronađeno ili nije prodato.';
      } else {
        // Proveri da li je parking mesto vezano za stan
        if (!empty($parking[0]['stan_id'])) {
          $_SESSION['error_message'] = 'Greška: Parking mesto vezano za stan se raskida preko liste stanova.';
        } else {
          // Ažuriraj parking mesto - vrati prodat na 0 i obriši podatke o kupcu
          $crud->update([
            'prodat' => 0,
            'kupac_id' => null,
            'datum_prodaje' => null
          ], ['id' => $parking_id]);
          
          $_SESSION['success_message'] = 'Prodaja parking mesta je uspešno raskinuta.';
        }
      }
    }
  } catch (Exception $e) {
    $_SESSION['error_message'] = 'Greška: ' . $e->getMessage();
  }
  
  // Redirect da se osveži stranica
  header('Location: ' . $_SERVER['PHP_SELF']);
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

  <title>GP RAZ - Pregled parking mesta</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous" />
  <link rel="stylesheet" href="../src/css/style.css" />
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>
</head>

<body>
  <div class="wrapper">
    <?php require_once 'sidebar.php' ?>
    <div id="content">
      <?php require_once 'topbar.php' ?>
      <div class="containter">
        <div class="row">
          <div class="col-12">
            <h2><i class="fas fa-parking"></i> Pregled parking mesta po objektima</h2>

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

            <?php
            $objCrud = new CRUD($_SESSION['godina']);
            $objCrud->table = 'objekti';
            $objekti = $objCrud->select(['*'], [], 'SELECT * FROM objekti ORDER BY naziv ASC');

            $selektovanId = isset($_GET['objekat_id']) ? (int)$_GET['objekat_id'] : 0;
            ?>

            <form method="get" class="mb-4">
              <div class="form-row">
                <div class="col-md-6 col-sm-12 mb-2">
                  <label for="objekat_id"><i class="far fa-building"></i> Izaberite objekat</label>
                  <select class="form-control" id="objekat_id" name="objekat_id" required onchange="this.form.submit()">
                    <option value="">Izaberite objekat</option>
                    <?php foreach ($objekti as $o): ?>
                      <option value="<?= (int)$o['id'] ?>" <?= $selektovanId === (int)$o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['naziv']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </form>

            <?php if ($selektovanId > 0): ?>
              <?php
              // Pronađi objekat
              $objekatCrud = new CRUD($_SESSION['godina']);
              $objekatCrud->table = "objekti";
              $objekat = $objekatCrud->select(['*'], ['id' => $selektovanId]);
              $objekatNaziv = !empty($objekat) ? $objekat[0]['naziv'] : 'Nepoznat objekat';
              ?>

              <div class="mb-4">
                <h3 class="mt-3 mb-3"><?= htmlspecialchars($objekatNaziv) ?></h3>
                
                <?php
                // Učitaj parking mesta za objekat sa JOIN za kupca i stan
                $parkingCrud = new CRUD($_SESSION['godina']);
                $parkingCrud->table = "parking_mesta";
                $parkingMesta = $parkingCrud->select(['*'], [], "SELECT p.*, k.ime as kupac_ime, k.prezime as kupac_prezime, s.naziv as stan_naziv
                  FROM parking_mesta p 
                  LEFT JOIN kupci k ON p.kupac_id = k.id
                  LEFT JOIN stanovi s ON p.stan_id = s.id
                  WHERE p.objekat_id = {$selektovanId} 
                  ORDER BY CAST(SUBSTRING(p.naziv, 2) AS UNSIGNED) ASC, p.naziv ASC");
                ?>

                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th scope="col">Naziv</th>
                        <th scope="col">Cena (€)</th>
                        <th scope="col">Povezano sa stanom</th>
                        <th scope="col">Napomena</th>
                        <th scope="col">Status</th>
                        <th scope="col">Kupac</th>
                        <th scope="col">Datum prodaje</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($parkingMesta) === 0): ?>
                        <tr>
                          <td colspan="7" class="text-center text-muted">Nema unetih parking mesta za ovaj objekat.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($parkingMesta as $pm): ?>
                          <tr>
                            <td><?= isset($pm['naziv']) ? htmlspecialchars($pm['naziv']) : ('PM #' . (int)$pm['id']) ?></td>
                            <td><strong><?= number_format((float)$pm['cena'], 2, ',', '.') ?></strong></td>
                            <td>
                              <?php if (!empty($pm['stan_id'])): ?>
                                <span class="badge badge-info"><?= htmlspecialchars($pm['stan_naziv'] ?? 'Stan #' . $pm['stan_id']) ?></span>
                              <?php else: ?>
                                <span class="badge badge-secondary">Ne</span>
                              <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($pm['napomena'] ?? '')) ?></td>
                            <td>
                              <?php if ((int)($pm['prodat'] ?? 0) === 1): ?>
                                <span class="badge badge-success">Prodat</span>
                              <?php else: ?>
                                <span class="badge badge-light">Na prodaju</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ((int)($pm['prodat'] ?? 0) === 1 && !empty($pm['kupac_ime'])): ?>
                                <?= htmlspecialchars(trim($pm['kupac_ime'] . ' ' . ($pm['kupac_prezime'] ?? ''))) ?>
                              <?php else: ?>
                                -
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ((int)($pm['prodat'] ?? 0) === 1 && !empty($pm['datum_prodaje'])): ?>
                                <?= formatirajDatum($pm['datum_prodaje']) ?>
                              <?php else: ?>
                                -
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
</body>

</html>


