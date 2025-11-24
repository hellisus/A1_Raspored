<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada forme za unos kupca
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  try {
    $ime = isset($_POST['ime']) ? trim($_POST['ime']) : '';
    $prezime = isset($_POST['prezime']) ? trim($_POST['prezime']) : null;
    $br_telefona = isset($_POST['br_telefona']) ? trim($_POST['br_telefona']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $tip_kupca_id = isset($_POST['tip_kupca_id']) && $_POST['tip_kupca_id'] !== '' ? $_POST['tip_kupca_id'] : null;
    $napomena = isset($_POST['napomena']) ? trim($_POST['napomena']) : null;

    if ($ime === '' || $br_telefona === '' || $tip_kupca_id === null) {
      $_SESSION['poruka'] = "Greška: Ime, broj telefona i tip kupca su obavezni.";
    } else {
      $crud = new CRUD($_SESSION['godina']);
      $crud->table = "kupci";

      // Provera duplikata: isto ime i prezime već postoje
      $postoji = $crud->select(['id'], ['ime' => $ime, 'prezime' => $prezime]);
      if (!empty($postoji)) {
        $_SESSION['poruka'] = "Greška: Kupac sa istim imenom i prezimenom već postoji.";
      } else {
        $result = $crud->insert([
          'ime' => $ime,
          'prezime' => $prezime,
          'br_telefona' => $br_telefona,
          'email' => $email,
          'tip_kupca_id' => $tip_kupca_id,
          'napomena' => $napomena,
        ]);

        if ($result) {
          $kupac_id = $result; // ID novog kupca
          $_SESSION['poruka'] = "Kupac je uspešno dodat!";
          
          // Obrada prodaje objekta ako je uneta
          $konkretan_objekat_id = isset($_POST['konkretan_objekat_id']) && $_POST['konkretan_objekat_id'] !== '' ? $_POST['konkretan_objekat_id'] : null;
          $tip_objekta = isset($_POST['tip_objekta']) && $_POST['tip_objekta'] !== '' ? $_POST['tip_objekta'] : null;
          $datum_ugovora = isset($_POST['datum_ugovora']) && $_POST['datum_ugovora'] !== '' ? $_POST['datum_ugovora'] : null;
          $datum_predugovora = isset($_POST['datum_predugovora']) && $_POST['datum_predugovora'] !== '' ? $_POST['datum_predugovora'] : null;
          
          if ($konkretan_objekat_id && $tip_objekta) {
            // Pripremi podatke o cenama
            $cene_data = [];
            if ($tip_objekta === 'stan') {
              $cene_data = [
                'cena_po_m2' => isset($_POST['cene_cena_po_m2']) && $_POST['cene_cena_po_m2'] !== '' ? $_POST['cene_cena_po_m2'] : null,
                'realna_cena_po_m2' => isset($_POST['cene_realna_cena_po_m2']) && $_POST['cene_realna_cena_po_m2'] !== '' ? $_POST['cene_realna_cena_po_m2'] : null,
                'pdv' => isset($_POST['cene_pdv']) && $_POST['cene_pdv'] !== '' ? $_POST['cene_pdv'] : null,
                'rabat' => isset($_POST['cene_rabat']) && $_POST['cene_rabat'] !== '' ? $_POST['cene_rabat'] : null,
                'ukupna_cena' => isset($_POST['cene_ukupna_cena']) && $_POST['cene_ukupna_cena'] !== '' ? $_POST['cene_ukupna_cena'] : null
              ];
            } elseif ($tip_objekta === 'lokal') {
              $cene_data = [
                'cena_po_m2' => isset($_POST['cene_lokal_cena_po_m2']) && $_POST['cene_lokal_cena_po_m2'] !== '' ? $_POST['cene_lokal_cena_po_m2'] : null,
                'pdv_suma' => isset($_POST['cene_lokal_pdv']) && $_POST['cene_lokal_pdv'] !== '' ? $_POST['cene_lokal_pdv'] : null,
                'ukupna_cena' => isset($_POST['cene_lokal_ukupna_cena']) && $_POST['cene_lokal_ukupna_cena'] !== '' ? $_POST['cene_lokal_ukupna_cena'] : null,
                'rabat' => isset($_POST['cene_lokal_rabat']) && $_POST['cene_lokal_rabat'] !== '' ? $_POST['cene_lokal_rabat'] : null
              ];
            } elseif ($tip_objekta === 'garaza') {
              $cene_data = [
                'cena_sa_pdv' => isset($_POST['cene_garaza_cena_sa_pdv']) && $_POST['cene_garaza_cena_sa_pdv'] !== '' ? $_POST['cene_garaza_cena_sa_pdv'] : null,
                'cena' => isset($_POST['cene_garaza_cena']) && $_POST['cene_garaza_cena'] !== '' ? $_POST['cene_garaza_cena'] : null,
                'pdv' => isset($_POST['cene_garaza_pdv']) && $_POST['cene_garaza_pdv'] !== '' ? $_POST['cene_garaza_pdv'] : null
              ];
            } elseif ($tip_objekta === 'parking') {
              $cene_data = [
                'cena' => isset($_POST['cene_parking_cena']) && $_POST['cene_parking_cena'] !== '' ? $_POST['cene_parking_cena'] : null
              ];
            }
            
            // Obradi glavnu prodaju sa cenama
            obradiProdajuKonkretno($kupac_id, $konkretan_objekat_id, $tip_objekta, $datum_ugovora, $datum_predugovora, $cene_data);
            
                         // Obradi dodatne prodaje
             $i = 1;
             while (isset($_POST["konkretan_objekat_id_$i"])) {
               $dodatni_konkretan_objekat_id = $_POST["konkretan_objekat_id_$i"];
               $dodatni_tip_objekta = $_POST["tip_objekta_$i"];
               $dodatni_datum_ugovora = isset($_POST["datum_ugovora_$i"]) && $_POST["datum_ugovora_$i"] !== '' ? $_POST["datum_ugovora_$i"] : null;
               $dodatni_datum_predugovora = isset($_POST["datum_predugovora_$i"]) && $_POST["datum_predugovora_$i"] !== '' ? $_POST["datum_predugovora_$i"] : null;
               
               if ($dodatni_konkretan_objekat_id && $dodatni_tip_objekta) {
                 // Pripremi podatke o cenama za dodatnu prodaju
                 $dodatne_cene_data = [];
                 if ($dodatni_tip_objekta === 'stan') {
                   $dodatne_cene_data = [
                     'cena_po_m2' => isset($_POST["cene_cena_po_m2_$i"]) && $_POST["cene_cena_po_m2_$i"] !== '' ? $_POST["cene_cena_po_m2_$i"] : null,
                     'realna_cena_po_m2' => isset($_POST["cene_realna_cena_po_m2_$i"]) && $_POST["cene_realna_cena_po_m2_$i"] !== '' ? $_POST["cene_realna_cena_po_m2_$i"] : null,
                     'pdv' => isset($_POST["cene_pdv_$i"]) && $_POST["cene_pdv_$i"] !== '' ? $_POST["cene_pdv_$i"] : null,
                     'rabat' => isset($_POST["cene_rabat_$i"]) && $_POST["cene_rabat_$i"] !== '' ? $_POST["cene_rabat_$i"] : null,
                     'ukupna_cena' => isset($_POST["cene_ukupna_cena_$i"]) && $_POST["cene_ukupna_cena_$i"] !== '' ? $_POST["cene_ukupna_cena_$i"] : null
                   ];
                 } elseif ($dodatni_tip_objekta === 'lokal') {
                   $dodatne_cene_data = [
                     'cena_po_m2' => isset($_POST["cene_lokal_cena_po_m2_$i"]) && $_POST["cene_lokal_cena_po_m2_$i"] !== '' ? $_POST["cene_lokal_cena_po_m2_$i"] : null,
                     'pdv_suma' => isset($_POST["cene_lokal_pdv_$i"]) && $_POST["cene_lokal_pdv_$i"] !== '' ? $_POST["cene_lokal_pdv_$i"] : null,
                     'ukupna_cena' => isset($_POST["cene_lokal_ukupna_cena_$i"]) && $_POST["cene_lokal_ukupna_cena_$i"] !== '' ? $_POST["cene_lokal_ukupna_cena_$i"] : null,
                     'rabat' => isset($_POST["cene_lokal_rabat_$i"]) && $_POST["cene_lokal_rabat_$i"] !== '' ? $_POST["cene_lokal_rabat_$i"] : null
                   ];
                 } elseif ($dodatni_tip_objekta === 'garaza') {
                   $dodatne_cene_data = [
                     'cena_sa_pdv' => isset($_POST["cene_garaza_cena_sa_pdv_$i"]) && $_POST["cene_garaza_cena_sa_pdv_$i"] !== '' ? $_POST["cene_garaza_cena_sa_pdv_$i"] : null,
                     'cena' => isset($_POST["cene_garaza_cena_$i"]) && $_POST["cene_garaza_cena_$i"] !== '' ? $_POST["cene_garaza_cena_$i"] : null,
                     'pdv' => isset($_POST["cene_garaza_pdv_$i"]) && $_POST["cene_garaza_pdv_$i"] !== '' ? $_POST["cene_garaza_pdv_$i"] : null
                   ];
                 } elseif ($dodatni_tip_objekta === 'parking') {
                   $dodatne_cene_data = [
                     'cena' => isset($_POST["cene_parking_cena_$i"]) && $_POST["cene_parking_cena_$i"] !== '' ? $_POST["cene_parking_cena_$i"] : null
                   ];
                 }
                 obradiProdajuKonkretno($kupac_id, $dodatni_konkretan_objekat_id, $dodatni_tip_objekta, $dodatni_datum_ugovora, $dodatni_datum_predugovora, $dodatne_cene_data);
               }
               $i++;
             }
          }

          header('Location: kupci_detaljno.php?id=' . $kupac_id);
          exit;
        } else {
          $_SESSION['poruka'] = "Greška pri dodavanju kupca!";
        }
      }
    }
  } catch (Exception $e) {
    $_SESSION['poruka'] = "Greška: " . $e->getMessage();
  }
}

// Funkcija za obradu prodaje konkretnog objekta
function obradiProdajuKonkretno($kupac_id, $konkretan_objekat_id, $tip_objekta, $datum_ugovora, $datum_predugovora, $cene_data = []) {
  try {
    $crud = new CRUD($_SESSION['godina']);
    
    // Odredi tabelu na osnovu tipa objekta
    $tabela = '';
    switch ($tip_objekta) {
      case 'stan':
        $tabela = 'stanovi';
        break;
      case 'garaza':
        $tabela = 'garaze';
        break;
      case 'lokal':
        $tabela = 'lokali';
        break;
      case 'parking':
        $tabela = 'parking_mesta';
        break;
      default:
        return false;
    }
    
    $crud->table = $tabela;
    
    // Proveri da li je objekat slobodan
    $objekat = $crud->select(['id', 'prodat'], ['id' => $konkretan_objekat_id]);
    
    if (empty($objekat)) {
      $_SESSION['poruka'] .= " Greška: Objekat nije pronađen!";
      return false;
    }
    
    if ($objekat[0]['prodat'] == 1) {
      $_SESSION['poruka'] .= " Greška: Objekat je već prodat!";
      return false;
    }
    
    // Ažuriraj objekat sa podacima o kupcu
    $update_data = [
      'kupac_id' => $kupac_id,
      'prodat' => 1,
      'datum_prodaje' => $datum_ugovora
    ];
    
    // Dodaj datum_predugovora samo za stanove
    if ($tip_objekta === 'stan' && $datum_predugovora) {
      $update_data['datum_predugovora'] = $datum_predugovora;
    }
    
    // Dodaj podatke o cenama ako su prosleđeni
    if (!empty($cene_data)) {
      if ($tip_objekta === 'stan') {
        if (isset($cene_data['cena_po_m2']) && $cene_data['cena_po_m2'] !== null) {
          $update_data['cena_po_m2'] = $cene_data['cena_po_m2'];
        }
        if (isset($cene_data['realna_cena_po_m2']) && $cene_data['realna_cena_po_m2'] !== null) {
          $update_data['realna_cena_po_m2'] = $cene_data['realna_cena_po_m2'];
        }
        if (isset($cene_data['pdv']) && $cene_data['pdv'] !== null) {
          $update_data['pdv'] = $cene_data['pdv'];
        }
        if (isset($cene_data['rabat']) && $cene_data['rabat'] !== null) {
          $update_data['rabat'] = $cene_data['rabat'];
        }
        if (isset($cene_data['ukupna_cena']) && $cene_data['ukupna_cena'] !== null) {
          $update_data['ukupna_cena'] = $cene_data['ukupna_cena'];
        }
      } elseif ($tip_objekta === 'lokal') {
        if (isset($cene_data['cena_po_m2']) && $cene_data['cena_po_m2'] !== null) {
          $update_data['cena_po_m2'] = $cene_data['cena_po_m2'];
        }
        if (isset($cene_data['pdv_suma']) && $cene_data['pdv_suma'] !== null) {
          $update_data['pdv_suma'] = $cene_data['pdv_suma'];
        }
        if (isset($cene_data['ukupna_cena']) && $cene_data['ukupna_cena'] !== null) {
          $update_data['ukupna_cena'] = $cene_data['ukupna_cena'];
        }
        if (isset($cene_data['rabat']) && $cene_data['rabat'] !== null) {
          $update_data['rabat'] = $cene_data['rabat'];
        }
      } elseif ($tip_objekta === 'garaza') {
        if (isset($cene_data['cena_sa_pdv']) && $cene_data['cena_sa_pdv'] !== null) {
          $update_data['cena_sa_pdv'] = $cene_data['cena_sa_pdv'];
        }
        if (isset($cene_data['cena']) && $cene_data['cena'] !== null) {
          $update_data['cena'] = $cene_data['cena'];
        }
        if (isset($cene_data['pdv']) && $cene_data['pdv'] !== null) {
          $update_data['pdv'] = $cene_data['pdv'];
        }
      } elseif ($tip_objekta === 'parking') {
        if (isset($cene_data['cena']) && $cene_data['cena'] !== null) {
          $update_data['cena'] = $cene_data['cena'];
        }
      }
    }
    
    $crud->update($update_data, ['id' => $konkretan_objekat_id]);
    
    // Proveri da li je ažuriranje stvarno uspešno
    $proveri = $crud->select(['prodat', 'kupac_id', 'datum_prodaje'], ['id' => $konkretan_objekat_id]);
    
    if (!empty($proveri) && $proveri[0]['prodat'] == 1 && $proveri[0]['kupac_id'] == $kupac_id) {
      $poruka = " Prodaja " . $tip_objekta . " je uspešno zabeležena!";
      if (!empty($cene_data)) {
        $poruka .= " Cene su ažurirane.";
      }
      $_SESSION['poruka'] .= $poruka;

      if ($tip_objekta === 'stan') {
        $rabatIznos = null;
        if (isset($cene_data['rabat']) && $cene_data['rabat'] !== null && $cene_data['rabat'] !== '') {
          $rabatIznos = (float)$cene_data['rabat'];
        } elseif (isset($update_data['rabat'])) {
          $rabatIznos = (float)$update_data['rabat'];
        }

        if ($rabatIznos !== null && $rabatIznos > 0) {
          $rabatIznos = round($rabatIznos, 2);
          $uplataCrud = new CRUD($_SESSION['godina']);
          $uplataCrud->table = "uplata";
          $srednjiKursRabat = isset($_SESSION['euro']) ? floatval($_SESSION['euro']) : null;
          $rabatVrednostRSD = ($srednjiKursRabat && $srednjiKursRabat > 0) ? round($rabatIznos * $srednjiKursRabat, 2) : null;

          $postojiRabatUplata = $uplataCrud->select(
            ['id'],
            [
              'id_kupca' => $kupac_id,
              'tip_jedinice' => 'stan',
              'id_jedinice' => $konkretan_objekat_id,
              'kes' => 1,
              'iznos_uplate' => $rabatIznos
            ]
          );

          if (empty($postojiRabatUplata)) {
            $uplataCrud->insert([
              'id_kupca' => $kupac_id,
              'datum_uplate' => $datum_ugovora ?? date('Y-m-d'),
              'trenutna_vrednost_eura' => $_SESSION['euro'] ?? 0,
              'srednji_kurs' => $srednjiKursRabat,
              'iznos_uplate' => $rabatIznos,
              'tip_jedinice' => 'stan',
              'id_jedinice' => $konkretan_objekat_id,
              'kes' => 1,
              'vrednost_u_dinarima' => $rabatVrednostRSD
            ]);
          }
        }
      }

      return true;
    } else {
      $_SESSION['poruka'] .= " Greška pri prodaji " . $tip_objekta . "!";
      return false;
    }
    
  } catch (Exception $e) {
    $_SESSION['poruka'] .= " Greška pri prodaji: " . $e->getMessage();
    return false;
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


  <title>GP RAZ - Kupci - Unos</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
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

          <h3 class="center sekcija">Unos novog kupca <i class="fas fa-user"></i></h3> <br>

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

            <!-- Osnovne informacije o kupcu -->
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Osnovne informacije o kupcu</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="ime" class="col-form-label"><i class="far fa-user"></i> Ime</label>
                    <input class="form-control" id="ime" name="ime" type="text" maxlength="50" required>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="prezime" class="col-form-label"><i class="far fa-user"></i> Prezime</label>
                    <input class="form-control" id="prezime" name="prezime" type="text" maxlength="50">
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="br_telefona" class="col-form-label"><i class="fas fa-phone"></i> Broj telefona</label>
                    <input class="form-control" id="br_telefona" name="br_telefona" type="text" maxlength="50" required>
                  </div>
                </div>
                
                <div class="form-group row">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="email" class="col-form-label"><i class="fas fa-envelope"></i> Email</label>
                    <input class="form-control" id="email" name="email" type="email" maxlength="100" placeholder="Opcionalno">
                  </div>
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="tip_kupca_id" class="col-form-label"><i class="fas fa-tag"></i> Tip kupca</label>
                    <select class="form-control" id="tip_kupca_id" name="tip_kupca_id" required>

                      <?php
                      $tipCrud = new CRUD($_SESSION['godina']);
                      $tipCrud->table = "tip_kupca";
                      $tipovi = $tipCrud->select(['*'], [], "SELECT * FROM tip_kupca ORDER BY id_tipa_kupca ASC");
                      $defaultSet = false;
                      foreach ($tipovi as $tip) : 
                        $isSelected = ($tip['id_tipa_kupca'] == 2 && !$defaultSet) ? 'selected' : '';
                        if ($isSelected) $defaultSet = true;
                      ?>
                        <option value="<?= $tip['id_tipa_kupca'] ?>" <?= $isSelected ?>><?= htmlspecialchars($tip['naziv']) ?></option>
                      <?php endforeach; ?>
                    </select>
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

            <!-- Sekcija za prodaju objekta -->
            <div class="card mb-4">
              <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-home"></i> Prodaja objekta (opciono)</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="objekat_id" class="col-form-label"><i class="fas fa-building"></i> Objekat</label>
                    <select class="form-control" id="objekat_id" name="objekat_id">
                      <option value="">Izaberite objekat</option>
                      <?php
                      $objekatCrud = new CRUD($_SESSION['godina']);
                      $objekatCrud->table = "objekti";
                      $objekti = $objekatCrud->select(['*'], [], "SELECT * FROM objekti ORDER BY naziv ASC");
                      foreach ($objekti as $objekat) : ?>
                        <option value="<?= $objekat['id'] ?>"><?= htmlspecialchars($objekat['naziv']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="tip_objekta" class="col-form-label"><i class="fas fa-tag"></i> Tip objekta</label>
                    <select class="form-control" id="tip_objekta" name="tip_objekta">
                      <option value="">Izaberite tip objekta</option>
                      <option value="stan">Stan</option>
                      <option value="garaza">Garaža</option>
                      <option value="lokal">Lokal</option>
                      <option value="parking">Parking mesto</option>
                    </select>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="konkretan_objekat_id" class="col-form-label"><i class="fas fa-home"></i> Konkretan objekat</label>
                    <select class="form-control" id="konkretan_objekat_id" name="konkretan_objekat_id" disabled>
                      <option value="">Prvo izaberite objekat i tip</option>
                    </select>
                  </div>
                </div>

                <div class="form-group row">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="datum_ugovora" class="col-form-label"><i class="fas fa-calendar"></i> Datum ugovora</label>
                    <input class="form-control" id="datum_ugovora" name="datum_ugovora" type="date">
                  </div>
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="datum_predugovora" class="col-form-label"><i class="fas fa-calendar-alt"></i> Datum predugovora</label>
                    <input class="form-control" id="datum_predugovora" name="datum_predugovora" type="date" disabled>
                    <small class="form-text text-muted">Dostupno samo za stanove</small>
                  </div>
                </div>

                <!-- Cene i kalkulacije - prikazuje se samo kada je izabran konkretan objekat -->
                <div id="cene_sekcija" style="display: none;">
                  <div class="card mt-3 mb-3" style="background-color: #f8f9fa;">
                    <div class="card-header bg-success text-white">
                      <h6 class="mb-0"><i class="fas fa-calculator"></i> Cene i kalkulacije</h6>
                    </div>
                    <div class="card-body">
                      <!-- Polja za stan -->
                      <div id="cene_stan" style="display: none;">
                        <div class="form-group row">
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                            <input class="form-control" id="cene_kvadratura" name="cene_kvadratura" type="number" step="0.01" min="0" readonly>
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-chart-line"></i> Cena po m² sa PDV (€)</label>
                            <input class="form-control" id="cene_realna_cena_po_m2" name="cene_realna_cena_po_m2" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² (€)</label>
                            <input class="form-control" id="cene_cena_po_m2" name="cene_cena_po_m2" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-percentage"></i> PDV PROCENT %</label>
                            <input class="form-control" id="cene_pdv_procenat" name="cene_pdv_procenat" type="number" step="0.01" min="0" max="100" value="10">
                          </div>
                        </div>
                        <div class="form-group row">
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-percentage"></i> PDV SUMA (€)</label>
                            <input class="form-control" id="cene_pdv" name="cene_pdv" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-tag"></i> Rabat (€)</label>
                            <input class="form-control" id="cene_rabat" name="cene_rabat" type="number" step="0.01" min="0" value="0">
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                            <input class="form-control" id="cene_ukupna_cena" name="cene_ukupna_cena" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-undo"></i> Povrat PDV (€)</label>
                            <input class="form-control" id="cene_povrat_pdv" name="cene_povrat_pdv" type="number" step="0.01" min="0" readonly>
                            <small class="form-text text-muted">Automatski se računa za prvih 40m²</small>
                          </div>
                        </div>
                      </div>

                      <!-- Polja za lokal -->
                      <div id="cene_lokal" style="display: none;">
                        <div class="form-group row">
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                            <input class="form-control" id="cene_lokal_kvadratura" name="cene_lokal_kvadratura" type="number" step="0.01" min="0" readonly>
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² sa PDV (€)</label>
                            <input class="form-control" id="cene_lokal_cena_po_m2" name="cene_lokal_cena_po_m2" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-percentage"></i> PDV PROCENT %</label>
                            <input class="form-control" id="cene_lokal_pdv_procenat" name="cene_lokal_pdv_procenat" type="number" step="0.01" min="0" max="100" value="20">
                          </div>
                          <div class="col-md-3 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-percentage"></i> PDV SUMA (€)</label>
                            <input class="form-control" id="cene_lokal_pdv" name="cene_lokal_pdv" type="number" step="0.01" min="0">
                          </div>
                        </div>
                        <div class="form-group row">
                          <div class="col-md-4 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-calculator"></i> Cena po m² bez PDV (€)</label>
                            <input class="form-control" id="cene_lokal_osnovna_cena" name="cene_lokal_osnovna_cena" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-4 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                            <input class="form-control" id="cene_lokal_ukupna_cena" name="cene_lokal_ukupna_cena" type="number" step="0.01" min="0" readonly>
                          </div>
                          <div class="col-md-4 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-tag"></i> Rabat (€)</label>
                            <input class="form-control" id="cene_lokal_rabat" name="cene_lokal_rabat" type="number" step="0.01" min="0" value="0">
                          </div>
                        </div>
                      </div>

                      <!-- Polja za garažu -->
                      <div id="cene_garaza" style="display: none;">
                        <div class="form-group row">
                          <div class="col-md-4 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena sa PDV (€)</label>
                            <input class="form-control" id="cene_garaza_cena_sa_pdv" name="cene_garaza_cena_sa_pdv" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-4 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena (€)</label>
                            <input class="form-control" id="cene_garaza_cena" name="cene_garaza_cena" type="number" step="0.01" min="0">
                          </div>
                          <div class="col-md-4 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-percentage"></i> PDV (€)</label>
                            <input class="form-control" id="cene_garaza_pdv" name="cene_garaza_pdv" type="number" step="0.01" min="0">
                          </div>
                        </div>
                      </div>

                      <!-- Polja za parking -->
                      <div id="cene_parking" style="display: none;">
                        <div class="form-group row">
                          <div class="col-md-6 col-sm-12 mb-3">
                            <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena (€)</label>
                            <input class="form-control" id="cene_parking_cena" name="cene_parking_cena" type="number" step="0.01" min="0">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Dinamičko dodavanje više prodaja -->
                <div id="dodatne_prodaje" class="mt-3">
                  <button type="button" class="btn btn-outline-primary btn-sm" id="dodaj_prodaju">
                    <i class="fas fa-plus"></i> Dodaj još jednu prodaju
                  </button>
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

     <script>
     function izracunajPovratPDVValue(kvadratura, cenaPoM2, pdvProcenat) {
       const kv = parseFloat(kvadratura) || 0;
       const cena = parseFloat(cenaPoM2) || 0;
       const procenat = parseFloat(pdvProcenat) || 0;
       if (kv <= 0 || cena <= 0 || procenat < 0) {
         return 0;
       }
       const kvZaPovrat = Math.min(kv, 40);
       return (kvZaPovrat * cena * procenat) / 100;
     }

     $(document).ready(function() {
       let prodajaCounter = 0;
       let isCalculating = false; // Flag za sprečavanje cikličnih poziva
       
       function sakrijSveTipoveCena() {
         $('#cene_stan, #cene_lokal, #cene_garaza, #cene_parking').hide();
       }
      
      // Učitaj konkretne objekte kada se promeni objekat ili tip
      $('#objekat_id, #tip_objekta').change(function() {
        ucitajKonkretneObjekte();
        togglePredugovorField();
      });
      
      // Omogući/onemogući polje za datum predugovora na osnovu tipa objekta
      function togglePredugovorField() {
        const tipObjekta = $('#tip_objekta').val();
        const predugovorField = $('#datum_predugovora');
        
        if (tipObjekta === 'stan') {
          predugovorField.prop('disabled', false);
          predugovorField.siblings('small').text('Dostupno za stanove');
        } else {
          predugovorField.prop('disabled', true);
          predugovorField.val(''); // Obriši vrednost
          predugovorField.siblings('small').text('Dostupno samo za stanove');
        }
      }
      
      function ucitajKonkretneObjekte() {
        const objekatId = $('#objekat_id').val();
        const tipObjekta = $('#tip_objekta').val();
        const konkretanSelect = $('#konkretan_objekat_id');
        
        if (objekatId && tipObjekta) {
          konkretanSelect.prop('disabled', false);
          konkretanSelect.html('<option value="">Učitavam...</option>');
          
          $.ajax({
            url: 'get_konkretni_objekti.php',
            method: 'POST',
            data: {
              objekat_id: objekatId,
              tip_objekta: tipObjekta
            },
            success: function(response) {
              konkretanSelect.html(response);
              // Resetuj sekciju cena kada se promeni lista
              $('#cene_sekcija').hide();
              sakrijSveTipoveCena();
            },
            error: function() {
              konkretanSelect.html('<option value="">Greška pri učitavanju</option>');
            }
          });
        } else {
          konkretanSelect.prop('disabled', true);
          konkretanSelect.html('<option value="">Prvo izaberite objekat i tip</option>');
          $('#cene_sekcija').hide();
          sakrijSveTipoveCena();
        }
      }
      
      // Učitaj podatke o cenama kada se izabere konkretan objekat
      $('#konkretan_objekat_id').on('change', function() {
        const konkretanObjekatId = $(this).val();
        const tipObjekta = $('#tip_objekta').val();
        
        if (konkretanObjekatId && tipObjekta) {
          // Učitaj podatke o cenama
          $.ajax({
            url: 'get_objekat_cene.php',
            method: 'POST',
            data: {
              tip_objekta: tipObjekta,
              objekat_id: konkretanObjekatId
            },
            dataType: 'json',
            success: function(response) {
              if (response.success) {
                // Prikaži sekciju cena
                $('#cene_sekcija').show();
                sakrijSveTipoveCena();
                
                // Popuni polja u zavisnosti od tipa
                if (response.tip === 'stan') {
                  $('#cene_stan').show();
                  $('#cene_kvadratura').val(response.kvadratura || '');
                  $('#cene_cena_po_m2').val(response.cena_po_m2 || '');
                  $('#cene_realna_cena_po_m2').val(response.realna_cena_po_m2 || '');
                  $('#cene_pdv_procenat').val(response.pdv_procenat || '10');
                  $('#cene_pdv').val(response.pdv || '');
                  $('#cene_rabat').val(response.rabat || '0');
                  $('#cene_ukupna_cena').val(response.ukupna_cena || '');
                  const povrat = izracunajPovratPDVValue(response.kvadratura, response.cena_po_m2, $('#cene_pdv_procenat').val());
                  $('#cene_povrat_pdv').val(povrat.toFixed(2));
                  calculateStanPDVFromPercentage();
                } else if (response.tip === 'lokal') {
                  $('#cene_lokal').show();
                  $('#cene_lokal_kvadratura').val(response.kvadratura || '');
                  $('#cene_lokal_cena_po_m2').val(response.cena_po_m2 || '');
                  $('#cene_lokal_pdv_procenat').val(response.pdv_procenat || '20');
                  $('#cene_lokal_pdv').val(response.pdv || '');
                  $('#cene_lokal_osnovna_cena').val(response.osnovna_cena || '');
                  $('#cene_lokal_ukupna_cena').val(response.ukupna_cena || '');
                  $('#cene_lokal_rabat').val(response.rabat || '0');
                } else if (response.tip === 'garaza') {
                  $('#cene_garaza').show();
                  $('#cene_garaza_cena_sa_pdv').val(response.cena_sa_pdv || '');
                  $('#cene_garaza_cena').val(response.cena || '');
                  $('#cene_garaza_pdv').val(response.pdv || '');
                } else if (response.tip === 'parking') {
                  $('#cene_parking').show();
                  $('#cene_parking_cena').val(response.cena || '');
                }
              } else {
                $('#cene_sekcija').hide();
                sakrijSveTipoveCena();
              }
            },
            error: function() {
                            $('#cene_sekcija').hide();
              sakrijSveTipoveCena();
            }
          });
        } else {
          $('#cene_sekcija').hide();
          sakrijSveTipoveCena();
        }
      });
      
             // Kalkulacije za stanove
       function calculateStanTotalPrice() {
         if (isCalculating) return;
         isCalculating = true;
         
         const kvadratura = parseFloat($('#cene_kvadratura').val()) || 0;
         const cenaPoM2 = parseFloat($('#cene_cena_po_m2').val()) || 0;
         const pdv = parseFloat($('#cene_pdv').val()) || 0;
         const rabat = parseFloat($('#cene_rabat').val()) || 0;
         
        const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
         const ukupnaCena = osnovnaCena + pdv;
         
         $('#cene_ukupna_cena').val(ukupnaCena.toFixed(2));
         
         if (kvadratura > 0) {
           const realnaCena = ukupnaCena / kvadratura;
           $('#cene_realna_cena_po_m2').val(realnaCena.toFixed(2));
         }
         
         calculateStanPovratPDV();
         isCalculating = false;
       }
      
             function calculateStanPDVFromPercentage() {
         if (isCalculating) return;
         isCalculating = true;
         
         const kvadratura = parseFloat($('#cene_kvadratura').val()) || 0;
         const cenaPoM2 = parseFloat($('#cene_cena_po_m2').val()) || 0;
         const pdvProcenat = parseFloat($('#cene_pdv_procenat').val()) || 0;
         const rabat = parseFloat($('#cene_rabat').val()) || 0;
         
        const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
         const pdvVrednost = (osnovnaCena * pdvProcenat) / 100;
         
         $('#cene_pdv').val(pdvVrednost.toFixed(2));
         isCalculating = false;
         calculateStanTotalPrice();
       }
      
             function calculateStanPovratPDV() {
         const kvadratura = parseFloat($('#cene_kvadratura').val()) || 0;
         const cenaPoM2 = parseFloat($('#cene_cena_po_m2').val()) || 0;
         const pdvProcenat = parseFloat($('#cene_pdv_procenat').val()) || 0;
         
         if (kvadratura > 0 && cenaPoM2 > 0) {
           const kvadraturaZaPovrat = Math.min(kvadratura, 40);
           const osnovicaZaPovrat = kvadraturaZaPovrat * cenaPoM2;
           const povratPDV = (osnovicaZaPovrat * pdvProcenat) / 100;
           $('#cene_povrat_pdv').val(povratPDV.toFixed(2));
         } else {
           $('#cene_povrat_pdv').val('0.00');
         }
       }
       
       // Računanje na osnovu ukupne cene
       function calculateStanFromTotalPrice() {
         if (isCalculating) return;
         isCalculating = true;
         
         const ukupnaCena = parseFloat($('#cene_ukupna_cena').val()) || 0;
         const kvadratura = parseFloat($('#cene_kvadratura').val()) || 0;
         const pdvProcenat = parseFloat($('#cene_pdv_procenat').val()) || 0;
         const rabat = parseFloat($('#cene_rabat').val()) || 0;
         
         if (kvadratura > 0) {
           // Izračunaj osnovicu na osnovu ukupne cene i PDV procenta
           // ukupnaCena = osnovica + (osnovica * pdvProcenat/100)
           // ukupnaCena = osnovica * (1 + pdvProcenat/100)
           // osnovica = ukupnaCena / (1 + pdvProcenat/100)
           const osnovica = ukupnaCena / (1 + pdvProcenat/100);
           
           // Dodaj rabat da dobiješ originalnu cenu po m²
          const osnovicaSaRabatom = osnovica - rabat;
           const cenaPoM2 = osnovicaSaRabatom / kvadratura;
           
           // Ažuriraj polja
           $('#cene_cena_po_m2').val(cenaPoM2.toFixed(2));
           $('#cene_pdv').val((osnovica * pdvProcenat / 100).toFixed(2));
           
           // Ažuriraj realnu cenu po m²
           const realnaCena = ukupnaCena / kvadratura;
           $('#cene_realna_cena_po_m2').val(realnaCena.toFixed(2));
         } else {
           $('#cene_cena_po_m2').val('0.00');
           $('#cene_realna_cena_po_m2').val('0.00');
           $('#cene_pdv').val('0.00');
         }
         
         // Računaj povrat PDV-a
         calculateStanPovratPDV();
         isCalculating = false;
       }
       
       // Računanje na osnovu cene po m² sa PDV
       function calculateStanFromRealPricePerM2() {
         if (isCalculating) return;
         isCalculating = true;
         
         const realnaCenaPoM2 = parseFloat($('#cene_realna_cena_po_m2').val()) || 0;
         const kvadratura = parseFloat($('#cene_kvadratura').val()) || 0;
         const pdvProcenat = parseFloat($('#cene_pdv_procenat').val()) || 0;
         const rabat = parseFloat($('#cene_rabat').val()) || 0;
         
         if (kvadratura > 0) {
           // Računaj ukupnu cenu na osnovu realne cene po m²
           const ukupnaCena = realnaCenaPoM2 * kvadratura;
           $('#cene_ukupna_cena').val(ukupnaCena.toFixed(2));
           
           // Izračunaj osnovicu na osnovu ukupne cene i PDV procenta
           // ukupnaCena = osnovica + (osnovica * pdvProcenat/100)
           // ukupnaCena = osnovica * (1 + pdvProcenat/100)
           // osnovica = ukupnaCena / (1 + pdvProcenat/100)
           const osnovica = ukupnaCena / (1 + pdvProcenat/100);
           
           // Dodaj rabat da dobiješ originalnu cenu po m²
          const osnovicaSaRabatom = osnovica - rabat;
           const cenaPoM2 = osnovicaSaRabatom / kvadratura;
           
           // Ažuriraj polja
           $('#cene_cena_po_m2').val(cenaPoM2.toFixed(2));
           $('#cene_pdv').val((osnovica * pdvProcenat / 100).toFixed(2));
         } else {
           $('#cene_ukupna_cena').val('0.00');
           $('#cene_cena_po_m2').val('0.00');
           $('#cene_pdv').val('0.00');
         }
         
         // Računaj povrat PDV-a
         calculateStanPovratPDV();
         isCalculating = false;
       }
       
       // Event listeners za kalkulacije stanova
       $('#cene_cena_po_m2, #cene_pdv').on('input', calculateStanTotalPrice);
       $('#cene_pdv_procenat').on('input', calculateStanPDVFromPercentage);
       $('#cene_rabat').on('input', calculateStanPDVFromPercentage);
       $('#cene_realna_cena_po_m2').on('input', calculateStanFromRealPricePerM2);
       $('#cene_ukupna_cena').on('input', calculateStanFromTotalPrice);
      
      // Kalkulacije za lokale
      function calculateLokalPDV() {
        const kvadratura = parseFloat($('#cene_lokal_kvadratura').val()) || 0;
        const cenaPoM2SaPDV = parseFloat($('#cene_lokal_cena_po_m2').val()) || 0;
        const pdvProcenat = parseFloat($('#cene_lokal_pdv_procenat').val()) || 0;
        
        if (kvadratura === 0) return;
        
        const ukupnaCenaSaPDV = kvadratura * cenaPoM2SaPDV;
        const osnovnaCenaBezPDV = ukupnaCenaSaPDV / (1 + pdvProcenat/100);
        const pdvSuma = ukupnaCenaSaPDV - osnovnaCenaBezPDV;
        const cenaPoM2BezPDV = osnovnaCenaBezPDV / kvadratura;
        
        $('#cene_lokal_pdv').val(pdvSuma.toFixed(2));
        $('#cene_lokal_osnovna_cena').val(cenaPoM2BezPDV.toFixed(2));
        $('#cene_lokal_ukupna_cena').val(ukupnaCenaSaPDV.toFixed(2));
      }
      
      // Event listeners za kalkulacije lokala
      $('#cene_lokal_kvadratura, #cene_lokal_cena_po_m2, #cene_lokal_pdv_procenat').on('input', calculateLokalPDV);
      
             // Dodaj novu prodaju
       $('#dodaj_prodaju').click(function() {
         prodajaCounter++;
         
         const novaProdaja = `
           <div class="card mt-3" id="prodaja_${prodajaCounter}">
             <div class="card-header bg-light">
               <h6 class="mb-0">Prodaja ${prodajaCounter + 1} <button type="button" class="btn btn-sm btn-outline-danger float-right" onclick="ukloniProdaju(${prodajaCounter})"><i class="fas fa-times"></i></button></h6>
             </div>
             <div class="card-body">
               <div class="form-group row">
                 <div class="col-md-4 col-sm-12 mb-3">
                   <label class="col-form-label"><i class="fas fa-building"></i> Objekat</label>
                   <select class="form-control" name="objekat_id_${prodajaCounter}" onchange="ucitajKonkretneObjekteDodatno(${prodajaCounter})">
                     <option value="">Izaberite objekat</option>
                     <?php
                     foreach ($objekti as $objekat) : ?>
                       <option value="<?= $objekat['id'] ?>"><?= htmlspecialchars($objekat['naziv']) ?></option>
                     <?php endforeach; ?>
                   </select>
                 </div>
                 <div class="col-md-4 col-sm-12 mb-3">
                   <label class="col-form-label"><i class="fas fa-tag"></i> Tip objekta</label>
                   <select class="form-control" name="tip_objekta_${prodajaCounter}" onchange="ucitajKonkretneObjekteDodatno(${prodajaCounter}); togglePredugovorFieldDodatno(${prodajaCounter})">
                     <option value="">Izaberite tip objekta</option>
                     <option value="stan">Stan</option>
                     <option value="garaza">Garaža</option>
                     <option value="lokal">Lokal</option>
                     <option value="parking">Parking mesto</option>
                   </select>
                 </div>
                 <div class="col-md-4 col-sm-12 mb-3">
                   <label class="col-form-label"><i class="fas fa-home"></i> Konkretan objekat</label>
                   <select class="form-control" name="konkretan_objekat_id_${prodajaCounter}" id="konkretan_objekat_id_${prodajaCounter}" disabled>
                     <option value="">Prvo izaberite objekat i tip</option>
                   </select>
                 </div>
               </div>
               <div class="form-group row">
                 <div class="col-md-6 col-sm-12 mb-3">
                   <label class="col-form-label"><i class="fas fa-calendar"></i> Datum ugovora</label>
                   <input class="form-control" name="datum_ugovora_${prodajaCounter}" type="date">
                 </div>
                 <div class="col-md-6 col-sm-12 mb-3">
                   <label class="col-form-label"><i class="fas fa-calendar-alt"></i> Datum predugovora</label>
                   <input class="form-control" name="datum_predugovora_${prodajaCounter}" id="datum_predugovora_${prodajaCounter}" type="date" disabled>
                   <small class="form-text text-muted">Dostupno samo za stanove</small>
                 </div>
               </div>
               
               <!-- Cene i kalkulacije za dodatnu prodaju -->
               <div id="cene_sekcija_${prodajaCounter}" style="display: none;">
                 <div class="card mt-3 mb-3" style="background-color: #f8f9fa;">
                   <div class="card-header bg-success text-white">
                     <h6 class="mb-0"><i class="fas fa-calculator"></i> Cene i kalkulacije</h6>
                   </div>
                   <div class="card-body">
                     <!-- Polja za stan -->
                     <div id="cene_stan_${prodajaCounter}" style="display: none;">
                       <div class="form-group row">
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                           <input class="form-control" id="cene_kvadratura_${prodajaCounter}" name="cene_kvadratura_${prodajaCounter}" type="number" step="0.01" min="0" readonly>
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-chart-line"></i> Cena po m² sa PDV (€)</label>
                           <input class="form-control" id="cene_realna_cena_po_m2_${prodajaCounter}" name="cene_realna_cena_po_m2_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² (€)</label>
                           <input class="form-control" id="cene_cena_po_m2_${prodajaCounter}" name="cene_cena_po_m2_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-percentage"></i> PDV PROCENT %</label>
                           <input class="form-control" id="cene_pdv_procenat_${prodajaCounter}" name="cene_pdv_procenat_${prodajaCounter}" type="number" step="0.01" min="0" max="100" value="10">
                         </div>
                       </div>
                       <div class="form-group row">
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-percentage"></i> PDV SUMA (€)</label>
                           <input class="form-control" id="cene_pdv_${prodajaCounter}" name="cene_pdv_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-tag"></i> Rabat (€)</label>
                           <input class="form-control" id="cene_rabat_${prodajaCounter}" name="cene_rabat_${prodajaCounter}" type="number" step="0.01" min="0" value="0">
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                           <input class="form-control" id="cene_ukupna_cena_${prodajaCounter}" name="cene_ukupna_cena_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-undo"></i> Povrat PDV (€)</label>
                           <input class="form-control" id="cene_povrat_pdv_${prodajaCounter}" name="cene_povrat_pdv_${prodajaCounter}" type="number" step="0.01" min="0" readonly>
                           <small class="form-text text-muted">Automatski se računa za prvih 40m²</small>
                         </div>
                       </div>
                     </div>

                     <!-- Polja za lokal -->
                     <div id="cene_lokal_${prodajaCounter}" style="display: none;">
                       <div class="form-group row">
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                           <input class="form-control" id="cene_lokal_kvadratura_${prodajaCounter}" name="cene_lokal_kvadratura_${prodajaCounter}" type="number" step="0.01" min="0" readonly>
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² sa PDV (€)</label>
                           <input class="form-control" id="cene_lokal_cena_po_m2_${prodajaCounter}" name="cene_lokal_cena_po_m2_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-percentage"></i> PDV PROCENT %</label>
                           <input class="form-control" id="cene_lokal_pdv_procenat_${prodajaCounter}" name="cene_lokal_pdv_procenat_${prodajaCounter}" type="number" step="0.01" min="0" max="100" value="20">
                         </div>
                         <div class="col-md-3 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-percentage"></i> PDV SUMA (€)</label>
                           <input class="form-control" id="cene_lokal_pdv_${prodajaCounter}" name="cene_lokal_pdv_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                       </div>
                       <div class="form-group row">
                         <div class="col-md-4 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-calculator"></i> Cena po m² bez PDV (€)</label>
                           <input class="form-control" id="cene_lokal_osnovna_cena_${prodajaCounter}" name="cene_lokal_osnovna_cena_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-4 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                           <input class="form-control" id="cene_lokal_ukupna_cena_${prodajaCounter}" name="cene_lokal_ukupna_cena_${prodajaCounter}" type="number" step="0.01" min="0" readonly>
                         </div>
                         <div class="col-md-4 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-tag"></i> Rabat (€)</label>
                           <input class="form-control" id="cene_lokal_rabat_${prodajaCounter}" name="cene_lokal_rabat_${prodajaCounter}" type="number" step="0.01" min="0" value="0">
                         </div>
                       </div>
                     </div>

                     <!-- Polja za garažu -->
                     <div id="cene_garaza_${prodajaCounter}" style="display: none;">
                       <div class="form-group row">
                         <div class="col-md-4 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena sa PDV (€)</label>
                           <input class="form-control" id="cene_garaza_cena_sa_pdv_${prodajaCounter}" name="cene_garaza_cena_sa_pdv_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-4 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena (€)</label>
                           <input class="form-control" id="cene_garaza_cena_${prodajaCounter}" name="cene_garaza_cena_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                         <div class="col-md-4 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-percentage"></i> PDV (€)</label>
                           <input class="form-control" id="cene_garaza_pdv_${prodajaCounter}" name="cene_garaza_pdv_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                       </div>
                     </div>

                     <!-- Polja za parking -->
                     <div id="cene_parking_${prodajaCounter}" style="display: none;">
                       <div class="form-group row">
                         <div class="col-md-6 col-sm-12 mb-3">
                           <label class="col-form-label"><i class="fas fa-euro-sign"></i> Cena (€)</label>
                           <input class="form-control" id="cene_parking_cena_${prodajaCounter}" name="cene_parking_cena_${prodajaCounter}" type="number" step="0.01" min="0">
                         </div>
                       </div>
                     </div>
                   </div>
                 </div>
               </div>
             </div>
           </div>
         `;
         
         $('#dodatne_prodaje').append(novaProdaja);
         
         // Dodaj event listener za promenu konkretnog objekta u dodatnoj prodaji
         $(`#konkretan_objekat_id_${prodajaCounter}`).on('change', function() {
           ucitajCeneZaDodatnuProdaju(prodajaCounter);
         });
         
         // Dodaj event listenere za kalkulacije za dodatnu prodaju
         inicijalizujKalkulacijeZaDodatnuProdaju(prodajaCounter);
       });
    });
    
    function ukloniProdaju(id) {
      $('#prodaja_' + id).remove();
    }
    
         // Funkcija za učitavanje konkretnih objekata za dodatne prodaje
     window.ucitajKonkretneObjekteDodatno = function(prodajaId) {
       const objekatId = $(`[name="objekat_id_${prodajaId}"]`).val();
       const tipObjekta = $(`[name="tip_objekta_${prodajaId}"]`).val();
       const konkretanSelect = $(`#konkretan_objekat_id_${prodajaId}`);
       
       // Resetuj sekciju cena kada se promeni objekat ili tip
       $(`#cene_sekcija_${prodajaId}`).hide();
       $(`#cene_stan_${prodajaId}, #cene_lokal_${prodajaId}, #cene_garaza_${prodajaId}, #cene_parking_${prodajaId}`).hide();
       
       if (objekatId && tipObjekta) {
         konkretanSelect.prop('disabled', false);
         konkretanSelect.html('<option value="">Učitavam...</option>');
         
         $.ajax({
           url: 'get_konkretni_objekti.php',
           method: 'POST',
           data: {
             objekat_id: objekatId,
             tip_objekta: tipObjekta
           },
           success: function(response) {
             konkretanSelect.html(response);
           },
           error: function() {
             konkretanSelect.html('<option value="">Greška pri učitavanju</option>');
           }
         });
       } else {
         konkretanSelect.prop('disabled', true);
         konkretanSelect.html('<option value="">Prvo izaberite objekat i tip</option>');
       }
     }
    
         // Funkcija za omogućavanje/onemogućavanje polja predugovora za dodatne prodaje
     window.togglePredugovorFieldDodatno = function(prodajaId) {
       const tipObjekta = $(`[name="tip_objekta_${prodajaId}"]`).val();
       const predugovorField = $(`#datum_predugovora_${prodajaId}`);
       
       if (tipObjekta === 'stan') {
         predugovorField.prop('disabled', false);
         predugovorField.siblings('small').text('Dostupno za stanove');
       } else {
         predugovorField.prop('disabled', true);
         predugovorField.val(''); // Obriši vrednost
         predugovorField.siblings('small').text('Dostupno samo za stanove');
       }
     }
     
     // Funkcija za učitavanje cena za dodatnu prodaju
     window.ucitajCeneZaDodatnuProdaju = function(prodajaId) {
       const konkretanObjekatId = $(`#konkretan_objekat_id_${prodajaId}`).val();
       const tipObjekta = $(`[name="tip_objekta_${prodajaId}"]`).val();
       
       if (konkretanObjekatId && tipObjekta) {
         $.ajax({
           url: 'get_objekat_cene.php',
           method: 'POST',
           data: {
             tip_objekta: tipObjekta,
             objekat_id: konkretanObjekatId
           },
           dataType: 'json',
           success: function(response) {
             if (response.success) {
               $(`#cene_sekcija_${prodajaId}`).show();
               $(`#cene_stan_${prodajaId}, #cene_lokal_${prodajaId}, #cene_garaza_${prodajaId}, #cene_parking_${prodajaId}`).hide();
               
               if (response.tip === 'stan') {
                 $(`#cene_stan_${prodajaId}`).show();
                 $(`#cene_kvadratura_${prodajaId}`).val(response.kvadratura || '');
                 $(`#cene_cena_po_m2_${prodajaId}`).val(response.cena_po_m2 || '');
                 $(`#cene_realna_cena_po_m2_${prodajaId}`).val(response.realna_cena_po_m2 || '');
                 $(`#cene_pdv_procenat_${prodajaId}`).val(response.pdv_procenat || '10');
                 $(`#cene_pdv_${prodajaId}`).val(response.pdv || '');
                 $(`#cene_rabat_${prodajaId}`).val(response.rabat || '0');
                 $(`#cene_ukupna_cena_${prodajaId}`).val(response.ukupna_cena || '');
                const dodatniPovrat = izracunajPovratPDVValue(response.kvadratura, response.cena_po_m2, $(`#cene_pdv_procenat_${prodajaId}`).val());
                $(`#cene_povrat_pdv_${prodajaId}`).val(dodatniPovrat.toFixed(2));
                $(`#cene_pdv_procenat_${prodajaId}`).trigger('input');
               } else if (response.tip === 'lokal') {
                 $(`#cene_lokal_${prodajaId}`).show();
                 $(`#cene_lokal_kvadratura_${prodajaId}`).val(response.kvadratura || '');
                 $(`#cene_lokal_cena_po_m2_${prodajaId}`).val(response.cena_po_m2 || '');
                 $(`#cene_lokal_pdv_procenat_${prodajaId}`).val(response.pdv_procenat || '20');
                 $(`#cene_lokal_pdv_${prodajaId}`).val(response.pdv || '');
                 $(`#cene_lokal_osnovna_cena_${prodajaId}`).val(response.osnovna_cena || '');
                 $(`#cene_lokal_ukupna_cena_${prodajaId}`).val(response.ukupna_cena || '');
                 $(`#cene_lokal_rabat_${prodajaId}`).val(response.rabat || '0');
               } else if (response.tip === 'garaza') {
                 $(`#cene_garaza_${prodajaId}`).show();
                 $(`#cene_garaza_cena_sa_pdv_${prodajaId}`).val(response.cena_sa_pdv || '');
                 $(`#cene_garaza_cena_${prodajaId}`).val(response.cena || '');
                 $(`#cene_garaza_pdv_${prodajaId}`).val(response.pdv || '');
               } else if (response.tip === 'parking') {
                 $(`#cene_parking_${prodajaId}`).show();
                 $(`#cene_parking_cena_${prodajaId}`).val(response.cena || '');
               }
             } else {
               $(`#cene_sekcija_${prodajaId}`).hide();
             }
           },
           error: function() {
             $(`#cene_sekcija_${prodajaId}`).hide();
           }
         });
       } else {
         $(`#cene_sekcija_${prodajaId}`).hide();
       }
     }
     
     // Funkcija za inicijalizaciju kalkulacija za dodatnu prodaju
     window.inicijalizujKalkulacijeZaDodatnuProdaju = function(prodajaId) {
       // Kalkulacije za stanove - dodatna prodaja
       function calculateStanTotalPriceDodatno() {
         if (isCalculating) return;
         isCalculating = true;
         
         const kvadratura = parseFloat($(`#cene_kvadratura_${prodajaId}`).val()) || 0;
         const cenaPoM2 = parseFloat($(`#cene_cena_po_m2_${prodajaId}`).val()) || 0;
         const pdv = parseFloat($(`#cene_pdv_${prodajaId}`).val()) || 0;
         const rabat = parseFloat($(`#cene_rabat_${prodajaId}`).val()) || 0;
         
        const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
         const ukupnaCena = osnovnaCena + pdv;
         
         $(`#cene_ukupna_cena_${prodajaId}`).val(ukupnaCena.toFixed(2));
         
         if (kvadratura > 0) {
           const realnaCena = ukupnaCena / kvadratura;
           $(`#cene_realna_cena_po_m2_${prodajaId}`).val(realnaCena.toFixed(2));
         }
         
         calculateStanPovratPDVDodatno();
         isCalculating = false;
       }
       
       function calculateStanPDVFromPercentageDodatno() {
         if (isCalculating) return;
         isCalculating = true;
         
         const kvadratura = parseFloat($(`#cene_kvadratura_${prodajaId}`).val()) || 0;
         const cenaPoM2 = parseFloat($(`#cene_cena_po_m2_${prodajaId}`).val()) || 0;
         const pdvProcenat = parseFloat($(`#cene_pdv_procenat_${prodajaId}`).val()) || 0;
         const rabat = parseFloat($(`#cene_rabat_${prodajaId}`).val()) || 0;
         
        const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
         const pdvVrednost = (osnovnaCena * pdvProcenat) / 100;
         
         $(`#cene_pdv_${prodajaId}`).val(pdvVrednost.toFixed(2));
         isCalculating = false;
         calculateStanTotalPriceDodatno();
       }
       
       function calculateStanPovratPDVDodatno() {
         const kvadratura = parseFloat($(`#cene_kvadratura_${prodajaId}`).val()) || 0;
         const cenaPoM2 = parseFloat($(`#cene_cena_po_m2_${prodajaId}`).val()) || 0;
         const pdvProcenat = parseFloat($(`#cene_pdv_procenat_${prodajaId}`).val()) || 0;
         
         if (kvadratura > 0 && cenaPoM2 > 0) {
           const kvadraturaZaPovrat = Math.min(kvadratura, 40);
           const osnovicaZaPovrat = kvadraturaZaPovrat * cenaPoM2;
           const povratPDV = (osnovicaZaPovrat * pdvProcenat) / 100;
           $(`#cene_povrat_pdv_${prodajaId}`).val(povratPDV.toFixed(2));
         } else {
           $(`#cene_povrat_pdv_${prodajaId}`).val('0.00');
         }
       }
       
       function calculateStanFromTotalPriceDodatno() {
         if (isCalculating) return;
         isCalculating = true;
         
         const ukupnaCena = parseFloat($(`#cene_ukupna_cena_${prodajaId}`).val()) || 0;
         const kvadratura = parseFloat($(`#cene_kvadratura_${prodajaId}`).val()) || 0;
         const pdvProcenat = parseFloat($(`#cene_pdv_procenat_${prodajaId}`).val()) || 0;
         const rabat = parseFloat($(`#cene_rabat_${prodajaId}`).val()) || 0;
         
         if (kvadratura > 0) {
           const osnovica = ukupnaCena / (1 + pdvProcenat/100);
          const osnovicaSaRabatom = osnovica - rabat;
           const cenaPoM2 = osnovicaSaRabatom / kvadratura;
           
           $(`#cene_cena_po_m2_${prodajaId}`).val(cenaPoM2.toFixed(2));
           $(`#cene_pdv_${prodajaId}`).val((osnovica * pdvProcenat / 100).toFixed(2));
           
           const realnaCena = ukupnaCena / kvadratura;
           $(`#cene_realna_cena_po_m2_${prodajaId}`).val(realnaCena.toFixed(2));
         }
         
         calculateStanPovratPDVDodatno();
         isCalculating = false;
       }
       
       function calculateStanFromRealPricePerM2Dodatno() {
         if (isCalculating) return;
         isCalculating = true;
         
         const realnaCenaPoM2 = parseFloat($(`#cene_realna_cena_po_m2_${prodajaId}`).val()) || 0;
         const kvadratura = parseFloat($(`#cene_kvadratura_${prodajaId}`).val()) || 0;
         const pdvProcenat = parseFloat($(`#cene_pdv_procenat_${prodajaId}`).val()) || 0;
         const rabat = parseFloat($(`#cene_rabat_${prodajaId}`).val()) || 0;
         
         if (kvadratura > 0) {
           const ukupnaCena = realnaCenaPoM2 * kvadratura;
           $(`#cene_ukupna_cena_${prodajaId}`).val(ukupnaCena.toFixed(2));
           
           const osnovica = ukupnaCena / (1 + pdvProcenat/100);
          const osnovicaSaRabatom = osnovica - rabat;
           const cenaPoM2 = osnovicaSaRabatom / kvadratura;
           
           $(`#cene_cena_po_m2_${prodajaId}`).val(cenaPoM2.toFixed(2));
           $(`#cene_pdv_${prodajaId}`).val((osnovica * pdvProcenat / 100).toFixed(2));
         }
         
         calculateStanPovratPDVDodatno();
         isCalculating = false;
       }
       
       // Event listeners za kalkulacije stanova - dodatna prodaja
       $(`#cene_cena_po_m2_${prodajaId}, #cene_pdv_${prodajaId}`).on('input', calculateStanTotalPriceDodatno);
       $(`#cene_pdv_procenat_${prodajaId}`).on('input', calculateStanPDVFromPercentageDodatno);
       $(`#cene_rabat_${prodajaId}`).on('input', calculateStanPDVFromPercentageDodatno);
       $(`#cene_realna_cena_po_m2_${prodajaId}`).on('input', calculateStanFromRealPricePerM2Dodatno);
       $(`#cene_ukupna_cena_${prodajaId}`).on('input', calculateStanFromTotalPriceDodatno);
       
       // Kalkulacije za lokale - dodatna prodaja
       function calculateLokalPDVDodatno() {
         const kvadratura = parseFloat($(`#cene_lokal_kvadratura_${prodajaId}`).val()) || 0;
         const cenaPoM2SaPDV = parseFloat($(`#cene_lokal_cena_po_m2_${prodajaId}`).val()) || 0;
         const pdvProcenat = parseFloat($(`#cene_lokal_pdv_procenat_${prodajaId}`).val()) || 0;
         
         if (kvadratura === 0) return;
         
         const ukupnaCenaSaPDV = kvadratura * cenaPoM2SaPDV;
         const osnovnaCenaBezPDV = ukupnaCenaSaPDV / (1 + pdvProcenat/100);
         const pdvSuma = ukupnaCenaSaPDV - osnovnaCenaBezPDV;
         const cenaPoM2BezPDV = osnovnaCenaBezPDV / kvadratura;
         
         $(`#cene_lokal_pdv_${prodajaId}`).val(pdvSuma.toFixed(2));
         $(`#cene_lokal_osnovna_cena_${prodajaId}`).val(cenaPoM2BezPDV.toFixed(2));
         $(`#cene_lokal_ukupna_cena_${prodajaId}`).val(ukupnaCenaSaPDV.toFixed(2));
       }
       
       $(`#cene_lokal_kvadratura_${prodajaId}, #cene_lokal_cena_po_m2_${prodajaId}, #cene_lokal_pdv_procenat_${prodajaId}`).on('input', calculateLokalPDVDodatno);
     }
  </script>

</body>

</html>


