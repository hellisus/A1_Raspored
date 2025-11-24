<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Učitaj lokal za izmenu
$lokal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$lokal_id) {
  header("location:glavni.php");
  exit;
}

$crud = new CRUD($_SESSION['godina']);
$crud->table = "lokali";
$lokal = $crud->select(['*'], ['id' => $lokal_id]);

if (empty($lokal)) {
  $_SESSION['poruka'] = "Greška: Lokal nije pronađen.";
  header("location:glavni.php");
  exit;
}

$lokal = $lokal[0]; // Uzmi prvi (i jedini) rezultat
$tipProdaje = $lokal['tip_prodaje'] ?? null;

// Učitaj objekte za dropdown
$objektiCrud = new CRUD($_SESSION['godina']);
$objektiCrud->table = "objekti";
$objekti = $objektiCrud->select(['*'], [], "SELECT * FROM objekti ORDER BY naziv ASC");

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

$lokalJeProdat = (int)($lokal['prodat'] ?? 0) === 1;

// Obrada forme za izmenu lokala
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['akcija']) && $_POST['akcija'] === 'raskini_prodaju') {
    try {
      $potvrdjeniId = isset($_POST['lokal_id']) ? (int)$_POST['lokal_id'] : 0;
      if ($potvrdjeniId !== $lokal_id) {
        throw new Exception('Neispravan zahtev za raskid prodaje.');
      }

      $crud->update([
        'prodat' => 0,
        'kupac_id' => null,
        'datum_prodaje' => null,
        'datum_predugovora' => null,
        'kanal_prodaje_id' => null,
        'tip_prodaje' => null,
      ], ['id' => $lokal_id]);

      $_SESSION['poruka'] = "Prodaja lokala je uspešno raskinuta. Sada možete izmeniti podatke.";
    } catch (Exception $e) {
      $_SESSION['poruka'] = "Greška: " . $e->getMessage();
    }

    $osvezen_lokal = $crud->select(['*'], ['id' => $lokal_id]);
    if (!empty($osvezen_lokal)) {
      $lokal = $osvezen_lokal[0];
      $tipProdaje = $lokal['tip_prodaje'] ?? null;
    }
    $lokalJeProdat = (int)($lokal['prodat'] ?? 0) === 1;
  } else {
    try {
      if ($lokalJeProdat) {
        $_SESSION['poruka'] = "Greška: Lokal je označen kao prodat. Najpre raskinite prodaju da biste menjali podatke.";
      } else {
        $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';
        $objekat_id = isset($_POST['objekat_id']) ? (int)$_POST['objekat_id'] : 0;
        $kvadratura = isset($_POST['kvadratura']) ? (float)$_POST['kvadratura'] : 0;
        $cena_po_m2 = isset($_POST['cena_po_m2']) ? (float)$_POST['cena_po_m2'] : 0;
        $ukupna_cena = isset($_POST['ukupna_cena']) ? (float)$_POST['ukupna_cena'] : 0;
        $pdv_suma = isset($_POST['pdv_suma']) ? (float)$_POST['pdv_suma'] : 0;
        $pdv_procenat_post = isset($_POST['pdv_procenat']) ? (float)$_POST['pdv_procenat'] : null;
        $osnovna_cena_post = isset($_POST['osnovna_cena']) ? (float)$_POST['osnovna_cena'] : null;
        $kompenzacija = isset($_POST['kompenzacija']) ? 1 : 0;
        $lokacija = isset($_POST['lokacija']) ? 1 : 0;
        $kanal_prodaje_id = isset($_POST['kanal_prodaje_id']) && $_POST['kanal_prodaje_id'] !== '' ? (int)$_POST['kanal_prodaje_id'] : null;
        $kupacIdValue = isset($_POST['kupac_id']) && $_POST['kupac_id'] !== '' ? (int)$_POST['kupac_id'] : null;
        $datumProdaje = isset($_POST['datum_prodaje']) && $_POST['datum_prodaje'] !== '' ? $_POST['datum_prodaje'] : null;
        $datumPredugovora = isset($_POST['datum_predugovora']) && $_POST['datum_predugovora'] !== '' ? $_POST['datum_predugovora'] : null;

        if ($kvadratura > 0 && $cena_po_m2 >= 0) {
          $izracunataUkupnaCena = $kvadratura * $cena_po_m2;
          if ($ukupna_cena < 0 || abs($ukupna_cena - $izracunataUkupnaCena) > 0.01) {
            $ukupna_cena = $izracunataUkupnaCena;
          }
        }

        $kvadratura = round($kvadratura, 2);
        $cena_po_m2 = round($cena_po_m2, 2);
        $ukupna_cena = round($ukupna_cena, 2);

        if ($pdv_procenat_post !== null && $pdv_procenat_post >= 0) {
          $osnovnaCenaBezPDV = $ukupna_cena / (1 + ($pdv_procenat_post / 100));
          $pdv_suma = round(max($ukupna_cena - $osnovnaCenaBezPDV, 0), 2);
        } elseif ($osnovna_cena_post !== null && $osnovna_cena_post >= 0 && $kvadratura > 0) {
          $osnovnaCenaBezPDV = $osnovna_cena_post * $kvadratura;
          $pdv_suma = round(max($ukupna_cena - $osnovnaCenaBezPDV, 0), 2);
        } else {
          $pdv_suma = round(max($pdv_suma, 0), 2);
        }

        $errors = [];
        if ($naziv === '' || !$objekat_id || $kvadratura <= 0) {
          $errors[] = "Naziv, objekat i kvadratura su obavezni, a kvadratura mora biti veća od 0.";
        }

        $cenaJeValidna = $kompenzacija || $lokacija ? $cena_po_m2 >= 0 : $cena_po_m2 > 0;
        if (!$cenaJeValidna) {
          if ($kompenzacija || $lokacija) {
            $errors[] = "Kod lokacije ili kompenzacije cena po m² mora biti veća ili jednaka 0.";
          } else {
            $errors[] = "Cena po m² mora biti veća od 0.";
          }
        }
        if ($ukupna_cena < 0 || $pdv_suma < 0) {
          $errors[] = "Ukupna cena i PDV suma moraju biti veći ili jednaki nuli.";
        }
        if ($kompenzacija && $lokacija) {
          $errors[] = "Lokal ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.";
        }
        if (($kompenzacija || $lokacija) && !$kupacIdValue) {
          $errors[] = "Molimo izaberite kupca za kompenzaciju/lokaciju.";
        }

        if ($kompenzacija || $lokacija) {
          $kanalLookupName = $kompenzacija ? 'Kompenzacija' : 'Lokacija';
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
            // Ignoriši grešku u pronalaženju kanala, korisnik će moći da izabere ručno
          }
        }

        if (!empty($errors)) {
          $_SESSION['poruka'] = "Greška: " . implode(' ', $errors);
        } else {
          $postojeciNaziv = $crud->select(['id'], ['objekat_id' => $objekat_id, 'naziv' => $naziv]);
          if (!empty($postojeciNaziv) && (int)$postojeciNaziv[0]['id'] !== $lokal_id) {
            $_SESSION['poruka'] = "Greška: Već postoji lokal sa tim nazivom u izabranom objektu.";
          } else {
            $prodat = ($kompenzacija || $lokacija) ? 1 : 0;
            $kupacZaUpis = $prodat ? $kupacIdValue : null;
            $datumProdajeZaUpis = $prodat ? $datumProdaje : null;
            $datumPredugovoraZaUpis = $prodat ? $datumPredugovora : null;
            $kanalZaUpis = $prodat ? $kanal_prodaje_id : ($kanal_prodaje_id ?: null);

            $tipProdaje = null;
            if ($kompenzacija) {
              $tipProdaje = 'kompenzacija';
            } elseif ($lokacija) {
              $tipProdaje = 'lokacija';
            }

            $tipProdaje = null;
            if ($kompenzacija) {
              $tipProdaje = 'kompenzacija';
            } elseif ($lokacija) {
              $tipProdaje = 'lokacija';
            }

            $crud->update([
              'naziv' => $naziv,
              'objekat_id' => $objekat_id,
              'kvadratura' => $kvadratura,
              'cena_po_m2' => $cena_po_m2,
              'ukupna_cena' => $ukupna_cena,
              'pdv_suma' => $pdv_suma,
              'prodat' => $prodat,
              'kupac_id' => $kupacZaUpis,
              'datum_prodaje' => $datumProdajeZaUpis,
              'datum_predugovora' => $datumPredugovoraZaUpis,
              'kanal_prodaje_id' => $kanalZaUpis,
              'tip_prodaje' => $tipProdaje
            ], ['id' => $lokal_id]);

            $updated_lokal = $crud->select(['*'], ['id' => $lokal_id]);

            if (!empty($updated_lokal)
              && abs((float)$updated_lokal[0]['kvadratura'] - $kvadratura) < 0.0001
              && $updated_lokal[0]['naziv'] === $naziv
              && abs((float)$updated_lokal[0]['cena_po_m2'] - $cena_po_m2) < 0.0001
              && abs((float)$updated_lokal[0]['ukupna_cena'] - $ukupna_cena) < 0.0001
              && abs((float)$updated_lokal[0]['pdv_suma'] - $pdv_suma) < 0.0001
              && (int)$updated_lokal[0]['objekat_id'] === $objekat_id
              && (int)($updated_lokal[0]['prodat'] ?? 0) === $prodat
              && (($updated_lokal[0]['kupac_id'] ?? null) == $kupacZaUpis)
              && (($updated_lokal[0]['datum_prodaje'] ?? null) == $datumProdajeZaUpis)
              && (($updated_lokal[0]['datum_predugovora'] ?? null) == $datumPredugovoraZaUpis)
              && (($updated_lokal[0]['kanal_prodaje_id'] ?? null) == $kanalZaUpis)
              && (($updated_lokal[0]['tip_prodaje'] ?? null) === $tipProdaje)) {
              $_SESSION['poruka'] = "Lokal je uspešno ažuriran!";
              $lokal = $updated_lokal[0];
              $tipProdaje = $lokal['tip_prodaje'] ?? null;
            } else {
              $_SESSION['poruka'] = "Greška pri ažuriranju lokala!";
            }
          }
        }
      }
    } catch (Exception $e) {
      $_SESSION['poruka'] = "Greška: " . $e->getMessage();
    }

    $lokalJeProdat = (int)($lokal['prodat'] ?? 0) === 1;
  }
}
$kvadraturaValue = isset($lokal['kvadratura']) ? (float)$lokal['kvadratura'] : 0.0;
$cenaPoM2Value = isset($lokal['cena_po_m2']) ? (float)$lokal['cena_po_m2'] : 0.0;
$ukupnaCenaValue = isset($lokal['ukupna_cena']) ? (float)$lokal['ukupna_cena'] : 0.0;
if ($ukupnaCenaValue <= 0 && $kvadraturaValue > 0 && $cenaPoM2Value > 0) {
  $ukupnaCenaValue = $kvadraturaValue * $cenaPoM2Value;
}
$pdvSumaValue = isset($lokal['pdv_suma']) ? (float)$lokal['pdv_suma'] : 0.0;
if ($pdvSumaValue < 0) {
  $pdvSumaValue = 0.0;
}

$osnovnaCenaPoM2Value = null;
$pdvProcenatValue = null;
$osnovnaCenaUkupno = $ukupnaCenaValue - $pdvSumaValue;
if ($osnovnaCenaUkupno < 0) {
  $osnovnaCenaUkupno = 0.0;
}
if ($kvadraturaValue > 0) {
  if ($osnovnaCenaUkupno > 0) {
    $osnovnaCenaPoM2Value = $osnovnaCenaUkupno / $kvadraturaValue;
  } elseif ($osnovnaCenaUkupno === 0.0 && $pdvSumaValue === 0.0) {
    $osnovnaCenaPoM2Value = $cenaPoM2Value;
  }
}
if ($osnovnaCenaUkupno > 0) {
  $pdvProcenatValue = ($pdvSumaValue / $osnovnaCenaUkupno) * 100;
} elseif ($pdvSumaValue === 0.0 && $ukupnaCenaValue > 0) {
  $pdvProcenatValue = 0.0;
}

$kvadraturaDisplay = number_format($kvadraturaValue, 2, '.', '');
$cenaPoM2Display = number_format($cenaPoM2Value, 2, '.', '');
$ukupnaCenaDisplay = number_format($ukupnaCenaValue, 2, '.', '');
$pdvSumaDisplay = number_format($pdvSumaValue, 2, '.', '');
$osnovnaCenaDisplay = $osnovnaCenaPoM2Value !== null ? number_format($osnovnaCenaPoM2Value, 2, '.', '') : '';
$pdvProcenatDisplay = $pdvProcenatValue !== null ? number_format($pdvProcenatValue, 2, '.', '') : '20.00';
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Lokal - Izmena</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/funkcije.js"></script>
  <script>
    function calculatePDVFromPercentage() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2SaPDV = parseFloat($('#cena_po_m2').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;

      if (kvadratura <= 0 || cenaPoM2SaPDV <= 0) {
        $('#ukupna_cena').val('');
        $('#pdv_suma').val('');
        $('#osnovna_cena').val('');
        return;
      }

      const ukupnaCenaSaPDV = kvadratura * cenaPoM2SaPDV;
      const faktor = 1 + (pdvProcenat / 100);
      const osnovnaCenaBezPDV = faktor > 0 ? ukupnaCenaSaPDV / faktor : ukupnaCenaSaPDV;
      const pdvSuma = Math.max(ukupnaCenaSaPDV - osnovnaCenaBezPDV, 0);
      const cenaPoM2BezPDV = kvadratura > 0 ? osnovnaCenaBezPDV / kvadratura : 0;

      $('#ukupna_cena').val(ukupnaCenaSaPDV.toFixed(2));
      $('#pdv_suma').val(pdvSuma.toFixed(2));
      $('#osnovna_cena').val(cenaPoM2BezPDV.toFixed(2));
    }

    function calculatePDVPercentage() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2SaPDV = parseFloat($('#cena_po_m2').val()) || 0;
      const pdvSuma = parseFloat($('#pdv_suma').val()) || 0;

      if (kvadratura <= 0 || cenaPoM2SaPDV <= 0) {
        return;
      }

      const ukupnaCenaSaPDV = kvadratura * cenaPoM2SaPDV;
      const osnovnaCenaBezPDV = Math.max(ukupnaCenaSaPDV - pdvSuma, 0);
      const cenaPoM2BezPDV = kvadratura > 0 ? osnovnaCenaBezPDV / kvadratura : 0;
      const pdvProcenat = osnovnaCenaBezPDV > 0 ? (pdvSuma / osnovnaCenaBezPDV) * 100 : 0;

      $('#ukupna_cena').val(ukupnaCenaSaPDV.toFixed(2));
      $('#osnovna_cena').val(cenaPoM2BezPDV.toFixed(2));
      $('#pdv_procenat').val(pdvProcenat.toFixed(2));
    }

    function calculateFromPriceWithoutPDV() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2BezPDV = parseFloat($('#osnovna_cena').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;

      if (kvadratura <= 0 || cenaPoM2BezPDV <= 0) {
        return;
      }

      const osnovnaCenaUkupno = kvadratura * cenaPoM2BezPDV;
      const pdvSuma = Math.max(osnovnaCenaUkupno * (pdvProcenat / 100), 0);
      const ukupnaCenaSaPDV = osnovnaCenaUkupno + pdvSuma;
      const cenaPoM2SaPDV = kvadratura > 0 ? ukupnaCenaSaPDV / kvadratura : 0;

      $('#pdv_suma').val(pdvSuma.toFixed(2));
      $('#cena_po_m2').val(cenaPoM2SaPDV.toFixed(2));
      $('#ukupna_cena').val(ukupnaCenaSaPDV.toFixed(2));
    }

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

      if (kompenzacija || lokacija) {
        $('#kupac_row').show();
        $('#kupac_id').prop('required', true);
        const kanalName = kompenzacija ? 'Kompenzacija' : 'Lokacija';
        setKanalProdajeByName(kanalName);
      } else {
        $('#kupac_row').hide();
        $('#kupac_id').prop('required', false);
        if (!initial) {
          $('#kupac_id').val('');
          $('#datum_prodaje').val('');
          $('#datum_predugovora').val('');
          resetKanalProdaje();
        }
      }
    }

    $(document).ready(function () {
      $('#kvadratura, #cena_po_m2').on('input', calculatePDVFromPercentage);
      $('#pdv_procenat').on('input', calculatePDVFromPercentage);
      $('#pdv_suma').on('input', calculatePDVPercentage);
      $('#osnovna_cena').on('input', calculateFromPriceWithoutPDV);

      $('#kompenzacija, #lokacija').on('change', function () {
        const kompenzacijaChecked = $('#kompenzacija').is(':checked');
        const lokacijaChecked = $('#lokacija').is(':checked');
        if (kompenzacijaChecked && lokacijaChecked) {
          alert('Lokal ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.');
          $(this).prop('checked', false);
        }
        updateStatusFields();
      });

      const pocetnaPdvSuma = parseFloat($('#pdv_suma').val());
      if (!isNaN(pocetnaPdvSuma) && pocetnaPdvSuma > 0) {
        calculatePDVPercentage();
      } else {
        calculatePDVFromPercentage();
      }

      updateStatusFields(true);
    });
  </script>

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

          <h3 class="center sekcija">IZMENA LOKALA <i class="fas fa-edit"></i></h3> <br>

          <?php if (isset($_SESSION['poruka']) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <div class="alert alert-<?= strpos($_SESSION['poruka'], 'Greška') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
              <?= $_SESSION['poruka'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['poruka']); ?>
          <?php endif; ?>

          <?php if ($lokalJeProdat): ?>
            <div class="alert alert-warning w-100" role="alert">
              <i class="fas fa-lock"></i> Lokal je trenutno označen kao prodat. Za izmene je neophodno da prethodno raskinete prodaju.
            </div>
            <form method="POST" class="mb-3" data-track-unsaved="false" onsubmit="return confirm('Da li ste sigurni da želite da raskinete prodaju ovog lokala?');">
              <input type="hidden" name="akcija" value="raskini_prodaju">
              <input type="hidden" name="lokal_id" value="<?= $lokal_id ?>">
              <button type="submit" class="btn btn-warning">
                <i class="fas fa-undo"></i> Raskini prodaju
              </button>
            </form>
          <?php endif; ?>

          <form method="POST" id="forma">
            <fieldset <?= $lokalJeProdat ? 'disabled' : '' ?>>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="naziv" class="col-form-label"><i class="fas fa-tag"></i> Naziv</label>
                <input class="form-control" id="naziv" name="naziv" type="text" value="<?= htmlspecialchars($lokal['naziv'] ?? '') ?>" required>
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="objekat_id" class="col-form-label"><i class="far fa-building"></i> Objekat</label>
                <select class="form-control" id="objekat_id" name="objekat_id" required>
                  <option value="">Izaberite objekat</option>
                  <?php foreach ($objekti as $objekat) : ?>
                    <option value="<?= $objekat['id'] ?>" <?= $objekat['id'] == $lokal['objekat_id'] ? 'selected' : '' ?>><?= htmlspecialchars($objekat['naziv']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="kvadratura" class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                <input class="form-control" id="kvadratura" name="kvadratura" type="number" step="0.01" min="0" value="<?= htmlspecialchars($kvadraturaDisplay) ?>" required>
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="cena_po_m2" class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² sa PDV (€)</label>
                <input class="form-control" id="cena_po_m2" name="cena_po_m2" type="number" step="0.01" min="0" value="<?= htmlspecialchars($cenaPoM2Display) ?>" required>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="pdv_procenat" class="col-form-label"><i class="fas fa-percentage"></i> PDV procenat (%)</label>
                <input class="form-control" id="pdv_procenat" name="pdv_procenat" type="number" step="0.01" min="0" max="100" value="<?= htmlspecialchars($pdvProcenatDisplay) ?>">
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="osnovna_cena" class="col-form-label"><i class="fas fa-calculator"></i> Cena po m² bez PDV (€)</label>
                <input class="form-control" id="osnovna_cena" name="osnovna_cena" type="number" step="0.01" min="0" value="<?= htmlspecialchars($osnovnaCenaDisplay) ?>">
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="ukupna_cena" class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                <input class="form-control" id="ukupna_cena" name="ukupna_cena" type="number" step="0.01" min="0" value="<?= htmlspecialchars($ukupnaCenaDisplay) ?>" readonly>
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="pdv_suma" class="col-form-label"><i class="fas fa-percentage"></i> PDV suma (€)</label>
                <input class="form-control" id="pdv_suma" name="pdv_suma" type="number" step="0.01" min="0" value="<?= htmlspecialchars($pdvSumaDisplay) ?>">
              </div>
            </div>

            <div class="card mb-4">
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
                        <option value="<?= $kanal['id'] ?>" <?= $kanal['id'] == ($lokal['kanal_prodaje_id'] ?? null) ? 'selected' : '' ?>><?= htmlspecialchars($kanal['naziv']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <?php $prikaziKupca = $tipProdaje === 'kompenzacija' || $tipProdaje === 'lokacija'; ?>
                <div class="form-group row" id="kupac_row" style="<?= $prikaziKupca ? '' : 'display: none;' ?>">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="kupac_id" class="col-form-label"><i class="fas fa-user"></i> Kupac</label>
                    <select class="form-control" id="kupac_id" name="kupac_id">
                      <option value="">Izaberite kupca</option>
                      <?php foreach ($kupciLista as $kupacOpcija) : ?>
                        <option value="<?= $kupacOpcija['id'] ?>" <?= $kupacOpcija['id'] == ($lokal['kupac_id'] ?? null) ? 'selected' : '' ?>>
                          <?= htmlspecialchars(trim(($kupacOpcija['ime'] ?? '') . ' ' . ($kupacOpcija['prezime'] ?? ''))) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_prodaje" class="col-form-label"><i class="fas fa-calendar"></i> Datum prodaje</label>
                    <input class="form-control" id="datum_prodaje" name="datum_prodaje" type="date" value="<?= !empty($lokal['datum_prodaje']) ? htmlspecialchars(date('Y-m-d', strtotime($lokal['datum_prodaje']))) : '' ?>">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_predugovora" class="col-form-label"><i class="fas fa-file-contract"></i> Datum predugovora</label>
                    <input class="form-control" id="datum_predugovora" name="datum_predugovora" type="date" value="<?= !empty($lokal['datum_predugovora']) ? htmlspecialchars(date('Y-m-d', strtotime($lokal['datum_predugovora']))) : '' ?>">
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-2">
                <button type="submit" class="btn btn-success btn-lg w-100" <?= $lokalJeProdat ? 'disabled' : '' ?>> <i class="fas fa-save"></i> <br>
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

</body>

</html>
