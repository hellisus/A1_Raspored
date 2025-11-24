<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada prodaje (kreiranje dokumenta prodaje)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akcija']) && $_POST['akcija'] === 'proda') {
  try {
    $objekatId = (int)($_POST['objekat_id'] ?? 0);
    $tip = $_POST['tip'] ?? '';
    $entitetId = (int)($_POST['entitet_id'] ?? 0);
    $kupacId = (int)($_POST['kupac_id'] ?? 0);
    $datumProdaje = $_POST['datum_prodaje'] ?? '';

    if ($objekatId <= 0 || $entitetId <= 0 || $kupacId <= 0 || !in_array($tip, ['stan','lokal','garaza','parking']) || empty($datumProdaje)) {
      $_SESSION['poruka'] = 'Greška: Neispravni podaci prodaje.';
    } else {
      // Proveri da li već postoji prodaja za ovaj entitet
      $checkCrud = new CRUD($_SESSION['godina']);
      $tableMap = [
        'stan' => 'stanovi',
        'lokal' => 'lokali', 
        'garaza' => 'garaze',
        'parking' => 'parking_mesta'
      ];
      $checkCrud->table = $tableMap[$tip];
      $vec = $checkCrud->select(['id'], ['id' => $entitetId, 'prodat' => 1]);
      if (!empty($vec)) {
        $_SESSION['poruka'] = 'Greška: Ovaj artikal je već označen kao prodat.';
      } else {
        // Ažuriraj kolonu prodat i dodaj podatke o kupcu i datumu u odgovarajućoj tabeli
        $updateCrud = new CRUD($_SESSION['godina']);
        // Mapiranje tipova na ispravna imena tabela
        $tableMap = [
          'stan' => 'stanovi',
          'lokal' => 'lokali', 
          'garaza' => 'garaze',
          'parking' => 'parking_mesta'
        ];
        $updateCrud->table = $tableMap[$tip];
        // Pripremi podatke za ažuriranje
        $updateData = [
          'prodat' => 1,
          'kupac_id' => $kupacId,
          'datum_prodaje' => $datumProdaje
        ];
        
        // Dodaj datum_predugovora samo ako tabela ima tu kolonu
        if ($tip === 'stan') {
          $updateData['datum_predugovora'] = $_POST['datum_predugovora'] ?? null;
        }
        
        $updateCrud->update($updateData, ['id' => $entitetId]);
        
         // Proveri da li je ažuriranje stvarno uspešno
         $proveri = $updateCrud->select(['prodat', 'kupac_id', 'datum_prodaje'], ['id' => $entitetId]);
        if (!empty($proveri) && $proveri[0]['prodat'] == 1 && $proveri[0]['kupac_id'] == $kupacId) {
          // Ako je prodat stan, proveri da li je vezan sa parking mestom i označi ga kao prodato
          if ($tip === 'stan') {
            // Prvo proveri da li stan ima označeno prodaje_sa_parking_mestom
            $stanCrud = new CRUD($_SESSION['godina']);
            $stanCrud->table = 'stanovi';
            $stan = $stanCrud->select(['prodaje_sa_parking_mestom', 'rabat'], ['id' => $entitetId]);
            
            if (!empty($stan) && $stan[0]['prodaje_sa_parking_mestom'] == 1) {
              $parkingCrud = new CRUD($_SESSION['godina']);
              $parkingCrud->table = 'parking_mesta';
              $parkingMesto = $parkingCrud->select(['id'], ['stan_id' => $entitetId]);
              if (!empty($parkingMesto)) {
                $parkingCrud->update([
                  'prodat' => 1,
                  'kupac_id' => $kupacId,
                  'datum_prodaje' => $datumProdaje
                ], ['stan_id' => $entitetId]);
              }
            }

            $rabatIznos = isset($stan[0]['rabat']) ? (float)$stan[0]['rabat'] : 0;
            if ($rabatIznos > 0) {
              $rabatIznos = round($rabatIznos, 2);
              $uplataCrud = new CRUD($_SESSION['godina']);
              $uplataCrud->table = 'uplata';
              $srednjiKursRabat = isset($_SESSION['euro']) ? floatval($_SESSION['euro']) : null;
              $rabatVrednostRSD = ($srednjiKursRabat && $srednjiKursRabat > 0) ? round($rabatIznos * $srednjiKursRabat, 2) : null;

              $postojiRabatUplata = $uplataCrud->select(
                ['id'],
                [
                  'id_kupca' => $kupacId,
                  'tip_jedinice' => 'stan',
                  'id_jedinice' => $entitetId,
                  'kes' => 1,
                  'iznos_uplate' => $rabatIznos
                ]
              );

              if (empty($postojiRabatUplata)) {
                $uplataCrud->insert([
                  'id_kupca' => $kupacId,
                  'datum_uplate' => $datumProdaje,
                  'trenutna_vrednost_eura' => $_SESSION['euro'] ?? 0,
                  'srednji_kurs' => $srednjiKursRabat,
                  'iznos_uplate' => $rabatIznos,
                  'tip_jedinice' => 'stan',
                  'id_jedinice' => $entitetId,
                  'kes' => 1,
                  'vrednost_u_dinarima' => $rabatVrednostRSD
                ]);
              }
            }
          }
          $_SESSION['poruka'] = 'Prodaja je sačuvana.';
         } else {
           $_SESSION['poruka'] = 'Greška pri čuvanju prodaje.';
         }
      }
    }
  } catch (Exception $e) {
    $_SESSION['poruka'] = 'Greška: ' . $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Prodaja po objektu</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous" />
  <link rel="stylesheet" href="../src/css/style.css" />
  
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  
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
            <h2><i class="fas fa-file-invoice-dollar"></i> Prodaja po objektu</h2>

            <?php if (isset($_SESSION['poruka'])): ?>
              <div class="alert alert-<?= strpos($_SESSION['poruka'], 'Greška') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['poruka'] ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <?php unset($_SESSION['poruka']); ?>
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
              // Kupci
              $kupciCrud = new CRUD($_SESSION['godina']);
              $kupciCrud->table = 'kupci';
              $kupci = $kupciCrud->select(['*'], [], 'SELECT * FROM kupci ORDER BY ime ASC, prezime ASC');

              // Helper za select kupca sa Select2
              $renderKupciSelect = function($name) use ($kupci) {
                ob_start();
                echo '<select class="form-control select2-kupci" name="' . $name . '" required style="width: 250px;">'; 
                echo '<option value="">Izaberite kupca</option>';
                foreach ($kupci as $k) {
                  $label = trim(($k['ime'] ?? '') . ' ' . ($k['prezime'] ?? ''));
                  echo '<option value="' . (int)$k['id'] . '">' . htmlspecialchars($label) . '</option>';
                }
                echo '</select>';
                return ob_get_clean();
              };

              // Učitaj kupce za prikaz
              $kupciCrud = new CRUD($_SESSION['godina']);
              $kupciCrud->table = 'kupci';
              $kupci = $kupciCrud->select(['*'], [], 'SELECT * FROM kupci ORDER BY ime ASC');
              $kupciMapa = [];
              foreach ($kupci as $k) {
                $kupciMapa[$k['id']] = $k['ime'] . ' ' . ($k['prezime'] ?? '');
              }
              
              // Podatci o prodaji se sada čitaju direktno iz tabela stanovi, lokali, garaze, parking_mesta

              // Stanovi
              $sCrud = new CRUD($_SESSION['godina']);
              $sCrud->table = 'stanovi';
              $stanovi = $sCrud->select(['*'], [], 'SELECT * FROM stanovi WHERE objekat_id = ' . $selektovanId . ' ORDER BY id ASC');
              ?>

              <h3 class="mt-4">Stanovi</h3>
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Naziv</th>
                      <th>Kvadratura</th>
                      <th>Cena po m²</th>
                      <th>Ukupna cena</th>
                      <th>Prodaja</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($stanovi) === 0): ?>
                      <tr><td colspan="5" class="text-center text-muted">Nema unetih stanova.</td></tr>
                    <?php else: foreach ($stanovi as $s): $key = 'stan:' . (int)$s['id']; ?>
                      <tr>
                        <td><?= isset($s['naziv']) && $s['naziv'] !== '' ? htmlspecialchars($s['naziv']) : ('Stan #' . (int)$s['id']) ?></td>
                        <td><?= number_format((float)$s['kvadratura'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$s['cena_po_m2'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$s['ukupna_cena'], 2, ',', '.') ?></td>
                        <td>
                            <?php if ((int)$s['prodat'] === 1): ?>
                              <?php if (!empty($s['lokacija'])): ?>
                                <span class="badge badge-info">Lokacija</span>
                              <?php elseif (!empty($s['kompenzacija'])): ?>
                                <span class="badge badge-warning">Kompenzacija</span>
                              <?php else: ?>
                                <?php if (isset($kupciMapa[$s['kupac_id']])): ?>
                                  <a href="kupci_detaljno.php?id=<?= $s['kupac_id'] ?>" class="badge badge-success text-white" style="text-decoration: none;">
                                    Prodat: <?= htmlspecialchars($kupciMapa[$s['kupac_id']]) ?><br><small><?= formatirajDatum($s['datum_prodaje'], 'Nepoznat datum') ?></small>
                                  </a>
                                <?php else: ?>
                                  <span class="badge badge-success">
                                    Prodat: Nepoznat kupac<br><small><?= formatirajDatum($s['datum_prodaje'], 'Nepoznat datum') ?></small>
                                  </span>
                                <?php endif; ?>
                              <?php endif; ?>
                          <?php else: ?>
                            <form method="post" class="form-inline">
                              <input type="hidden" name="akcija" value="proda" />
                              <input type="hidden" name="objekat_id" value="<?= $selektovanId ?>" />
                              <input type="hidden" name="tip" value="stan" />
                              <input type="hidden" name="entitet_id" value="<?= (int)$s['id'] ?>" />
                              <div class="form-group mr-2 mb-2"><?= $renderKupciSelect('kupac_id') ?></div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum ugovora</label>
                                <input type="date" name="datum_prodaje" class="form-control" required style="width: 150px;" title="Datum ugovora" />
                                <small class="form-text text-muted">Datum ugovora</small>
                              </div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum predugovora</label>
                                <input type="date" name="datum_predugovora" class="form-control" style="width: 150px;" title="Datum predugovora (opciono)" />
                                <small class="form-text text-muted">Datum predugovora</small>
                              </div>
                              <button type="submit" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i> Označi kao prodat</button>
                            </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>

              <?php // Lokali
              $lCrud = new CRUD($_SESSION['godina']);
              $lCrud->table = 'lokali';
              $lokali = $lCrud->select(['*'], [], 'SELECT * FROM lokali WHERE objekat_id = ' . $selektovanId . ' ORDER BY id ASC');
              ?>

              <h3 class="mt-4">Lokali</h3>
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Naziv</th>
                      <th>Kvadratura</th>
                      <th>Cena po m²</th>
                      <th>Prodaja</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($lokali) === 0): ?>
                      <tr><td colspan="4" class="text-center text-muted">Nema unetih lokala.</td></tr>
                    <?php else: foreach ($lokali as $l): $key = 'lokal:' . (int)$l['id']; ?>
                      <tr>
                        <td><?= isset($l['naziv']) && $l['naziv'] !== '' ? htmlspecialchars($l['naziv']) : ('Lokal #' . (int)$l['id']) ?></td>
                        <td><?= number_format((float)$l['kvadratura'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$l['cena_po_m2'], 2, ',', '.') ?></td>
                        <td>
                          <?php if ((int)$l['prodat'] === 1): ?>
                            <?php if (isset($kupciMapa[$l['kupac_id']])): ?>
                              <a href="kupci_detaljno.php?id=<?= $l['kupac_id'] ?>" class="badge badge-success text-white" style="text-decoration: none;">
                                Prodat: <?= htmlspecialchars($kupciMapa[$l['kupac_id']]) ?><br><small><?= formatirajDatum($l['datum_prodaje'], 'Nepoznat datum') ?></small>
                              </a>
                            <?php else: ?>
                              <span class="badge badge-success">
                                Prodat: Nepoznat kupac<br><small><?= formatirajDatum($l['datum_prodaje'], 'Nepoznat datum') ?></small>
                              </span>
                            <?php endif; ?>
                          <?php else: ?>
                            <form method="post" class="form-inline">
                              <input type="hidden" name="akcija" value="proda" />
                              <input type="hidden" name="objekat_id" value="<?= $selektovanId ?>" />
                              <input type="hidden" name="tip" value="lokal" />
                              <input type="hidden" name="entitet_id" value="<?= (int)$l['id'] ?>" />
                              <div class="form-group mr-2 mb-2"><?= $renderKupciSelect('kupac_id') ?></div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum ugovora</label>
                                <input type="date" name="datum_prodaje" class="form-control" required style="width: 150px;" title="Datum ugovora" />
                                <small class="form-text text-muted">Datum ugovora</small>
                              </div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum predugovora</label>
                                <input type="date" name="datum_predugovora" class="form-control" style="width: 150px;" title="Datum predugovora (opciono)" />
                                <small class="form-text text-muted">Datum predugovora</small>
                              </div>
                              <button type="submit" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i> Označi kao prodat</button>
                            </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>

              <?php // Garaže
              $gCrud = new CRUD($_SESSION['godina']);
              $gCrud->table = 'garaze';
              $garaze = $gCrud->select(['*'], [], 'SELECT * FROM garaze WHERE objekat_id = ' . $selektovanId . ' ORDER BY id ASC');
              ?>

              <h3 class="mt-4">Garaže</h3>
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Naziv</th>
                      <th>Kvadratura</th>
                      <th>Cena po m²</th>
                      <th>Prodaja</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($garaze) === 0): ?>
                      <tr><td colspan="4" class="text-center text-muted">Nema unetih garaža.</td></tr>
                    <?php else: foreach ($garaze as $g): $key = 'garaza:' . (int)$g['id']; ?>
                      <?php
                      $garazaKvadratura = isset($g['kvadratura']) && $g['kvadratura'] !== '' ? (float)$g['kvadratura'] : null;
                      $garazaCenaKvadrata = isset($g['cena_kvadrata']) && $g['cena_kvadrata'] !== '' ? (float)$g['cena_kvadrata'] : null;
                      if ($garazaCenaKvadrata === null && $garazaKvadratura !== null && isset($g['cena_sa_pdv']) && $g['cena_sa_pdv'] !== null) {
                        $garazaCenaKvadrata = (float)$g['cena_sa_pdv'] / ($garazaKvadratura > 0 ? $garazaKvadratura : 1);
                      }
                      ?>
                      <tr>
                        <td><?= isset($g['naziv']) && $g['naziv'] !== '' ? htmlspecialchars($g['naziv']) : ('Garaža #' . (int)$g['id']) ?></td>
                        <td>
                          <?php if ($garazaKvadratura !== null): ?>
                            <?= number_format($garazaKvadratura, 2, ',', '.') ?>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($garazaCenaKvadrata !== null): ?>
                            <?= number_format($garazaCenaKvadrata, 2, ',', '.') ?>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ((int)$g['prodat'] === 1): ?>
                            <?php if (isset($kupciMapa[$g['kupac_id']])): ?>
                              <a href="kupci_detaljno.php?id=<?= $g['kupac_id'] ?>" class="badge badge-success text-white" style="text-decoration: none;">
                                Prodata: <?= htmlspecialchars($kupciMapa[$g['kupac_id']]) ?><br><small><?= formatirajDatum($g['datum_prodaje'], 'Nepoznat datum') ?></small>
                              </a>
                            <?php else: ?>
                              <span class="badge badge-success">
                                Prodata: Nepoznat kupac<br><small><?= formatirajDatum($g['datum_prodaje'], 'Nepoznat datum') ?></small>
                              </span>
                            <?php endif; ?>
                          <?php else: ?>
                            <form method="post" class="form-inline">
                              <input type="hidden" name="akcija" value="proda" />
                              <input type="hidden" name="objekat_id" value="<?= $selektovanId ?>" />
                              <input type="hidden" name="tip" value="garaza" />
                              <input type="hidden" name="entitet_id" value="<?= (int)$g['id'] ?>" />
                              <div class="form-group mr-2 mb-2"><?= $renderKupciSelect('kupac_id') ?></div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum ugovora</label>
                                <input type="date" name="datum_prodaje" class="form-control" required style="width: 150px;" title="Datum ugovora" />
                                <small class="form-text text-muted">Datum ugovora</small>
                              </div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum predugovora</label>
                                <input type="date" name="datum_predugovora" class="form-control" style="width: 150px;" title="Datum predugovora (opciono)" />
                                <small class="form-text text-muted">Datum predugovora</small>
                              </div>
                              <button type="submit" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i> Označi kao prodata</button>
                            </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>

              <?php // Parking mesta - ne prikazuj ona koja su vezana za stanove koji se prodaju sa parking mestom
              $pCrud = new CRUD($_SESSION['godina']);
              $pCrud->table = 'parking_mesta';
              $parking = $pCrud->select(['*'], [], 'SELECT p.* FROM parking_mesta p 
                LEFT JOIN stanovi s ON p.stan_id = s.id 
                WHERE p.objekat_id = ' . $selektovanId . ' 
                AND (p.stan_id IS NULL OR s.prodaje_sa_parking_mestom = 0 OR s.prodaje_sa_parking_mestom IS NULL)
                ORDER BY p.id ASC');
              ?>

              <h3 class="mt-4">Parking mesta</h3>
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Naziv</th>
                      <th>Cena</th>
                      <th>Napomena</th>
                      <th>Prodaja</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($parking) === 0): ?>
                      <tr><td colspan="4" class="text-center text-muted">Nema unetih parking mesta.</td></tr>
                    <?php else: foreach ($parking as $pm): $key = 'parking:' . (int)$pm['id']; ?>
                      <tr>
                        <td><?= isset($pm['naziv']) && $pm['naziv'] !== '' ? htmlspecialchars($pm['naziv']) : ('PM #' . (int)$pm['id']) ?></td>
                        <td><?= number_format((float)$pm['cena'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)($pm['napomena'] ?? '')) ?></td>
                        <td>
                          <?php if ((int)$pm['prodat'] === 1): ?>
                            <?php if (isset($kupciMapa[$pm['kupac_id']])): ?>
                              <a href="kupci_detaljno.php?id=<?= $pm['kupac_id'] ?>" class="badge badge-success text-white" style="text-decoration: none;">
                                Prodato: <?= htmlspecialchars($kupciMapa[$pm['kupac_id']]) ?><br><small><?= formatirajDatum($pm['datum_prodaje'], 'Nepoznat datum') ?></small>
                              </a>
                            <?php else: ?>
                              <span class="badge badge-success">
                                Prodato: Nepoznat kupac<br><small><?= formatirajDatum($pm['datum_prodaje'], 'Nepoznat datum') ?></small>
                              </span>
                            <?php endif; ?>
                          <?php else: ?>
                            <form method="post" class="form-inline">
                              <input type="hidden" name="akcija" value="proda" />
                              <input type="hidden" name="objekat_id" value="<?= $selektovanId ?>" />
                              <input type="hidden" name="tip" value="parking" />
                              <input type="hidden" name="entitet_id" value="<?= (int)$pm['id'] ?>" />
                              <div class="form-group mr-2 mb-2"><?= $renderKupciSelect('kupac_id') ?></div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum ugovora</label>
                                <input type="date" name="datum_prodaje" class="form-control" required style="width: 150px;" title="Datum ugovora" />
                                <small class="form-text text-muted">Datum ugovora</small>
                              </div>
                              <div class="form-group mr-2 mb-2">
                                <label class="sr-only">Datum predugovora</label>
                                <input type="date" name="datum_predugovora" class="form-control" style="width: 150px;" title="Datum predugovora (opciono)" />
                                <small class="form-text text-muted">Datum predugovora</small>
                              </div>
                              <button type="submit" class="btn btn-sm btn-success mb-2"><i class="fas fa-check"></i> Označi kao prodato</button>
                            </form>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
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
  
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Inicijalizacija Select2 za kupce
      $('.select2-kupci').select2({
        theme: 'bootstrap-5',
        placeholder: 'Izaberite kupca',
        allowClear: true,
        width: '250px',
        language: {
          noResults: function() {
            return "Nema rezultata";
          },
          searching: function() {
            return "Pretražujem...";
          }
        }
      });
    });
  </script>
</body>

</html>


