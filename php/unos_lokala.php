<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada forme za unos lokala
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['objekat_id'])) {
  try {
    // Validacija naziva i jedinstvenosti po objektu
    $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';
    if ($naziv === '') {
      $_SESSION['poruka'] = "Greška: Naziv lokala je obavezan.";
    } else {
      // Proverava da li objekat ima dovoljno mesta za lokale
      $objCrud = new CRUD($_SESSION['godina']);
      $objCrud->table = "objekti";
      $obj = $objCrud->select(['broj_lokala'], ['id' => $_POST['objekat_id']]);
      
      if (empty($obj)) {
        $_SESSION['poruka'] = "Greška: Objekat ne postoji.";
      } else {
        $brojLokala = intval($obj[0]['broj_lokala']);
        
        if ($brojLokala > 0) {
          // Broji postojeće lokale za ovaj objekat
          $podatci = new CRUD($_SESSION['godina']);
          $podatci->table = "lokali";
          $trenutniBrojLokala = $podatci->select(['COUNT(id) as count'], ['objekat_id' => $_POST['objekat_id']]);
          
          if ($trenutniBrojLokala[0]['count'] >= $brojLokala) {
            $_SESSION['poruka'] = "Greška: Dostignut je maksimalan broj lokala za ovaj objekat.";
          } else {
            // Proverava duplikat naziva
            $check = new CRUD($_SESSION['godina']);
            $check->table = "lokali";
            $duplikat = $check->select(['id'], ['objekat_id' => $_POST['objekat_id'], 'naziv' => $naziv]);
            
            if (!empty($duplikat)) {
              $_SESSION['poruka'] = "Greška: Već postoji lokal sa tim nazivom u izabranom objektu.";
            } else {
              $data = [
                'objekat_id' => $_POST['objekat_id'],
                'naziv' => $naziv,
                'kvadratura' => $_POST['kvadratura'],
                'cena_po_m2' => $_POST['cena_po_m2'],
                'ukupna_cena' => $_POST['ukupna_cena'] ?? 0,
                'pdv_suma' => $_POST['pdv'] ?? 0
              ];

              $result = $podatci->insert($data);

              if ($result) {
                $_SESSION['poruka'] = "Lokal je uspešno dodat!";
              } else {
                $_SESSION['poruka'] = "Greška pri dodavanju lokala!";
              }
            }
          }
        } else {
          // Ako je broj_lokala 0 ili NULL, dozvoljava neograničen broj lokala
          $check = new CRUD($_SESSION['godina']);
          $check->table = "lokali";
          $duplikat = $check->select(['id'], ['objekat_id' => $_POST['objekat_id'], 'naziv' => $naziv]);
          
          if (!empty($duplikat)) {
            $_SESSION['poruka'] = "Greška: Već postoji lokal sa tim nazivom u izabranom objektu.";
          } else {
            $podatci = new CRUD($_SESSION['godina']);
            $podatci->table = "lokali";

            $data = [
              'objekat_id' => $_POST['objekat_id'],
              'naziv' => $naziv,
              'kvadratura' => $_POST['kvadratura'],
              'cena_po_m2' => $_POST['cena_po_m2'],
              'ukupna_cena' => $_POST['ukupna_cena'] ?? 0,
              'pdv_suma' => $_POST['pdv'] ?? 0
            ];

            $result = $podatci->insert($data);

            if ($result) {
              $_SESSION['poruka'] = "Lokal je uspešno dodat!";
            } else {
              $_SESSION['poruka'] = "Greška pri dodavanju lokala!";
            }
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


  <title>GP RAZ - Lokal - Unos</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    
    // Računanje PDV-a iz procenta
    function calculatePDVFromPercentage() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2SaPDV = parseFloat($('#cena_po_m2').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
      
      if (kvadratura === 0) return;
      
      // Ukupna cena sa PDV
      const ukupnaCenaSaPDV = kvadratura * cenaPoM2SaPDV;
      
      // Osnovna cena bez PDV
      const osnovnaCenaBezPDV = ukupnaCenaSaPDV / (1 + pdvProcenat/100);
      
      // PDV suma
      const pdvSuma = ukupnaCenaSaPDV - osnovnaCenaBezPDV;
      
      // Cena po m² bez PDV
      const cenaPoM2BezPDV = osnovnaCenaBezPDV / kvadratura;
      
      $('#pdv').val(pdvSuma.toFixed(2));
      $('#osnovna_cena').val(cenaPoM2BezPDV.toFixed(2));
      $('#ukupna_cena').val(ukupnaCenaSaPDV.toFixed(2));
    }
    
    // Računanje PDV procenta iz apsolutne vrednosti
    function calculatePDVPercentage() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2SaPDV = parseFloat($('#cena_po_m2').val()) || 0;
      const pdvSuma = parseFloat($('#pdv').val()) || 0;
      
      if (kvadratura === 0) return;
      
      const ukupnaCenaSaPDV = kvadratura * cenaPoM2SaPDV;
      const osnovnaCenaBezPDV = ukupnaCenaSaPDV - pdvSuma;
      
      // Izračunaj PDV procenat
      const pdvProcenat = osnovnaCenaBezPDV > 0 ? (pdvSuma / osnovnaCenaBezPDV) * 100 : 0;
      
      // Cena po m² bez PDV
      const cenaPoM2BezPDV = osnovnaCenaBezPDV / kvadratura;
      
      $('#pdv_procenat').val(pdvProcenat.toFixed(2));
      $('#osnovna_cena').val(cenaPoM2BezPDV.toFixed(2));
      $('#ukupna_cena').val(ukupnaCenaSaPDV.toFixed(2));
    }
    
    // Računanje na osnovu cene po m² bez PDV
    function calculateFromPriceWithoutPDV() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2BezPDV = parseFloat($('#osnovna_cena').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
      
      if (kvadratura === 0) return;
      
      // Osnovna cena (ukupna bez PDV)
      const osnovnaCenaUkupno = kvadratura * cenaPoM2BezPDV;
      
      // PDV suma
      const pdvSuma = (osnovnaCenaUkupno * pdvProcenat) / 100;
      
      // Ukupna cena sa PDV
      const ukupnaCenaSaPDV = osnovnaCenaUkupno + pdvSuma;
      
      // Cena po m² sa PDV
      const cenaPoM2SaPDV = ukupnaCenaSaPDV / kvadratura;
      
      $('#cena_po_m2').val(cenaPoM2SaPDV.toFixed(2));
      $('#pdv').val(pdvSuma.toFixed(2));
      $('#ukupna_cena').val(ukupnaCenaSaPDV.toFixed(2));
    }
    
    // Funkcija za generisanje sledećeg naziva lokala
    function generateNextLokalName() {
      const objekatId = $('#objekat_id').val();
      if (objekatId) {
        $.ajax({
          url: 'get_next_lokal_name.php',
          method: 'POST',
          data: { objekat_id: objekatId },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              $('#naziv').val(response.nextName);
            }
          },
          error: function() {
            console.log('Greška pri učitavanju sledećeg naziva lokala');
          }
        });
      }
    }

    // Funkcija za onemogućavanje/omogućavanje polja u drugoj sekciji
    function toggleSecondSectionFields() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const isEnabled = kvadratura > 0;
      
      // Polja u drugoj sekciji (Cene i kalkulacije)
      const secondSectionFields = [
        '#cena_po_m2', '#pdv_procenat', '#pdv', '#osnovna_cena', '#ukupna_cena'
      ];
      
      // Onemogući/omogući polja
      secondSectionFields.forEach(function(fieldId) {
        $(fieldId).prop('disabled', !isEnabled);
        if (isEnabled) {
          $(fieldId).removeClass('disabled-field');
        } else {
          $(fieldId).addClass('disabled-field');
        }
      });
      
      // Onemogući/omogući celu sekciju
      const secondSectionCard = $('.card').eq(1); // Druga kartica (Cene i kalkulacije)
      if (isEnabled) {
        secondSectionCard.removeClass('section-disabled');
      } else {
        secondSectionCard.addClass('section-disabled');
      }
    }

    $(document).ready(function () {
      // Inicijalno onemogući polja u drugoj sekciji
      toggleSecondSectionFields();
      
      // Inicijalno izračunaj sve ako postoje vrednosti
      if ($('#pdv_procenat').val() && $('#kvadratura').val() && $('#cena_po_m2').val()) {
        calculatePDVFromPercentage();
      }
      
      // Event listener za kvadraturu - omogući/onemogući polja u drugoj sekciji
      $('#kvadratura').on('input', function() {
        toggleSecondSectionFields();
      });
      
      // Pozovi kalkulaciju pri promeni bilo kog od ovih polja
      $('#kvadratura, #cena_po_m2').on('input', calculatePDVFromPercentage);
      
      // PDV procenat - računanje apsolutne vrednosti
      $('#pdv_procenat').on('input', calculatePDVFromPercentage);
      
      // PDV apsolutna vrednost - računanje procenta
      $('#pdv').on('input', calculatePDVPercentage);
      
      // Cena po m² bez PDV - bidirekcioni unos
      $('#osnovna_cena').on('input', calculateFromPriceWithoutPDV);
      
      // Generiši sledeći naziv kada se promeni objekat
      $('#objekat_id').on('change', generateNextLokalName);
    });
  </script>

  <style>
    /* Stilovi za onemogućena polja */
    .disabled-field {
      background-color: #f8f9fa !important;
      color: #6c757d !important;
      cursor: not-allowed !important;
      opacity: 0.6;
    }
    
    .disabled-field:focus {
      box-shadow: none !important;
      border-color: #ced4da !important;
    }
    
    /* Stil za sekciju koja je onemogućena */
    .section-disabled {
      pointer-events: none;
      opacity: 0.6;
    }
  </style>

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

          <h3 class="center sekcija">Kreiranje novog lokala <i class="fas fa-store"></i></h3> <br>

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

            <!-- Osnovne informacije o lokalu -->
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-store"></i> Osnovne informacije o lokalu</h5>
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
                      $objekti = $podatci->select(['*'], [], "SELECT o.* FROM objekti o WHERE COALESCE(o.broj_lokala, 0) > (SELECT COUNT(*) FROM lokali l WHERE l.objekat_id = o.id) ORDER BY o.naziv ASC");
                      foreach ($objekti as $objekat) : 
                        $maxLokala = intval($objekat['broj_lokala']);
                        $lokalCrud = new CRUD($_SESSION['godina']);
                        $lokalCrud->table = "lokali";
                        $trenutniLokali = $lokalCrud->select(['id'], ['objekat_id' => $objekat['id']]);
                        $trenutniBroj = count($trenutniLokali);
                        ?>
                        <option value="<?= $objekat['id'] ?>"><?= $objekat['naziv'] ?> (<?= $trenutniBroj . '/' . $maxLokala ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="naziv" class="col-form-label"><i class="fas fa-tag"></i> Naziv lokala</label>
                    <input class="form-control" id="naziv" name="naziv" type="text" maxlength="100" required placeholder="npr. L1, L2, L3, ...">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="kvadratura" class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                    <input class="form-control" id="kvadratura" name="kvadratura" type="number" step="0.01" min="0" required>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cene i kalkulacije -->
            <div class="card mb-4">
              <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-calculator"></i> Cene i kalkulacije</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="cena_po_m2" class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² sa PDV (€)</label>
                    <input class="form-control" id="cena_po_m2" name="cena_po_m2" type="number" step="0.01" min="0" required>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="pdv_procenat" class="col-form-label"><i class="fas fa-percentage"></i> PDV PROCENT %</label>
                    <input class="form-control" id="pdv_procenat" name="pdv_procenat" type="number" step="0.01" min="0" max="100" value="20">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="pdv" class="col-form-label"><i class="fas fa-percentage"></i> PDV SUMA €</label>
                    <input class="form-control" id="pdv" name="pdv" type="number" step="0.01" min="0" value="0">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="osnovna_cena" class="col-form-label"><i class="fas fa-calculator"></i> Cena po m² bez PDV (€)</label>
                    <input class="form-control" id="osnovna_cena" name="osnovna_cena" type="number" step="0.01" min="0">
                  </div>
                </div>
                
                <div class="form-group row">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="ukupna_cena" class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                    <input class="form-control" id="ukupna_cena" name="ukupna_cena" type="number" step="0.01" min="0" readonly>
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


