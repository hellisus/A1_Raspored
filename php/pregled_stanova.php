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

  <title>GP RAZ - Pregled stanova</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous" />
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />
  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>
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
        <div class="row">
          <div class="col-12">
            <h2><i class="fas fa-house-user"></i> Pregled stanova</h2>

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
                // Učitaj stanove za objekat sa JOIN za kanal prodaje i parking mesta
                $stanoviCrud = new CRUD($_SESSION['godina']);
                $stanoviCrud->table = "stanovi";
                $stanovi = $stanoviCrud->select(['*'], [], "SELECT s.*, kp.naziv as kanal_prodaje_naziv, pm.naziv as parking_mesto_naziv, k.ime as kupac_ime, k.prezime as kupac_prezime,
                  CASE 
                    WHEN s.kvadratura > 0 THEN ROUND(s.ukupna_cena / s.kvadratura, 2)
                    ELSE 0
                  END as realna_cena_po_m2
                  FROM stanovi s 
                  LEFT JOIN kanal_prodaje kp ON s.kanal_prodaje_id = kp.id 
                  LEFT JOIN parking_mesta pm ON s.id = pm.stan_id 
                  LEFT JOIN kupci k ON s.kupac_id = k.id
                  WHERE s.objekat_id = {$selektovanId} 
                  ORDER BY CAST(SUBSTRING(s.naziv, 2) AS UNSIGNED) ASC, s.naziv ASC");
                ?>

                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th scope="col">Naziv</th>
                        <th scope="col">Kvadratura (m²)</th>
                        <th scope="col">Cena po m² sa PDV (€)</th>
                        <th scope="col">Cena po m² bez PDV (€)</th>
                        <th scope="col">PDV (€)</th>
                        <th scope="col">Rabat (€)</th>
                        <th scope="col">Ukupna cena (€)</th>
                        <th scope="col">Sa parking mestom</th>
                        <th scope="col">Kompenzacija</th>
                        <th scope="col">Lokacija</th>
                        <th scope="col">Kanal prodaje</th>
                        <th scope="col">Status</th>
                        <th scope="col">Kupac</th>
                        <th scope="col">Datum prodaje</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($stanovi) === 0): ?>
                        <tr>
                          <td colspan="14" class="text-center text-muted">Nema unetih stanova za ovaj objekat.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($stanovi as $stan): ?>
                          <tr data-stan-id="<?= (int)$stan['id'] ?>">
                            <td><?= isset($stan['naziv']) ? htmlspecialchars($stan['naziv']) : ('Stan #' . (int)$stan['id']) ?></td>
                            <td><?= number_format((float)$stan['kvadratura'], 2, ',', '.') ?></td>
                            <td><?= number_format((float)($stan['realna_cena_po_m2'] ?? 0), 2, ',', '.') ?></td>
                            <td><?= number_format((float)$stan['cena_po_m2'], 2, ',', '.') ?></td>
                            <td><?= number_format((float)$stan['pdv'], 2, ',', '.') ?></td>
                            <td><?= number_format((float)$stan['rabat'], 2, ',', '.') ?></td>
                            <td><strong><?= number_format((float)$stan['ukupna_cena'], 2, ',', '.') ?></strong></td>
                            <td><?= isset($stan['parking_mesto_naziv']) && $stan['parking_mesto_naziv'] !== null ? '<span class="badge badge-success">' . htmlspecialchars($stan['parking_mesto_naziv']) . '</span>' : '<span class="badge badge-secondary">Ne</span>' ?></td>
                            <td><?= ((int)$stan['kompenzacija']) === 1 ? '<span class="badge badge-info">Da</span>' : '<span class="badge badge-secondary">Ne</span>' ?></td>
                            <td><?= ((int)$stan['lokacija']) === 1 ? '<span class="badge badge-warning">Da</span>' : '<span class="badge badge-secondary">Ne</span>' ?></td>
                            <td><?= isset($stan['kanal_prodaje_naziv']) && $stan['kanal_prodaje_naziv'] !== null ? '<span class="badge badge-primary">' . htmlspecialchars($stan['kanal_prodaje_naziv']) . '</span>' : '<span class="badge badge-secondary">-</span>' ?></td>
                            <td>
                              <?php if ((int)$stan['prodat'] === 1): ?>
                                <?php if (!empty($stan['lokacija'])): ?>
                                  <span class="badge badge-info">Lokacija</span>
                                <?php elseif (!empty($stan['kompenzacija'])): ?>
                                  <span class="badge badge-warning">Kompenzacija</span>
                                <?php else: ?>
                                  <span class="badge badge-success">Prodat</span>
                                <?php endif; ?>
                              <?php else: ?>
                                <span class="badge badge-light">Na prodaju</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ((int)$stan['prodat'] === 1 && !empty($stan['kupac_ime'])): ?>
                                <?= htmlspecialchars(trim($stan['kupac_ime'] . ' ' . ($stan['kupac_prezime'] ?? ''))) ?>
                              <?php else: ?>
                                -
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ((int)$stan['prodat'] === 1 && !empty($stan['datum_prodaje'])): ?>
                                <?= formatirajDatum($stan['datum_prodaje']) ?>
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

  <!-- jQuery CDN - Full version -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <!-- Popper.JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
  <!-- Bootstrap JS -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
  
  <script>
    // Funkcija za preusmeravanje na izmenu stana
    function redirectToEdit(stanId) {
      window.location.href = 'izmena_stana.php?id=' + stanId;
    }
    
    // Dodaj event listener za klik na red
    $(document).ready(function() {
      $('tbody tr').click(function() {
        // Uzmi ID stana iz data atributa
        var stanId = $(this).data('stan-id');
        if (stanId) {
          redirectToEdit(stanId);
        }
      });
      
      // Dodaj hover efekat
      $('tbody tr').hover(
        function() {
          $(this).addClass('table-row-hover');
        },
        function() {
          $(this).removeClass('table-row-hover');
        }
      );
    });
  </script>
  
  <style>
    .table-row-hover {
      background-color: #f8f9fa !important;
      cursor: pointer;
    }
    
    tbody tr {
      cursor: pointer;
    }
    
    tbody tr:hover {
      background-color: #e9ecef !important;
    }
  </style>
</body>

</html>


