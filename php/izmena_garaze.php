<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Učitaj garažu za izmenu
$garaza_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$garaza_id) {
  header("location:glavni.php");
  exit;
}

$crud = new CRUD($_SESSION['godina']);
$crud->table = "garaze";
$garaza = $crud->select(['*'], ['id' => $garaza_id]);

if (empty($garaza)) {
  $_SESSION['poruka'] = "Greška: Garaža nije pronađena.";
  header("location:glavni.php");
  exit;
}

$garaza = $garaza[0]; // Uzmi prvi (i jedini) rezultat
$tipProdaje = $garaza['tip_prodaje'] ?? null;

$kanali = [];
try {
  $kanalCrud = new CRUD($_SESSION['godina']);
  $kanalCrud->table = "kanal_prodaje";
  $kanali = $kanalCrud->select(['*'], [], "SELECT * FROM kanal_prodaje ORDER BY id ASC");
} catch (Exception $e) {
  $kanali = [];
}

$kupciLista = [];
try {
  $kupacCrud = new CRUD($_SESSION['godina']);
  $kupacCrud->table = "kupci";
  $kupciLista = $kupacCrud->select(['*'], [], "SELECT * FROM kupci ORDER BY ime ASC, prezime ASC");
} catch (Exception $e) {
  $kupciLista = [];
}

$garazaJeProdata = (int)($garaza['prodat'] ?? 0) === 1;

// Obrada forme za izmenu garaže
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['akcija']) && $_POST['akcija'] === 'raskini_prodaju') {
    try {
      $potvrdjeniId = isset($_POST['garaza_id']) ? (int)$_POST['garaza_id'] : 0;
      if ($potvrdjeniId !== $garaza_id) {
        throw new Exception('Neispravan zahtev za raskid prodaje.');
      }

      $crud->update([
        'prodat' => 0,
        'kupac_id' => null,
        'datum_prodaje' => null,
        'datum_predugovora' => null,
        'kanal_prodaje_id' => null,
        'tip_prodaje' => null,
      ], ['id' => $garaza_id]);

      $_SESSION['poruka'] = "Prodaja garaže je uspešno raskinuta. Sada možete izmeniti podatke.";
    } catch (Exception $e) {
      $_SESSION['poruka'] = "Greška: " . $e->getMessage();
    }

    $osvezena_garaza = $crud->select(['*'], ['id' => $garaza_id]);
    if (!empty($osvezena_garaza)) {
      $garaza = $osvezena_garaza[0];
      $tipProdaje = $garaza['tip_prodaje'] ?? null;
    }
    $garazaJeProdata = (int)($garaza['prodat'] ?? 0) === 1;
  } else {
    try {
      if ($garazaJeProdata) {
        $_SESSION['poruka'] = "Greška: Garaža je označena kao prodata. Najpre raskinite prodaju da biste menjali podatke.";
      } else {
        $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';
        $cena_sa_pdv_input = isset($_POST['cena_sa_pdv']) ? trim($_POST['cena_sa_pdv']) : '';
        $cena_sa_pdv = $cena_sa_pdv_input !== '' ? (float)$cena_sa_pdv_input : null;
        $isKompenzacija = isset($_POST['kompenzacija']);
        $isLokacija = isset($_POST['lokacija']);
        $kanal_prodaje_id = isset($_POST['kanal_prodaje_id']) && $_POST['kanal_prodaje_id'] !== '' ? (int)$_POST['kanal_prodaje_id'] : null;
        $kupacIdValue = isset($_POST['kupac_id']) && $_POST['kupac_id'] !== '' ? (int)$_POST['kupac_id'] : null;
        $datumProdaje = isset($_POST['datum_prodaje']) && $_POST['datum_prodaje'] !== '' ? $_POST['datum_prodaje'] : null;
        $datumPredugovora = isset($_POST['datum_predugovora']) && $_POST['datum_predugovora'] !== '' ? $_POST['datum_predugovora'] : null;

        if ($naziv === '' || $cena_sa_pdv === null || $cena_sa_pdv < 0) {
          $_SESSION['poruka'] = "Greška: Naziv i cena sa PDV su obavezni. Cena mora biti veća ili jednaka 0.";
        } elseif ($isKompenzacija && $isLokacija) {
          $_SESSION['poruka'] = "Greška: Garaža ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.";
        } elseif (($isKompenzacija || $isLokacija) && !$kupacIdValue) {
          $_SESSION['poruka'] = "Greška: Molimo izaberite kupca za kompenzaciju/lokaciju.";
        } else {
          if ($isKompenzacija || $isLokacija) {
            $kanalLookupName = $isKompenzacija ? 'Kompenzacija' : 'Lokacija';
            if (!$kanal_prodaje_id) {
              try {
                if (!isset($kanalCrud) || !($kanalCrud instanceof CRUD)) {
                  $kanalCrud = new CRUD($_SESSION['godina']);
                  $kanalCrud->table = "kanal_prodaje";
                }
                $kanalLookup = $kanalCrud->select(['id'], ['naziv' => $kanalLookupName]);
                if (!empty($kanalLookup)) {
                  $kanal_prodaje_id = (int)$kanalLookup[0]['id'];
                }
              } catch (Exception $e) {
                // Ignorisati grešku pri automatskom određivanju kanala
              }
            }
          } else {
            $kanal_prodaje_id = null;
            $kupacIdValue = null;
            $datumProdaje = null;
            $datumPredugovora = null;
          }

          $check = new CRUD($_SESSION['godina']);
          $check->table = "garaze";
          $duplikat = $check->select(['id'], [
            'objekat_id' => $garaza['objekat_id'] ?? null,
            'naziv' => $naziv,
            'trenutni_id' => $garaza_id
          ], "SELECT id FROM garaze WHERE objekat_id = :objekat_id AND naziv = :naziv AND id != :trenutni_id LIMIT 1");

          if (!empty($duplikat)) {
            $_SESSION['poruka'] = "Greška: Već postoji garaža sa tim nazivom.";
          } else {
            $prodat = ($isKompenzacija || $isLokacija) ? 1 : 0;
            $tipProdajeNovo = $isKompenzacija ? 'kompenzacija' : ($isLokacija ? 'lokacija' : null);

            $crud->update([
              'naziv' => $naziv,
              'cena_sa_pdv' => $cena_sa_pdv,
              'prodat' => $prodat,
              'kupac_id' => $kupacIdValue,
              'datum_prodaje' => $datumProdaje,
              'datum_predugovora' => $datumPredugovora,
              'kanal_prodaje_id' => $kanal_prodaje_id,
              'tip_prodaje' => $tipProdajeNovo
            ], ['id' => $garaza_id]);

            $updated_garaza = $crud->select(['*'], ['id' => $garaza_id]);

            if (!empty($updated_garaza)
              && $updated_garaza[0]['naziv'] === $naziv
              && (float)$updated_garaza[0]['cena_sa_pdv'] == $cena_sa_pdv
              && (int)($updated_garaza[0]['prodat'] ?? 0) === $prodat
              && (($updated_garaza[0]['kupac_id'] ?? null) == $kupacIdValue)
              && (($updated_garaza[0]['datum_prodaje'] ?? null) == $datumProdaje)
              && (($updated_garaza[0]['datum_predugovora'] ?? null) == $datumPredugovora)
              && (($updated_garaza[0]['kanal_prodaje_id'] ?? null) == $kanal_prodaje_id)
              && (($updated_garaza[0]['tip_prodaje'] ?? null) === $tipProdajeNovo)) {
              $_SESSION['poruka'] = "Garaža je uspešno ažurirana!";
              $garaza = $updated_garaza[0];
              $tipProdaje = $garaza['tip_prodaje'] ?? null;
            } else {
              $_SESSION['poruka'] = "Greška pri ažuriranju garaže!";
            }
          }
        }
      }
    } catch (Exception $e) {
      $_SESSION['poruka'] = "Greška: " . $e->getMessage();
    }

    $garazaJeProdata = (int)($garaza['prodat'] ?? 0) === 1;
  }
}

$tipProdaje = $garaza['tip_prodaje'] ?? null;

?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Garaža - Izmena</title>

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

          <h3 class="center sekcija">IZMENA GARAŽE <i class="fas fa-edit"></i></h3> <br>

          <?php if (isset($_SESSION['poruka']) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <div class="alert alert-<?= strpos($_SESSION['poruka'], 'Greška') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
              <?= $_SESSION['poruka'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['poruka']); ?>
          <?php endif; ?>

          <?php if ($garazaJeProdata): ?>
            <div class="alert alert-warning w-100" role="alert">
              <i class="fas fa-lock"></i> Garaža je trenutno označena kao prodata. Da biste izvršili izmene, potrebno je prethodno raskinuti prodaju.
            </div>
            <form method="POST" class="mb-3" data-track-unsaved="false" onsubmit="return confirm('Da li ste sigurni da želite da raskinete prodaju ove garaže?');">
              <input type="hidden" name="akcija" value="raskini_prodaju">
              <input type="hidden" name="garaza_id" value="<?= $garaza_id ?>">
              <button type="submit" class="btn btn-warning">
                <i class="fas fa-undo"></i> Raskini prodaju
              </button>
            </form>
          <?php endif; ?>

          <form method="POST" id="forma">
            <fieldset <?= $garazaJeProdata ? 'disabled' : '' ?>>

            <div class="card mb-4 w-100">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-warehouse"></i> Osnovne informacije o garaži</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="naziv" class="col-form-label"><i class="fas fa-tag"></i> Naziv garaže</label>
                    <input class="form-control" id="naziv" name="naziv" type="text" maxlength="100" value="<?= htmlspecialchars($garaza['naziv'] ?? '') ?>" required>
                  </div>
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="cena_sa_pdv" class="col-form-label"><i class="fas fa-calculator"></i> Cena sa PDV (€)</label>
                    <input class="form-control" id="cena_sa_pdv" name="cena_sa_pdv" type="number" step="0.01" min="0" value="<?= htmlspecialchars($garaza['cena_sa_pdv'] ?? '') ?>" required>
                  </div>
                </div>
              </div>
            </div>

            <?php $prikaziKupca = $tipProdaje === 'kompenzacija' || $tipProdaje === 'lokacija'; ?>
            <div class="card mb-4 w-100">
              <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Status prodaje i kanal</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-4 col-sm-12 mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="kompenzacija" name="kompenzacija" value="1" <?= $tipProdaje === 'kompenzacija' ? 'checked' : '' ?>>
                      <label class="form-check-label" for="kompenzacija">
                        <i class="fas fa-exchange-alt"></i> Kompenzacija
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="lokacija" name="lokacija" value="1" <?= $tipProdaje === 'lokacija' ? 'checked' : '' ?>>
                      <label class="form-check-label" for="lokacija">
                        <i class="fas fa-map-marker-alt"></i> Lokacija
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="kanal_prodaje_id" class="col-form-label"><i class="fas fa-shopping-cart"></i> Kanal prodaje</label>
                    <select class="form-control" id="kanal_prodaje_id" name="kanal_prodaje_id">
                      <option value="">Izaberite kanal prodaje</option>
                      <?php foreach ($kanali as $kanal) : ?>
                        <option value="<?= $kanal['id'] ?>" <?= $kanal['id'] == ($garaza['kanal_prodaje_id'] ?? null) ? 'selected' : '' ?>><?= htmlspecialchars($kanal['naziv']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group row" id="kupac_row" style="<?= $prikaziKupca ? '' : 'display: none;' ?>">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="kupac_id" class="col-form-label"><i class="fas fa-user"></i> Kupac</label>
                    <select class="form-control" id="kupac_id" name="kupac_id">
                      <option value="">Izaberite kupca</option>
                      <?php foreach ($kupciLista as $kupacOpcija) : ?>
                        <option value="<?= $kupacOpcija['id'] ?>" <?= $kupacOpcija['id'] == ($garaza['kupac_id'] ?? null) ? 'selected' : '' ?>>
                          <?= htmlspecialchars(trim(($kupacOpcija['ime'] ?? '') . ' ' . ($kupacOpcija['prezime'] ?? ''))) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_prodaje" class="col-form-label"><i class="fas fa-calendar"></i> Datum prodaje</label>
                    <input class="form-control" id="datum_prodaje" name="datum_prodaje" type="date" value="<?= !empty($garaza['datum_prodaje']) ? htmlspecialchars(date('Y-m-d', strtotime($garaza['datum_prodaje']))) : '' ?>">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_predugovora" class="col-form-label"><i class="fas fa-file-contract"></i> Datum predugovora</label>
                    <input class="form-control" id="datum_predugovora" name="datum_predugovora" type="date" value="<?= !empty($garaza['datum_predugovora']) ? htmlspecialchars(date('Y-m-d', strtotime($garaza['datum_predugovora']))) : '' ?>">
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-2">
                <button type="submit" class="btn btn-success btn-lg w-100" <?= $garazaJeProdata ? 'disabled' : '' ?>> <i class="fas fa-save"></i> <br>
                  Sačuvaj izmene
                </button>
              </div>
              <div class="col-md-6 col-sm-12 mb-2">
                <a href="glavni.php" class="btn btn-danger btn-lg w-100"><i class="fas fa-ban"></i>
                  Otkaži</a>
              </div>
            </div>
            </fieldset>
          </form>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

<script>
  function setKanalProdajeByName(name) {
    let found = false;
    $('#kanal_prodaje_id option').each(function () {
      if ($(this).text().trim().toLowerCase() === name.toLowerCase()) {
        $('#kanal_prodaje_id').val($(this).val());
        found = true;
        return false;
      }
    });
    if (found) {
      $('#kanal_prodaje_id').trigger('change');
    }
  }

  function resetKanalProdaje() {
    $('#kanal_prodaje_id').val('');
    $('#kanal_prodaje_id').trigger('change');
  }

  function updateStatusFields(initial = false) {
    const kompenzacija = $('#kompenzacija').is(':checked');
    const lokacija = $('#lokacija').is(':checked');
    const isSale = kompenzacija || lokacija;

    if (isSale) {
      $('#kupac_row').show();
      $('#kupac_id').prop('required', true);
      $('#datum_prodaje').prop('required', false);
      const kanalName = kompenzacija ? 'Kompenzacija' : 'Lokacija';
      setKanalProdajeByName(kanalName);
    } else {
      $('#kupac_row').hide();
      $('#kupac_id').prop('required', false);
      $('#datum_prodaje').prop('required', false);
      if (!initial) {
        $('#kupac_id').val('');
        $('#datum_prodaje').val('');
        $('#datum_predugovora').val('');
        resetKanalProdaje();
      }
    }
  }

  $(document).ready(function () {
    $('#kompenzacija, #lokacija').on('change', function () {
      const kompenzacijaChecked = $('#kompenzacija').is(':checked');
      const lokacijaChecked = $('#lokacija').is(':checked');
      if (kompenzacijaChecked && lokacijaChecked) {
        alert('Garaža ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.');
        $(this).prop('checked', false);
      }
      updateStatusFields();
    });

    updateStatusFields(true);
  });
</script>

</body>

</html>
