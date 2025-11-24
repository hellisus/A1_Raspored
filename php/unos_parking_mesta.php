<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada forme za unos parking mesta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['objekat_id'])) {
  try {
    // Provera da li izabrani objekat ima definisan broj parking mesta > 0
    $objCrud = new CRUD($_SESSION['godina']);
    $objCrud->table = "objekti";
    $obj = $objCrud->select(['*'], ['id' => $_POST['objekat_id']]);
    if (empty($obj) || intval($obj[0]['broj_parkinga']) <= 0) {
      $_SESSION['poruka'] = "Greška: Izabrani objekat nema definisana parking mesta.";
    } else {
      // Provera duplikata naziva za isti objekat
      $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';
      if ($naziv === '') {
        $_SESSION['poruka'] = "Greška: Naziv je obavezan.";
      } else {
        $checkCrud = new CRUD($_SESSION['godina']);
        $checkCrud->table = "parking_mesta";
        $postoji = $checkCrud->select(['id'], ['objekat_id' => $_POST['objekat_id'], 'naziv' => $naziv]);
        if (!empty($postoji)) {
          $_SESSION['poruka'] = "Greška: Već postoji parking mesto sa tim nazivom u izabranom objektu.";
        } else {
      $podatci = new CRUD($_SESSION['godina']);
      $podatci->table = "parking_mesta";

      $data = [
        'objekat_id' => $_POST['objekat_id'],
        'naziv' => $naziv,
        'cena' => $_POST['cena'],
        'stan_id' => 0, // inicijalno nevezano za stan
        'napomena' => $_POST['napomena'] ?? null
      ];

      $result = $podatci->insert($data);

      if ($result) {
        $_SESSION['poruka'] = "Parking mesto je uspešno dodato!";
      } else {
        $_SESSION['poruka'] = "Greška pri dodavanju parking mesta!";
      }
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


  <title>GP RAZ - Parking mesto - Unos</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Funkcija za generisanje sledećeg naziva parking mesta
    function generateNextParkingName() {
      const objekatId = $('#objekat_id').val();
      if (objekatId) {
        $.ajax({
          url: 'get_next_parking_name.php',
          method: 'POST',
          data: { objekat_id: objekatId },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              $('#naziv').val(response.nextName);
            }
          },
          error: function() {
            console.log('Greška pri učitavanju sledećeg naziva parking mesta');
          }
        });
      }
    }

    $(document).ready(function () {
      // Generiši sledeći naziv kada se promeni objekat
      $('#objekat_id').on('change', generateNextParkingName);
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

          <h3 class="center sekcija">Kreiranje novog parking mesta <i class="fas fa-parking"></i></h3> <br>

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

            <!-- Osnovne informacije o parking mestu -->
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-parking"></i> Osnovne informacije o parking mestu</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="objekat_id" class="col-form-label"><i class="far fa-building"></i> Objekat</label>
                    <select class="form-control" id="objekat_id" name="objekat_id" required>
                      <option value="">Izaberite objekat</option>
                      <?php
                      $podatci = new CRUD($_SESSION['godina']);
                      $podatci->table = "objekti";
                      $objekti = $podatci->select(['*'], [], "SELECT o.* FROM objekti o 
                        WHERE COALESCE(o.broj_parkinga, 0) > (SELECT COUNT(*) FROM parking_mesta p WHERE p.objekat_id = o.id)
                        ORDER BY o.naziv ASC");
                      foreach ($objekti as $objekat) : 
                        $maxParkinga = intval($objekat['broj_parkinga']);
                        $parkingCrud = new CRUD($_SESSION['godina']);
                        $parkingCrud->table = "parking_mesta";
                        $trenutniParking = $parkingCrud->select(['id'], ['objekat_id' => $objekat['id']]);
                        $trenutniBroj = count($trenutniParking);
                        ?>
                        <option value="<?= $objekat['id'] ?>"><?= $objekat['naziv'] ?> (<?= $trenutniBroj . '/' . $maxParkinga ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="naziv" class="col-form-label"><i class="fas fa-tag"></i> Naziv parking mesta</label>
                    <input class="form-control" id="naziv" name="naziv" type="text" maxlength="100" required placeholder="npr. P1, P2, P3, ...">
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="cena" class="col-form-label"><i class="fas fa-euro-sign"></i> Cena (€)</label>
                    <input class="form-control" id="cena" name="cena" type="number" step="0.01" min="0" required>
                  </div>
                </div>
                
                <div class="form-group row">
                  <div class="col-md-12 col-sm-12 mb-3">
                    <label for="napomena" class="col-form-label"><i class="fas fa-sticky-note"></i> Napomena</label>
                    <input class="form-control" id="napomena" name="napomena" type="text" maxlength="250" placeholder="Opcionalno">
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


