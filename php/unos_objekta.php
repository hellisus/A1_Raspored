<?php
require  'config.php';
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

$formData = [
  'naziv' => '',
  'broj_stanova' => '0',
  'broj_lokala' => '0',
  'broj_garaza' => '0',
  'broj_parkinga' => '0',
  'faza_1' => date('d.m.Y'),
  'faza_2' => '',
  'faza_3' => '',
  'faza_4' => '',
];

// Obrada forme za unos objekta
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
    if ($formData['faza_1'] === '') {
      $formData['faza_1'] = date('d.m.Y');
    }
    $formData['faza_2'] = trim($_POST['faza_2'] ?? '');
    $formData['faza_3'] = trim($_POST['faza_3'] ?? '');
    $formData['faza_4'] = trim($_POST['faza_4'] ?? '');

    $errors = [];

    if ($naziv === '') {
      $errors[] = 'Naziv objekta je obavezan.';
    }

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

    if ($fazaDatumi['faza_1'] === null) {
      $today = new DateTime();
      $fazaDatumi['faza_1'] = $today;
      $formData['faza_1'] = $today->format('d.m.Y');
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

    if (!empty($errors)) {
      $_SESSION['poruka'] = 'Greška:<br>' . implode('<br>', $errors);
    } else {
      $objekti = new CRUD($_SESSION['godina']);
      $objekti->table = "objekti";

      // Provera duplikata: objekat sa istim nazivom već postoji
      $postoji = $objekti->select(['id'], ['naziv' => $naziv]);
      if (!empty($postoji)) {
        $_SESSION['poruka'] = "Greška: Objekat sa tim nazivom već postoji.";
      } else {
        $result = $objekti->insert([
          'naziv' => $naziv,
          'broj_stanova' => $broj_stanova,
          'broj_lokala' => $broj_lokala,
          'broj_garaza' => $broj_garaza,
          'broj_parkinga' => $broj_parkinga,
          'faza_1' => $fazaDatumi['faza_1'] instanceof DateTime ? $fazaDatumi['faza_1']->format('Y-m-d') : null,
          'faza_2' => $fazaDatumi['faza_2'] instanceof DateTime ? $fazaDatumi['faza_2']->format('Y-m-d') : null,
          'faza_3' => $fazaDatumi['faza_3'] instanceof DateTime ? $fazaDatumi['faza_3']->format('Y-m-d') : null,
          'faza_4' => $fazaDatumi['faza_4'] instanceof DateTime ? $fazaDatumi['faza_4']->format('Y-m-d') : null,
        ]);

        if ($result) {
          $_SESSION['poruka'] = "Objekat je uspešno kreiran!";
          $formData = [
            'naziv' => '',
            'broj_stanova' => '0',
            'broj_lokala' => '0',
            'broj_garaza' => '0',
            'broj_parkinga' => '0',
            'faza_1' => date('d.m.Y'),
            'faza_2' => '',
            'faza_3' => '',
            'faza_4' => '',
          ];
        } else {
          $_SESSION['poruka'] = "Greška pri kreiranju objekta!";
        }
      }
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


  <title>GP RAZ - Objekt - Unos</title>

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

          <h3 class="center sekcija">UNOS NOVOG OBJEKTA <i class="fas fa-city"></i></h3> <br>
          
          <?php if (isset($_SESSION['poruka'])): ?>
            <div class="alert alert-<?php echo strpos($_SESSION['poruka'], 'Greška') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
              <?php echo $_SESSION['poruka']; ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['poruka']); ?>
          <?php endif; ?>

          <form method="POST" id="forma">

            <!-- Osnovne informacije o objektu -->
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="far fa-building"></i> Osnovne informacije o objektu</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-12 col-sm-12 mb-3">
                    <label for="naziv" class="col-form-label"><i class="far fa-building"></i> Naziv objekta</label>
                    <input class="form-control" id="naziv" name="naziv" type="text" value="<?= htmlspecialchars($formData['naziv']) ?>" required placeholder="Unesite naziv objekta">
                  </div>
                </div>
              </div>
            </div>

            <!-- Kapacitet objekta -->
            <div class="card mb-4">
              <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-calculator"></i> Kapacitet objekta</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="broj_stanova" class="col-form-label"><i class="fas fa-home"></i> Broj stanova</label>
                    <input class="form-control" id="broj_stanova" name="broj_stanova" type="number" min="0" value="<?= htmlspecialchars($formData['broj_stanova']) ?>" required>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="broj_lokala" class="col-form-label"><i class="fas fa-store"></i> Broj lokala</label>
                    <input class="form-control" id="broj_lokala" name="broj_lokala" type="number" min="0" value="<?= htmlspecialchars($formData['broj_lokala']) ?>" required>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="broj_garaza" class="col-form-label"><i class="fas fa-warehouse"></i> Broj garaža</label>
                    <input class="form-control" id="broj_garaza" name="broj_garaza" type="number" min="0" value="<?= htmlspecialchars($formData['broj_garaza']) ?>" required>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="broj_parkinga" class="col-form-label"><i class="fas fa-parking"></i> Broj parking mesta</label>
                    <input class="form-control" id="broj_parkinga" name="broj_parkinga" type="number" min="0" value="<?= htmlspecialchars($formData['broj_parkinga']) ?>" required>
                  </div>
                </div>
              </div>
            </div>

            <!-- Faze izgradnje -->
            <div class="card mb-4">
              <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-flag-checkered"></i> Faze izgradnje</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="faza_1" class="col-form-label"><i class="fas fa-calendar-day"></i> Faza 1</label>
                    <input class="form-control" id="faza_1" name="faza_1" type="text" value="<?= htmlspecialchars($formData['faza_1']) ?>" placeholder="dd.mm.gggg" oninput="formatSerbianDate(this)" required>
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


            <!-- Dugmad za akcije -->
            <div class="card">
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-6 col-sm-12 mb-2">
                    <button type="submit" class="btn btn-success btn-lg w-100"> 
                      <i class="fas fa-plus-square"></i> <br>
                      Snimi
                    </button>
                  </div>
                  <div class="col-md-6 col-sm-12 mb-2">
                    <a href="glavni.php" class="btn btn-danger btn-lg w-100">
                      <i class="fas fa-ban"></i> <br>
                      Otkaži
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </form>





          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

</html>