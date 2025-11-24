<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

$crud = new CRUD($_SESSION['godina']);
$crud->table = "kupci";

$objekatCrud = new CRUD($_SESSION['godina']);
$objekatCrud->table = 'objekti';
$objekti = $objekatCrud->select(['*'], [], 'SELECT * FROM objekti ORDER BY naziv ASC');

$selektovanObjekatId = isset($_GET['objekat_id']) ? (int)$_GET['objekat_id'] : 0;

$kupci = [];
if ($selektovanObjekatId > 0) {
  $objectId = $selektovanObjekatId;
  $kupciQuery = "SELECT k.*, tk.naziv as tip_kupca_naziv
    FROM kupci k
    LEFT JOIN tip_kupca tk ON k.tip_kupca_id = tk.id_tipa_kupca
    WHERE (
      EXISTS (SELECT 1 FROM stanovi s WHERE s.kupac_id = k.id AND s.prodat = 1 AND s.objekat_id = {$objectId})
      OR EXISTS (SELECT 1 FROM lokali l WHERE l.kupac_id = k.id AND l.prodat = 1 AND l.objekat_id = {$objectId})
      OR EXISTS (SELECT 1 FROM garaze g WHERE g.kupac_id = k.id AND g.prodat = 1 AND g.objekat_id = {$objectId})
      OR EXISTS (SELECT 1 FROM parking_mesta pm WHERE pm.kupac_id = k.id AND pm.prodat = 1 AND pm.objekat_id = {$objectId})
    )
    ORDER BY k.ime ASC, k.prezime ASC";

  $kupci = $crud->select(['*'], [], $kupciQuery);
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Pregled kupaca</title>

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
            <h2><i class="fas fa-users"></i> Pregled kupaca</h2>

            <?php if (count($objekti) > 0): ?>
              <form method="get" class="mb-4">
                <div class="form-row">
                  <div class="col-md-6 col-sm-12 mb-2">
                    <label for="objekat_id"><i class="far fa-building"></i> Izaberite objekat</label>
                    <select class="form-control" id="objekat_id" name="objekat_id" required onchange="this.form.submit()">
                      <option value="">Izaberite objekat</option>
                      <?php foreach ($objekti as $o): ?>
                        <option value="<?= (int)$o['id'] ?>" <?= $selektovanObjekatId === (int)$o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['naziv']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </form>
            <?php else: ?>
              <div class="alert alert-warning">Nema unetih objekata.</div>
            <?php endif; ?>

            <?php if ($selektovanObjekatId === 0 && count($objekti) > 0): ?>
              <div class="alert alert-info">Molimo izaberite objekat da biste videli kupce.</div>
            <?php endif; ?>

            <?php if ($selektovanObjekatId > 0): ?>
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th scope="col">Ime</th>
                      <th scope="col">Prezime</th>
                      <th scope="col">Broj telefona</th>
                      <th scope="col">Email</th>
                      <th scope="col">Tip kupca</th>
                      <th scope="col">Napomena</th>
                      <th scope="col">Kupljeno</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($kupci) === 0): ?>
                      <tr>
                        <td colspan="7" class="text-center text-muted">Nema unetih kupaca.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($kupci as $kupac): ?>
                        <?php
                          // Učitaj sve prodaje za kupca sa nazivima artikala i objekta
                          $kupacId = (int)$kupac['id'];

                          // Dohvati stanove koje je kupio
                          $stanCrud = new CRUD($_SESSION['godina']);
                          $stanCrud->table = 'stanovi';
                          $stanovi = $stanCrud->select(['*'], [],
                            "SELECT s.*, o.naziv AS objekat_naziv,
                             COALESCE(s.naziv, CONCAT('Stan #', s.id)) AS artikal_naziv,
                             'stan' AS tip
                             FROM stanovi s
                             LEFT JOIN objekti o ON o.id = s.objekat_id
                             WHERE s.kupac_id = {$kupacId} AND s.prodat = 1 AND s.objekat_id = {$selektovanObjekatId}
                             ORDER BY s.id ASC"
                          );

                          // Dohvati lokale koje je kupio
                          $lokalCrud = new CRUD($_SESSION['godina']);
                          $lokalCrud->table = 'lokali';
                          $lokali = $lokalCrud->select(['*'], [],
                            "SELECT l.*, o.naziv AS objekat_naziv,
                             COALESCE(l.naziv, CONCAT('Lokal #', l.id)) AS artikal_naziv,
                             'lokal' AS tip
                             FROM lokali l
                             LEFT JOIN objekti o ON o.id = l.objekat_id
                             WHERE l.kupac_id = {$kupacId} AND l.prodat = 1 AND l.objekat_id = {$selektovanObjekatId}
                             ORDER BY l.id ASC"
                          );

                          // Dohvati garaže koje je kupio
                          $garazaCrud = new CRUD($_SESSION['godina']);
                          $garazaCrud->table = 'garaze';
                          $garaze = $garazaCrud->select(['*'], [],
                            "SELECT g.*, o.naziv AS objekat_naziv,
                             COALESCE(g.naziv, CONCAT('Garaža #', g.id)) AS artikal_naziv,
                             'garaza' AS tip
                             FROM garaze g
                             LEFT JOIN objekti o ON o.id = g.objekat_id
                             WHERE g.kupac_id = {$kupacId} AND g.prodat = 1 AND g.objekat_id = {$selektovanObjekatId}
                             ORDER BY g.id ASC"
                          );

                          // Dohvati parking mesta koje je kupio
                          $parkingCrud = new CRUD($_SESSION['godina']);
                          $parkingCrud->table = 'parking_mesta';
                          $parking_mesta = $parkingCrud->select(['*'], [],
                            "SELECT pm.*, o.naziv AS objekat_naziv,
                             COALESCE(pm.naziv, CONCAT('PM #', pm.id)) AS artikal_naziv,
                             'parking' AS tip
                             FROM parking_mesta pm
                             LEFT JOIN objekti o ON o.id = pm.objekat_id
                             WHERE pm.kupac_id = {$kupacId} AND pm.prodat = 1 AND pm.objekat_id = {$selektovanObjekatId}
                             ORDER BY pm.id ASC"
                          );

                          // Kombinuj sve prodaje
                          $prodaje = array_merge($stanovi, $lokali, $garaze, $parking_mesta);

                          // Pripremi HTML listu kupljenih
                          $kupljenoHtml = '';
                          if (count($prodaje) > 0) {
                            $byTip = ['stan' => [], 'lokal' => [], 'garaza' => [], 'parking' => []];
                            foreach ($prodaje as $pr) {
                              $label = htmlspecialchars(($pr['artikal_naziv'] ?? '') . ' (' . ($pr['objekat_naziv'] ?? 'Objekat') . ')');
                              $byTip[$pr['tip']][] = $label;
                            }
                            $parts = [];
                            foreach ($byTip as $tip => $items) {
                              if (!empty($items)) {
                                $nas = ucfirst($tip);
                                if ($tip === 'garaza') $nas = 'Garaže';
                                if ($tip === 'parking') $nas = 'Parking mesta';
                                if ($tip === 'lokal') $nas = 'Lokali';
                                if ($tip === 'stan') $nas = 'Stanovi';
                                $parts[] = '<strong>' . $nas . ':</strong> ' . implode(', ', $items);
                              }
                            }
                            $kupljenoHtml = implode('<br>', $parts);
                          } else {
                            $kupljenoHtml = '<span class="text-muted">—</span>';
                          }
                        ?>
                        <tr class="clickable-row" data-href="kupci_detaljno.php?id=<?= $kupac['id'] ?>">
                          <td><?= htmlspecialchars((string)$kupac['ime']) ?></td>
                          <td><?= htmlspecialchars((string)($kupac['prezime'] ?? '')) ?></td>
                          <td><?= htmlspecialchars((string)$kupac['br_telefona']) ?></td>
                          <td><?= isset($kupac['email']) && $kupac['email'] ? '<a href="mailto:' . htmlspecialchars($kupac['email']) . '" class="text-primary" onclick="event.stopPropagation();"><i class="fas fa-envelope"></i> ' . htmlspecialchars($kupac['email']) . '</a>' : '<span class="text-muted">—</span>' ?></td>
                          <td>
                            <?php if (isset($kupac['tip_kupca_naziv']) && $kupac['tip_kupca_naziv']): ?>
                              <span class="badge badge-info"><?= htmlspecialchars($kupac['tip_kupca_naziv']) ?></span>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </td>
                          <td><?= htmlspecialchars((string)($kupac['napomena'] ?? '')) ?></td>
                          <td><?= $kupljenoHtml ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
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
  
  <script>
    // Omogućava klik na ceo red tabele
    $(document).ready(function() {
      $('.clickable-row').click(function() {
        window.location = $(this).data('href');
      });
    });
  </script>
</body>

</html>


