<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Učitaj kupca za izmenu
$kupac_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$kupac_id) {
  header("location:pregled_kupaca.php");
  exit;
}

$crud = new CRUD($_SESSION['godina']);
$crud->table = "kupci";
$kupac = $crud->select(['*'], ['id' => $kupac_id]);

if (empty($kupac)) {
  $_SESSION['poruka'] = "Greška: Kupac nije pronađen.";
  header("location:pregled_kupaca.php");
  exit;
}

$kupac = $kupac[0]; // Uzmi prvi (i jedini) rezultat

// Obrada forme za izmenu kupca
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  try {
    $ime = isset($_POST['ime']) ? trim($_POST['ime']) : '';
    $prezime = isset($_POST['prezime']) ? trim($_POST['prezime']) : null;
    $br_telefona = isset($_POST['br_telefona']) ? trim($_POST['br_telefona']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $tip_kupca_id = isset($_POST['tip_kupca_id']) && $_POST['tip_kupca_id'] !== '' ? $_POST['tip_kupca_id'] : null;
    $napomena = isset($_POST['napomena']) ? trim($_POST['napomena']) : null;

    if ($ime === '' || $br_telefona === '') {
      $_SESSION['poruka'] = "Greška: Ime i broj telefona su obavezni.";
    } else {
      // Provera duplikata: isto ime i prezime već postoje (osim trenutnog kupca)
      $postoji = $crud->select(['id'], ['ime' => $ime, 'prezime' => $prezime]);
      $duplikat = false;
      foreach ($postoji as $p) {
        if ($p['id'] != $kupac_id) {
          $duplikat = true;
          break;
        }
      }
      
      if ($duplikat) {
        $_SESSION['poruka'] = "Greška: Kupac sa istim imenom i prezimenom već postoji.";
      } else {
        // CRUD update metoda ne vraća vrednost, samo izvršava upit
        $crud->update([
          'ime' => $ime,
          'prezime' => $prezime,
          'br_telefona' => $br_telefona,
          'email' => $email,
          'tip_kupca_id' => $tip_kupca_id,
          'napomena' => $napomena,
        ], ['id' => $kupac_id]);

        // Proveri da li je kupac stvarno ažuriran
        $updated_kupac = $crud->select(['*'], ['id' => $kupac_id]);
        
        if (!empty($updated_kupac) && $updated_kupac[0]['ime'] === $ime) {
          $_SESSION['poruka'] = "Kupac je uspešno ažuriran!";
          // Ažuriraj podatke koji se prikazuju u formi
          $kupac = $updated_kupac[0];
        } else {
          $_SESSION['poruka'] = "Greška pri ažuriranju kupca!";
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

  <title>GP RAZ - Kupac - Izmena</title>

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

          <h3 class="center sekcija">IZMENA KUPCA <i class="fas fa-edit"></i></h3> <br>

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
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="ime" class="col-form-label"><i class="far fa-user"></i> Ime</label>
                <input class="form-control" id="ime" name="ime" type="text" maxlength="50" value="<?= htmlspecialchars($kupac['ime']) ?>" required>
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="prezime" class="col-form-label"><i class="far fa-user"></i> Prezime</label>
                <input class="form-control" id="prezime" name="prezime" type="text" maxlength="50" value="<?= htmlspecialchars($kupac['prezime'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="br_telefona" class="col-form-label"><i class="fas fa-phone"></i> Broj telefona</label>
                <input class="form-control" id="br_telefona" name="br_telefona" type="text" maxlength="50" value="<?= htmlspecialchars($kupac['br_telefona']) ?>" required>
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="email" class="col-form-label"><i class="fas fa-envelope"></i> Email</label>
                <input class="form-control" id="email" name="email" type="email" maxlength="100" value="<?= htmlspecialchars($kupac['email'] ?? '') ?>" placeholder="Opcionalno">
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-12 col-sm-12 mb-3">
                <label for="tip_kupca_id" class="col-form-label"><i class="fas fa-tag"></i> Tip kupca</label>
                <select class="form-control" id="tip_kupca_id" name="tip_kupca_id">
                  <option value="">Izaberite tip kupca</option>
                  <?php
                  $tipCrud = new CRUD($_SESSION['godina']);
                  $tipCrud->table = "tip_kupca";
                  $tipovi = $tipCrud->select(['*'], [], "SELECT * FROM tip_kupca ORDER BY id_tipa_kupca ASC");
                  foreach ($tipovi as $tip) : ?>
                    <option value="<?= $tip['id_tipa_kupca'] ?>" <?= $tip['id_tipa_kupca'] == $kupac['tip_kupca_id'] ? 'selected' : '' ?>><?= htmlspecialchars($tip['naziv']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-12 col-sm-12 mb-3">
                <label for="napomena" class="col-form-label"><i class="fas fa-sticky-note"></i> Napomena</label>
                <input class="form-control" id="napomena" name="napomena" type="text" maxlength="250" value="<?= htmlspecialchars($kupac['napomena'] ?? '') ?>" placeholder="Opcionalno">
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-2">
                <button type="submit" class="btn btn-success btn-lg w-100"> <i class="fas fa-save"></i> <br>
                  Sačuvaj izmene
                </button>
              </div>
              <div class="col-md-6 col-sm-12 mb-2">
                <a href="pregled_kupaca.php" class="btn btn-danger btn-lg w-100"><i class="fas fa-ban"></i>
                  Otkaži</a>
              </div>
            </div>
          </form>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

</html>
