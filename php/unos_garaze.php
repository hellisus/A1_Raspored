<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada forme za unos garaže
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['objekat_id'])) {
  try {
    // Provera i priprema naziva
    $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';
    if ($naziv === '') {
      $_SESSION['poruka'] = "Greška: Naziv je obavezan.";
    } else {
      // Provera limita broja garaža za objekat
      $objCrud = new CRUD($_SESSION['godina']);
      $objCrud->table = "objekti";
      $obj = $objCrud->select(['*'], ['id' => $_POST['objekat_id']]);
      if (!empty($obj)) {
        $maxGaraza = intval($obj[0]['broj_garaza']);
      } else {
        $maxGaraza = 0;
      }

      if ($maxGaraza > 0) {
        $cntCrud = new CRUD($_SESSION['godina']);
        $cntCrud->table = "garaze";
        $postojecih = $cntCrud->select(['id'], ['objekat_id' => $_POST['objekat_id']]);
        if (count($postojecih) >= $maxGaraza) {
          $_SESSION['poruka'] = "Greška: Dostignut je maksimalan broj garaža za izabrani objekat.";
          // prekid bez inserta
          goto render_page;
        }
      }

      // Provera duplikata naziva za isti objekat
      $check = new CRUD($_SESSION['godina']);
      $check->table = "garaze";
      $duplikat = $check->select(['id'], ['objekat_id' => $_POST['objekat_id'], 'naziv' => $naziv]);
      if (!empty($duplikat)) {
        $_SESSION['poruka'] = "Greška: Već postoji garaža sa tim nazivom u izabranom objektu.";
      } else {
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "garaze";

        $data = [
          'objekat_id' => $_POST['objekat_id'],
          'naziv' => $naziv,
          'cena_sa_pdv' => $_POST['cena_sa_pdv']
        ];

        $result = $podatci->insert($data);

        if ($result) {
          $_SESSION['poruka'] = "Garaža je uspešno dodata!";
        } else {
          $_SESSION['poruka'] = "Greška pri dodavanju garaže!";
        }
      }
    }
  } catch (Exception $e) {
    $_SESSION['poruka'] = "Greška: " . $e->getMessage();
  }
}
render_page:
?>


<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />


  <title>GP RAZ - Garaža - Unos</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>

    // Funkcija za generisanje sledećeg naziva garaže
    function generateNextGarazaName() {
      const objekatId = $('#objekat_id').val();
      if (objekatId) {
        $.ajax({
          url: 'get_next_garaza_name.php',
          method: 'POST',
          data: { objekat_id: objekatId },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              $('#naziv').val(response.nextName);
            }
          },
          error: function() {
            console.log('Greška pri učitavanju sledećeg naziva garaže');
          }
        });
      }
    }

    $(document).ready(function () {
      // Generiši sledeći naziv kada se promeni objekat
      $('#objekat_id').on('change', generateNextGarazaName);
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

          <h3 class="center sekcija">Kreiranje nove garaže <i class="fas fa-warehouse"></i></h3> <br>

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

            <!-- Osnovne informacije o garaži -->
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-car"></i> Osnovne informacije o garaži</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="objekat_id" class="col-form-label"><i class="far fa-building"></i> Objekat</label>
                    <select class="form-control" id="objekat_id" name="objekat_id" required>
                      <option value="">Izaberite objekat</option>
                      <?php
                      $podatci = new CRUD($_SESSION['godina']);
                      $podatci->table = "objekti";
                      $objekti = $podatci->select(['*'], [], "SELECT * FROM objekti ORDER BY naziv ASC");
                      
                      foreach ($objekti as $objekat) : 
                        // Proveri da li objekat može da prima nove garaže
                        $maxGaraza = intval($objekat['broj_garaza']);
                        $dostupan = false;
                        
                        if ($maxGaraza > 0) {
                          // Ako je broj_garaza > 0, proveri da li ima mesta
                          $cntCrud = new CRUD($_SESSION['godina']);
                          $cntCrud->table = "garaze";
                          $postojecih = $cntCrud->select(['id'], ['objekat_id' => $objekat['id']]);
                          $trenutniBroj = count($postojecih);
                          $dostupan = $trenutniBroj < $maxGaraza;
                        } else {
                          // Ako je broj_garaza = 0 ili NULL, objekat nema definisan broj garaža
                          $dostupan = false;
                        }
                        
                        if ($dostupan) : ?>
                          <option value="<?= $objekat['id'] ?>"><?= $objekat['naziv'] ?> (<?= $trenutniBroj . '/' . $maxGaraza ?>)</option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="naziv" class="col-form-label"><i class="fas fa-tag"></i> Naziv garaže</label>
                    <input class="form-control" id="naziv" name="naziv" type="text" maxlength="100" required placeholder="npr. G1, G2, G3, ...">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="cena_sa_pdv" class="col-form-label"><i class="fas fa-calculator"></i> Cena sa PDV (€)</label>
                    <input class="form-control" id="cena_sa_pdv" name="cena_sa_pdv" type="number" step="0.01" min="0" required>
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


