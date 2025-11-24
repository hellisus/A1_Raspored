<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

function parseSerbianDate(?string $value)
{
  $value = trim($value ?? '');
  if ($value === '') {
    return null;
  }

  $date = DateTime::createFromFormat('d.m.Y', $value);
  return ($date && $date->format('d.m.Y') === $value) ? $date : false;
}

function dbDateToSerbian(?string $value): string
{
  if (!$value) {
    return '';
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  return $date ? $date->format('d.m.Y') : '';
}

// Učitaj objekat za izmenu
$objekat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$objekat_id) {
  header("location:glavni.php");
  exit;
}

$crud = new CRUD($_SESSION['godina']);
$crud->table = "objekti";
$objekat = $crud->select(['*'], ['id' => $objekat_id]);

if (empty($objekat)) {
  $_SESSION['poruka'] = "Greška: Objekat nije pronađen.";
  header("location:glavni.php");
  exit;
}

$objekat = $objekat[0]; // Uzmi prvi (i jedini) rezultat

$formData = [
  'naziv' => $objekat['naziv'] ?? '',
  'broj_stanova' => (string)($objekat['broj_stanova'] ?? 0),
  'broj_lokala' => (string)($objekat['broj_lokala'] ?? 0),
  'broj_garaza' => (string)($objekat['broj_garaza'] ?? 0),
  'broj_parkinga' => (string)($objekat['broj_parkinga'] ?? 0),
  'faza_1' => dbDateToSerbian($objekat['faza_1'] ?? null),
  'faza_2' => dbDateToSerbian($objekat['faza_2'] ?? null),
  'faza_3' => dbDateToSerbian($objekat['faza_3'] ?? null),
  'faza_4' => dbDateToSerbian($objekat['faza_4'] ?? null),
];

// Proveri trenutno kreirane stanove, garaže, lokale i parking mesta
$podatci = new CRUD($_SESSION['godina']);

// Stanovi - kreirano
$podatci->table = "stanovi";
$stanovi_kreirano = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM stanovi WHERE objekat_id = " . (int)$objekat_id);
$trenutni_broj_stanova = !empty($stanovi_kreirano) ? (int)$stanovi_kreirano[0]['count'] : 0;

// Stanovi - prodato
$stanovi_prodato = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM stanovi WHERE objekat_id = " . (int)$objekat_id . " AND prodat = 1");
$prodato_stanova = !empty($stanovi_prodato) ? (int)$stanovi_prodato[0]['count'] : 0;

// Lokali - kreirano
$podatci->table = "lokali";
$lokali_kreirano = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM lokali WHERE objekat_id = " . (int)$objekat_id);
$trenutni_broj_lokala = !empty($lokali_kreirano) ? (int)$lokali_kreirano[0]['count'] : 0;

// Lokali - prodato
$lokali_prodato = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM lokali WHERE objekat_id = " . (int)$objekat_id . " AND prodat = 1");
$prodato_lokala = !empty($lokali_prodato) ? (int)$lokali_prodato[0]['count'] : 0;

// Garaže - kreirano
$podatci->table = "garaze";
$garaze_kreirano = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM garaze WHERE objekat_id = " . (int)$objekat_id);
$trenutni_broj_garaza = !empty($garaze_kreirano) ? (int)$garaze_kreirano[0]['count'] : 0;

// Garaže - prodato
$garaze_prodato = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM garaze WHERE objekat_id = " . (int)$objekat_id . " AND prodat = 1");
$prodato_garaza = !empty($garaze_prodato) ? (int)$garaze_prodato[0]['count'] : 0;

// Parking mesta - kreirano
$podatci->table = "parking_mesta";
$parking_kreirano = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM parking_mesta WHERE objekat_id = " . (int)$objekat_id);
$trenutni_broj_parkinga = !empty($parking_kreirano) ? (int)$parking_kreirano[0]['count'] : 0;

// Parking mesta - prodato
$parking_prodato = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM parking_mesta WHERE objekat_id = " . (int)$objekat_id . " AND prodat = 1");
$prodato_parking_mesta = !empty($parking_prodato) ? (int)$parking_prodato[0]['count'] : 0;

// Obrada forme za izmenu objekta
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  try {
    $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';
    $broj_stanova = isset($_POST['broj_stanova']) ? (int)$_POST['broj_stanova'] : 0;
    $broj_lokala = isset($_POST['broj_lokala']) ? (int)$_POST['broj_lokala'] : 0;
    $broj_garaza = isset($_POST['broj_garaza']) ? (int)$_POST['broj_garaza'] : 0;
    $broj_parkinga = isset($_POST['broj_parkinga']) ? (int)$_POST['broj_parkinga'] : 0;

    $formData['naziv'] = $naziv;
    $formData['broj_stanova'] = (string)$broj_stanova;
    $formData['broj_lokala'] = (string)$broj_lokala;
    $formData['broj_garaza'] = (string)$broj_garaza;
    $formData['broj_parkinga'] = (string)$broj_parkinga;
    $formData['faza_1'] = trim($_POST['faza_1'] ?? '');
    $formData['faza_2'] = trim($_POST['faza_2'] ?? '');
    $formData['faza_3'] = trim($_POST['faza_3'] ?? '');
    $formData['faza_4'] = trim($_POST['faza_4'] ?? '');

    $errors = [];

    if ($naziv === '') {
      $errors[] = "Naziv objekta je obavezan.";
    }

    if ($broj_stanova < $trenutni_broj_stanova) {
      $errors[] = "Broj stanova ne može biti manji od trenutno kreiranih stanova ($trenutni_broj_stanova).";
    }
    if ($broj_garaza < $trenutni_broj_garaza) {
      $errors[] = "Broj garaža ne može biti manji od trenutno kreiranih garaža ($trenutni_broj_garaza).";
    }
    if ($broj_lokala < $trenutni_broj_lokala) {
      $errors[] = "Broj lokala ne može biti manji od trenutno kreiranih lokala ($trenutni_broj_lokala).";
    }
    if ($broj_parkinga < $trenutni_broj_parkinga) {
      $errors[] = "Broj parking mesta ne može biti manji od trenutno kreiranih parking mesta ($trenutni_broj_parkinga).";
    }

    if (empty($errors)) {
      $fazaLabels = [
        'faza_1' => 'Faza 1',
        'faza_2' => 'Faza 2',
        'faza_3' => 'Faza 3',
        'faza_4' => 'Faza 4',
      ];
      $fazaDatumi = [];

      foreach ($fazaLabels as $key => $label) {
        $parsed = parseSerbianDate($formData[$key]);
        if ($parsed === false) {
          $errors[] = sprintf('%s mora biti u formatu dd.mm.gggg.', $label);
        } else {
          $fazaDatumi[$key] = $parsed; // DateTime ili null
        }
      }

      $redosled = array_keys($fazaLabels);
      for ($i = 1; $i < count($redosled); $i++) {
        $prevKey = $redosled[$i - 1];
        $currKey = $redosled[$i];
        if ($fazaDatumi[$prevKey] instanceof DateTime && $fazaDatumi[$currKey] instanceof DateTime) {
          if ($fazaDatumi[$currKey] < $fazaDatumi[$prevKey]) {
            $errors[] = sprintf('%s ne može biti pre %s.', $fazaLabels[$currKey], strtolower($fazaLabels[$prevKey]));
          }
        }
      }

      if (empty($errors)) {
        // Osiguraj da je $crud objekat postavljen za objekti tabelu
        $crud->table = "objekti";
        
        $update_data = [
          'naziv' => $naziv,
          'broj_stanova' => $broj_stanova,
          'broj_lokala' => $broj_lokala,
          'broj_garaza' => $broj_garaza,
          'broj_parkinga' => $broj_parkinga,
          'faza_1' => $fazaDatumi['faza_1'] instanceof DateTime ? $fazaDatumi['faza_1']->format('Y-m-d') : null,
          'faza_2' => $fazaDatumi['faza_2'] instanceof DateTime ? $fazaDatumi['faza_2']->format('Y-m-d') : null,
          'faza_3' => $fazaDatumi['faza_3'] instanceof DateTime ? $fazaDatumi['faza_3']->format('Y-m-d') : null,
          'faza_4' => $fazaDatumi['faza_4'] instanceof DateTime ? $fazaDatumi['faza_4']->format('Y-m-d') : null,
        ];
        
        $crud->update($update_data, ['id' => $objekat_id]);
        
        $updated_objekat = $crud->select(['*'], ['id' => $objekat_id]);
        
        if (!empty($updated_objekat) && $updated_objekat[0]['naziv'] === $naziv) {
          $_SESSION['poruka'] = "Objekat je uspešno ažuriran!";
          $objekat = $updated_objekat[0];
          $formData['faza_1'] = dbDateToSerbian($objekat['faza_1'] ?? null);
          $formData['faza_2'] = dbDateToSerbian($objekat['faza_2'] ?? null);
          $formData['faza_3'] = dbDateToSerbian($objekat['faza_3'] ?? null);
          $formData['faza_4'] = dbDateToSerbian($objekat['faza_4'] ?? null);
        } else {
          $_SESSION['poruka'] = "Greška pri ažuriranju objekta!";
        }
      }
    }

    if (!empty($errors)) {
      $_SESSION['poruka'] = 'Greška:<br>' . implode('<br>', $errors);
    }
  } catch (Exception $e) {
    $_SESSION['poruka'] = "Greška: " . $e->getMessage();
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

  <title>GP RAZ - Objekt - Izmena</title>

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
    function formatSerbianDate(el) {
      const digits = (el.value || '').replace(/\D/g, '').slice(0, 8);
      let formatted = '';

      if (digits.length <= 2) {
        formatted = digits;
      } else if (digits.length <= 4) {
        formatted = digits.slice(0, 2) + '.' + digits.slice(2);
      } else {
        formatted = digits.slice(0, 2) + '.' + digits.slice(2, 4) + '.' + digits.slice(4);
      }

      el.value = formatted;
    }
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

          <h3 class="center sekcija">IZMENA OBJEKTA <i class="fas fa-edit"></i></h3> <br>

          <div class="alert alert-info" role="alert">
            <h6><i class="fas fa-info-circle"></i> Trenutno stanje:</h6>
            <div class="row">
              <div class="col-md-6">
                <small>
                  <strong>Kreirano:</strong><br>
                  Stanovi: <strong><?= $trenutni_broj_stanova ?></strong> | 
                  Garaže: <strong><?= $trenutni_broj_garaza ?></strong><br>
                  Lokali: <strong><?= $trenutni_broj_lokala ?></strong> | 
                  Parking: <strong><?= $trenutni_broj_parkinga ?></strong>
                </small>
              </div>
              <div class="col-md-6">
                <small>
                  <strong>Prodato:</strong><br>
                  Stanovi: <strong><?= $prodato_stanova ?></strong> | 
                  Garaže: <strong><?= $prodato_garaza ?></strong><br>
                  Lokali: <strong><?= $prodato_lokala ?></strong> | 
                  Parking: <strong><?= $prodato_parking_mesta ?></strong>
                </small>
              </div>
            </div>
          </div>

          <?php if (isset($_SESSION['poruka']) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <div class="alert alert-<?= strpos($_SESSION['poruka'], 'Greška') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
              <?= $_SESSION['poruka'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['poruka']); ?>
          <?php endif; ?>

          <form method="POST" id="forma">

            <div class="form-group row">
              <div class="input-field col s12">
                <label for="naziv" class="col-form-label"><i class="far fa-building"></i> Naziv</label>
                <input class="form-control" id="naziv" name="naziv" type="text" value="<?= htmlspecialchars($formData['naziv']) ?>" required>
              </div>
            </div>

             <div class="form-group row">
               <div class="input-field col s12">
                 <label for="broj_stanova" class="col-form-label"><i class="fas fa-home"></i> Broj stanova</label>
                 <input class="form-control" id="broj_stanova" name="broj_stanova" type="number" min="0" value="<?= htmlspecialchars($formData['broj_stanova']) ?>">
               </div>
             </div>

             <div class="form-group row">
               <div class="input-field col s12">
                 <label for="broj_lokala" class="col-form-label"><i class="fas fa-store"></i> Broj lokala</label>
                 <input class="form-control" id="broj_lokala" name="broj_lokala" type="number" min="0" value="<?= htmlspecialchars($formData['broj_lokala']) ?>">
               </div>
             </div>

             <div class="form-group row">
               <div class="input-field col s12">
                 <label for="broj_garaza" class="col-form-label"><i class="fas fa-warehouse"></i> Broj garaža</label>
                 <input class="form-control" id="broj_garaza" name="broj_garaza" type="number" min="0" value="<?= htmlspecialchars($formData['broj_garaza']) ?>">
               </div>
             </div>

             <div class="form-group row">
               <div class="input-field col s12">
                 <label for="broj_parkinga" class="col-form-label"><i class="fas fa-parking"></i> Broj parking mesta</label>
                 <input class="form-control" id="broj_parkinga" name="broj_parkinga" type="number" min="0" value="<?= htmlspecialchars($formData['broj_parkinga']) ?>">
               </div>
             </div>

            <div class="form-group row">
              <div class="input-field col s12">
                <div class="card mb-4">
                  <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-flag-checkered"></i> Faze izgradnje</h5>
                  </div>
                  <div class="card-body">
                    <div class="form-group row">
                      <div class="col-md-3 col-sm-12 mb-3">
                        <label for="faza_1" class="col-form-label"><i class="fas fa-calendar-day"></i> Faza 1</label>
                        <input class="form-control" id="faza_1" name="faza_1" type="text" value="<?= htmlspecialchars($formData['faza_1']) ?>" placeholder="dd.mm.gggg" oninput="formatSerbianDate(this)">
                        <small class="form-text text-muted">Format: dd.mm.gggg</small>
                      </div>
                      <div class="col-md-3 col-sm-12 mb-3">
                        <label for="faza_2" class="col-form-label"><i class="fas fa-calendar"></i> Faza krov</label>
                        <input class="form-control" id="faza_2" name="faza_2" type="text" value="<?= htmlspecialchars($formData['faza_2']) ?>" placeholder="dd.mm.gggg" oninput="formatSerbianDate(this)">
                      </div>
                      <div class="col-md-3 col-sm-12 mb-3">
                        <label for="faza_3" class="col-form-label"><i class="fas fa-calendar"></i> Faza stolarija</label>
                        <input class="form-control" id="faza_3" name="faza_3" type="text" value="<?= htmlspecialchars($formData['faza_3']) ?>" placeholder="dd.mm.gggg" oninput="formatSerbianDate(this)">
                      </div>
                      <div class="col-md-3 col-sm-12 mb-3">
                        <label for="faza_4" class="col-form-label"><i class="fas fa-calendar"></i> Faza ključevi</label>
                        <input class="form-control" id="faza_4" name="faza_4" type="text" value="<?= htmlspecialchars($formData['faza_4']) ?>" placeholder="dd.mm.gggg" oninput="formatSerbianDate(this)">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-2">
                <button type="submit" class="btn btn-success btn-lg w-100"> <i class="fas fa-save"></i> <br>
                  Sačuvaj izmene
                </button>
              </div>
              <div class="col-md-6 col-sm-12 mb-2">
                <a href="glavni.php" class="btn btn-danger btn-lg w-100"><i class="fas fa-ban"></i>
                  Otkaži</a>
              </div>
            </div>
          </form>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

</html>
