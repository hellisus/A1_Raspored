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

  <title>GP RAZ - Pregled lokala</title>

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
            <h2><i class="fas fa-store"></i> Pregled lokala po objektima</h2>

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
                // Učitaj lokale za objekat sa JOIN za kupca
                $lokaliCrud = new CRUD($_SESSION['godina']);
                $lokaliCrud->table = "lokali";
                $lokali = $lokaliCrud->select(['*'], [], "SELECT l.*, k.ime as kupac_ime, k.prezime as kupac_prezime
                  FROM lokali l 
                  LEFT JOIN kupci k ON l.kupac_id = k.id
                  WHERE l.objekat_id = {$selektovanId} 
                  ORDER BY l.id ASC");
                ?>

                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th scope="col">Naziv</th>
                        <th scope="col">Kvadratura (m²)</th>
                        <th scope="col">Cena po m² sa PDV (€)</th>
                        <th scope="col">PDV suma (€)</th>
                        <th scope="col">Rabat (€)</th>
                        <th scope="col">Ukupna cena (€)</th>
                        <th scope="col">Status</th>
                        <th scope="col">Kupac</th>
                        <th scope="col">Datum prodaje</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($lokali) === 0): ?>
                        <tr>
                          <td colspan="9" class="text-center text-muted">Nema unetih lokala za ovaj objekat.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($lokali as $lok): ?>
                          <tr>
                            <td><?= isset($lok['naziv']) ? htmlspecialchars($lok['naziv']) : ('Lokal #' . (int)$lok['id']) ?></td>
                            <td><?= number_format((float)$lok['kvadratura'], 2, ',', '.') ?></td>
                            <td><?= number_format((float)$lok['cena_po_m2'], 2, ',', '.') ?></td>
                            <td><?= number_format((float)$lok['pdv_suma'], 2, ',', '.') ?></td>
                            <td><?= number_format((float)$lok['rabat'], 2, ',', '.') ?></td>
                            <td><strong><?= number_format((float)$lok['ukupna_cena'], 2, ',', '.') ?></strong></td>
                            <td>
                              <?php if ((int)$lok['prodat'] === 1): ?>
                                <span class="badge badge-success">Prodat</span>
                              <?php else: ?>
                                <span class="badge badge-light">Na prodaju</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ((int)$lok['prodat'] === 1 && !empty($lok['kupac_ime'])): ?>
                                <?= htmlspecialchars(trim($lok['kupac_ime'] . ' ' . ($lok['kupac_prezime'] ?? ''))) ?>
                              <?php else: ?>
                                -
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ((int)$lok['prodat'] === 1 && !empty($lok['datum_prodaje'])): ?>
                                <?= formatirajDatum($lok['datum_prodaje']) ?>
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


