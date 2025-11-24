<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

if (!function_exists('ucitajPlanOtplateStana')) {
  function ucitajPlanOtplateStana(int $stanId, ?int $kupacId): array
  {
    $rezultat = [
      'ima_plan' => false,
      'stavke' => [],
      'ukupno' => 0.0,
      'uplaceno' => 0.0,
      'preostalo' => 0.0,
    ];

    if (!$stanId || !$kupacId) {
      return $rezultat;
    }

    try {
      $crudPlan = new CRUD($_SESSION['godina']);
      $crudPlan->table = 'planovi_otplate';
      $planovi = $crudPlan->select(
        ['*'],
        [
          'kupac_id' => $kupacId,
          'jedinica_id' => $stanId,
          'tip_jedinice' => 'stan',
        ],
        "SELECT * FROM planovi_otplate WHERE kupac_id = :kupac_id AND jedinica_id = :jedinica_id AND tip_jedinice = :tip_jedinice ORDER BY datum_rate ASC"
      );
    } catch (Exception $e) {
      return $rezultat;
    }

    if (empty($planovi)) {
      return $rezultat;
    }

    $rezultat['ima_plan'] = true;
    $imaKolonuUplaceno = array_key_exists('uplaceno', $planovi[0] ?? []);

    foreach ($planovi as $plan) {
      $suma = (float)($plan['suma'] ?? 0);
      $rezultat['ukupno'] += $suma;

      $uplaceno = 0.0;
      if ($imaKolonuUplaceno) {
        $uplaceno = (float)($plan['uplaceno'] ?? 0);
      } else {
        $uplaceno = (($plan['status'] ?? '') === 'placeno') ? $suma : 0.0;
      }
      $uplaceno = min($uplaceno, $suma);
      $rezultat['uplaceno'] += $uplaceno;

      $preostalo = max(0.0, $suma - $uplaceno);
      $rezultat['preostalo'] += $preostalo;

      $datumRate = $plan['datum_rate'] ?? null;
      $formatiranDatum = $datumRate ? formatirajDatum($datumRate, '') : '';

      $rezultat['stavke'][] = [
        'datum' => $formatiranDatum,
        'suma' => $suma,
        'uplaceno' => $uplaceno,
        'preostalo' => $preostalo,
        'status' => $plan['status'] ?? '',
      ];
    }

    return $rezultat;
  }
}

// Učitaj stan za izmenu
$stan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$stan_id) {
  header("location:glavni.php");
  exit;
}

$crud = new CRUD($_SESSION['godina']);
$crud->table = "stanovi";
$stan = $crud->select(['*'], ['id' => $stan_id]);

if (empty($stan)) {
  $_SESSION['poruka'] = "Greška: Stan nije pronađen.";
  header("location:glavni.php");
  exit;
}

$stan = $stan[0]; // Uzmi prvi (i jedini) rezultat

// Učitaj vezano parking mesto ako postoji
$parkingCrud = new CRUD($_SESSION['godina']);
$parkingCrud->table = "parking_mesta";
$vezano_parking = $parkingCrud->select(['*'], ['stan_id' => $stan_id]);
$stan['vezano_parking'] = !empty($vezano_parking) ? $vezano_parking[0] : null;

$stanJeProdat = (int)($stan['prodat'] ?? 0) === 1;

// Učitaj objekte za dropdown
$objektiCrud = new CRUD($_SESSION['godina']);
$objektiCrud->table = "objekti";
$objekti = $objektiCrud->select(['*'], [], "SELECT * FROM objekti ORDER BY naziv ASC");

// Učitaj kanale prodaje
$kanalCrud = new CRUD($_SESSION['godina']);
$kanalCrud->table = "kanal_prodaje";
$kanali = $kanalCrud->select(['*'], [], "SELECT * FROM kanal_prodaje ORDER BY id ASC");

// Učitaj kupca ako je stan prodat
$kupac = null;
if ($stan['kupac_id']) {
    $kupacCrud = new CRUD($_SESSION['godina']);
    $kupacCrud->table = "kupci";
    $kupac_data = $kupacCrud->select(['*'], ['id' => $stan['kupac_id']]);
    $kupac = !empty($kupac_data) ? $kupac_data[0] : null;
}

$planOtplate = ucitajPlanOtplateStana($stan_id, $stan['kupac_id'] ?? null);

// Obrada forme za izmenu stana
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['akcija']) && $_POST['akcija'] === 'raskini_prodaju') {
    try {
      $potvrdjeniId = isset($_POST['stan_id']) ? (int)$_POST['stan_id'] : 0;
      if ($potvrdjeniId !== $stan_id) {
        throw new Exception('Neispravan zahtev za raskid prodaje.');
      }

      $crud->update([
        'prodat' => 0,
        'kupac_id' => null,
        'datum_prodaje' => null,
        'datum_predugovora' => null,
      ], ['id' => $stan_id]);

      $parkingResetCrud = new CRUD($_SESSION['godina']);
      $parkingResetCrud->table = "parking_mesta";
      $parkingResetCrud->update([
        'prodat' => 0,
        'kupac_id' => null,
        'datum_prodaje' => null,
      ], ['stan_id' => $stan_id]);

      $planDeleteCrud = new CRUD($_SESSION['godina']);
      $planDeleteCrud->table = "planovi_otplate";
      $planDeleteCrud->delete([
        'tip_jedinice' => 'stan',
        'jedinica_id' => $stan_id
      ]);

      $uplataDeleteCrud = new CRUD($_SESSION['godina']);
      $uplataDeleteCrud->table = "uplata";
      $uplataDeleteCrud->delete([
        'tip_jedinice' => 'stan',
        'id_jedinice' => $stan_id
      ]);

      $_SESSION['poruka'] = "Prodaja stana je uspešno raskinuta. Sada možete izmeniti podatke.";
    } catch (Exception $e) {
      $_SESSION['poruka'] = "Greška: " . $e->getMessage();
    }

    $osvezen = $crud->select(['*'], ['id' => $stan_id]);
    if (!empty($osvezen)) {
      $stan = $osvezen[0];
    }
    $parkingCrud = new CRUD($_SESSION['godina']);
    $parkingCrud->table = "parking_mesta";
    $vezano_parking = $parkingCrud->select(['*'], ['stan_id' => $stan_id]);
    $stan['vezano_parking'] = !empty($vezano_parking) ? $vezano_parking[0] : null;
    if (!empty($stan['kupac_id'])) {
      $kupacCrud = new CRUD($_SESSION['godina']);
      $kupacCrud->table = "kupci";
      $kupac_podaci = $kupacCrud->select(['*'], ['id' => $stan['kupac_id']]);
      $kupac = !empty($kupac_podaci) ? $kupac_podaci[0] : null;
    } else {
      $kupac = null;
    }
    $stanJeProdat = (int)($stan['prodat'] ?? 0) === 1;
  } else {
    try {
      if ($stanJeProdat) {
        $_SESSION['poruka'] = "Greška: Stan je označen kao prodat. Najpre raskinite prodaju da biste menjali podatke.";
      } else {
        $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';

        if ($naziv === '') {
          $_SESSION['poruka'] = "Greška: Naziv stana je obavezan.";
        } else {
          $prodajeSaParking = isset($_POST['prodaje_sa_parking_mestom']) ? 1 : 0;
          $parking_mesto_id = isset($_POST['parking_mesto_id']) ? (int)$_POST['parking_mesto_id'] : 0;
          $kompenzacija = isset($_POST['kompenzacija']) ? 1 : 0;
          $lokacija = isset($_POST['lokacija']) ? 1 : 0;

          $errors = [];

          if ($kompenzacija && $lokacija) {
            $errors[] = "Stan ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.";
          }

          if ($prodajeSaParking && $parking_mesto_id <= 0) {
            $errors[] = "Molimo izaberite parking mesto.";
          }

          $kupacIdValue = null;
          $datumProdaje = null;
          $datumPredugovora = null;

          if ($kompenzacija || $lokacija) {
            $kupacIdValue = isset($_POST['kupac_id']) && $_POST['kupac_id'] !== '' ? (int)$_POST['kupac_id'] : null;
            $datumProdaje = $_POST['datum_prodaje'] ?? null;
            $datumPredugovora = $_POST['datum_predugovora'] ?? null;

            if (!$kupacIdValue) {
              $errors[] = "Molimo izaberite kupca za kompenzaciju/lokaciju.";
            }
          }

          if (!empty($errors)) {
            $_SESSION['poruka'] = "Greška: " . implode(' ', $errors);
          } else {
            $kanal_prodaje_id = $_POST['kanal_prodaje_id'] ?? $stan['kanal_prodaje_id'];

            if ($kompenzacija || $lokacija) {
              $kanalLookupName = $kompenzacija ? 'Kompenzacija' : 'Lokacija';
              $kanalLookupCrud = new CRUD($_SESSION['godina']);
              $kanalLookupCrud->table = "kanal_prodaje";
              $kanalLookup = $kanalLookupCrud->select(['id'], ['naziv' => $kanalLookupName]);
              if (!empty($kanalLookup)) {
                $kanal_prodaje_id = $kanalLookup[0]['id'];
              }
            } else {
              if ($kanal_prodaje_id === '') {
                $kanal_prodaje_id = null;
              }
            }

            if ($kanal_prodaje_id !== null && $kanal_prodaje_id !== '') {
              $kanal_prodaje_id = (int)$kanal_prodaje_id;
            } else {
              $kanal_prodaje_id = null;
            }

            $prodat = ($kompenzacija || $lokacija) ? 1 : 0;
            $kupacIdValue = ($kompenzacija || $lokacija) ? $kupacIdValue : null;
            $datumProdaje = ($kompenzacija || $lokacija) ? ($datumProdaje ?: null) : null;
            $datumPredugovora = ($kompenzacija || $lokacija) ? ($datumPredugovora ?: null) : null;

            $crud->update([
              'objekat_id' => $_POST['objekat_id'],
              'naziv' => $naziv,
              'kvadratura' => $_POST['kvadratura'],
              'cena_po_m2' => $_POST['cena_po_m2'],
              'pdv' => $_POST['pdv'] ?? 0,
              'rabat' => $_POST['rabat'] ?? 0,
              'ukupna_cena' => $_POST['ukupna_cena'],
              'realna_cena_po_m2' => $_POST['realna_cena_po_m2'] ?? 0,
              'prodaje_sa_parking_mestom' => $prodajeSaParking,
              'kompenzacija' => $kompenzacija,
              'lokacija' => $lokacija,
              'prodat' => $prodat,
              'kupac_id' => $kupacIdValue,
              'datum_prodaje' => $datumProdaje,
              'datum_predugovora' => $datumPredugovora,
              'kanal_prodaje_id' => $kanal_prodaje_id
            ], ['id' => $stan_id]);

            $parkingCrudUpdate = new CRUD($_SESSION['godina']);
            $parkingCrudUpdate->table = "parking_mesta";

            if ($prodajeSaParking && $parking_mesto_id > 0) {
              $parkingCrudUpdate->update([
                'stan_id' => null,
                'prodat' => 0,
                'kupac_id' => null,
                'datum_prodaje' => null
              ], ['stan_id' => $stan_id]);

              $parkingCrudUpdate->update([
                'stan_id' => $stan_id,
                'prodat' => 1
              ], ['id' => $parking_mesto_id]);
            } else {
              $parkingCrudUpdate->update([
                'stan_id' => null,
                'prodat' => 0,
                'kupac_id' => null,
                'datum_prodaje' => null
              ], ['stan_id' => $stan_id]);
            }

            $updated_stan = $crud->select(['*'], ['id' => $stan_id]);

            if (!empty($updated_stan) && $updated_stan[0]['naziv'] === $naziv) {
              $_SESSION['poruka'] = "Stan je uspešno ažuriran!";
              $stan = $updated_stan[0];
              $vezano_parking = $parkingCrudUpdate->select(['*'], ['stan_id' => $stan_id]);
              $stan['vezano_parking'] = !empty($vezano_parking) ? $vezano_parking[0] : null;
              if (!empty($stan['kupac_id'])) {
                $kupacCrud = new CRUD($_SESSION['godina']);
                $kupacCrud->table = "kupci";
                $kupac_podaci = $kupacCrud->select(['*'], ['id' => $stan['kupac_id']]);
                $kupac = !empty($kupac_podaci) ? $kupac_podaci[0] : null;
              } else {
                $kupac = null;
              }

              if ($prodat && $kupacIdValue) {
                $rabatIznos = isset($stan['rabat']) ? (float)$stan['rabat'] : 0;
                if ($rabatIznos > 0) {
                  $rabatIznos = round($rabatIznos, 2);
                  $uplataCrud = new CRUD($_SESSION['godina']);
                  $uplataCrud->table = "uplata";
                  $srednjiKursRabat = isset($_SESSION['euro']) ? floatval($_SESSION['euro']) : null;
                  $rabatVrednostRSD = ($srednjiKursRabat && $srednjiKursRabat > 0) ? round($rabatIznos * $srednjiKursRabat, 2) : null;

                  $postojiRabatUplata = $uplataCrud->select(
                    ['id'],
                    [
                      'id_kupca' => $kupacIdValue,
                      'tip_jedinice' => 'stan',
                      'id_jedinice' => $stan_id,
                      'kes' => 1,
                      'iznos_uplate' => $rabatIznos
                    ]
                  );

                  if (empty($postojiRabatUplata)) {
                    $uplataCrud->insert([
                      'id_kupca' => $kupacIdValue,
                      'datum_uplate' => $stan['datum_prodaje'] ?? ($datumProdaje ?? date('Y-m-d')),
                      'trenutna_vrednost_eura' => $_SESSION['euro'] ?? 0,
                      'srednji_kurs' => $srednjiKursRabat,
                      'iznos_uplate' => $rabatIznos,
                      'tip_jedinice' => 'stan',
                      'id_jedinice' => $stan_id,
                      'kes' => 1,
                      'vrednost_u_dinarima' => $rabatVrednostRSD
                    ]);
                  }
                }
              }
            } else {
              $_SESSION['poruka'] = "Greška pri ažuriranju stana!";
            }
          }
        }
      }
    } catch (Exception $e) {
      $_SESSION['poruka'] = "Greška: " . $e->getMessage();
    }

    $stanJeProdat = (int)($stan['prodat'] ?? 0) === 1;
  }
}

$planOtplate = ucitajPlanOtplateStana($stan_id, $stan['kupac_id'] ?? null);
$kupciLista = [];
try {
  $kupacSelectCrud = new CRUD($_SESSION['godina']);
  $kupacSelectCrud->table = "kupci";
  $kupciLista = $kupacSelectCrud->select(['*'], [], "SELECT * FROM kupci ORDER BY ime ASC, prezime ASC");
} catch (Exception $e) {
  $kupciLista = [];
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Stan - Izmena</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/funkcije.js"></script>
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <script>
    // Automatsko računanje ukupne cene
    function calculateTotalPrice() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2 = parseFloat($('#cena_po_m2').val()) || 0;
      const pdv = parseFloat($('#pdv').val()) || 0;
      const rabat = parseFloat($('#rabat').val()) || 0;

      const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
      const ukupnaCena = osnovnaCena + pdv;

      $('#ukupna_cena').val(ukupnaCena.toFixed(2));
      calculateRealPricePerM2();
      calculatePovratPDV();
    }
    
    function calculateRealPricePerM2() {
      const ukupnaCena = parseFloat($('#ukupna_cena').val()) || 0;
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const isFocused = document.activeElement && document.activeElement.id === 'realna_cena_po_m2';

      if (kvadratura > 0) {
        const realnaCena = ukupnaCena / kvadratura;
        if (!isFocused) {
          $('#realna_cena_po_m2').val(realnaCena.toFixed(2));
        }
      } else if (!isFocused) {
        $('#realna_cena_po_m2').val('0.00');
      }

      calculatePovratPDV();
    }

    // Računanje PDV-a iz procenta
    function calculatePDVFromPercentage() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2 = parseFloat($('#cena_po_m2').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
      const rabat = parseFloat($('#rabat').val()) || 0;

      const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
      const pdvVrednost = (osnovnaCena * pdvProcenat) / 100;

      $('#pdv').val(pdvVrednost.toFixed(2));
      calculateTotalPrice();
    }

    // Računanje PDV procenta iz apsolutne vrednosti
    function calculatePDVPercentage() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2 = parseFloat($('#cena_po_m2').val()) || 0;
      const pdv = parseFloat($('#pdv').val()) || 0;
      const rabat = parseFloat($('#rabat').val()) || 0;

      const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
      const pdvProcenat = osnovnaCena > 0 ? (pdv / osnovnaCena) * 100 : 0;

      $('#pdv_procenat').val(pdvProcenat.toFixed(2));
      calculateTotalPrice();
    }

    // Računanje povrata PDV-a (samo za prvih 40m²)
    function calculatePovratPDV() {
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2 = parseFloat($('#cena_po_m2').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;

      if (kvadratura > 0 && cenaPoM2 > 0) {
        const kvadraturaZaPovrat = Math.min(kvadratura, 40);

        const osnovicaZaPovrat = kvadraturaZaPovrat * cenaPoM2;

        const povratPDV = (osnovicaZaPovrat * pdvProcenat) / 100;

        $('#povrat_pdv').val(povratPDV.toFixed(2));
      } else {
        $('#povrat_pdv').val('0.00');
      }
    }

    function calculateFromTotalPrice() {
      const ukupnaCena = parseFloat($('#ukupna_cena').val()) || 0;
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
      const rabat = parseFloat($('#rabat').val()) || 0;
      const parkingCena = parseFloat($('#parking_mesto_id option:selected').data('cena')) || 0;

      if (kvadratura > 0) {
        const ukupnaCenaBezParkinga = ukupnaCena - parkingCena;
        const osnovica = ukupnaCenaBezParkinga / (1 + pdvProcenat / 100);
        const osnovicaSaRabatom = osnovica - rabat;
        const cenaPoM2 = osnovicaSaRabatom / kvadratura;

        $('#cena_po_m2').val(cenaPoM2.toFixed(2));
        $('#pdv').val(((osnovica * pdvProcenat) / 100).toFixed(2));

        const realnaCena = ukupnaCena / kvadratura;
        $('#realna_cena_po_m2').val(realnaCena.toFixed(2));
      } else {
        $('#cena_po_m2').val('0.00');
        $('#realna_cena_po_m2').val('0.00');
        $('#pdv').val('0.00');
      }

      calculatePovratPDV();
    }

    function calculateFromRealPricePerM2() {
      const realnaCenaPoM2 = parseFloat($('#realna_cena_po_m2').val()) || 0;
      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
      const rabat = parseFloat($('#rabat').val()) || 0;
      const parkingCena = parseFloat($('#parking_mesto_id option:selected').data('cena')) || 0;

      if (kvadratura > 0) {
        const ukupnaCena = realnaCenaPoM2 * kvadratura;
        $('#ukupna_cena').val(ukupnaCena.toFixed(2));

        const ukupnaCenaBezParkinga = ukupnaCena - parkingCena;
        const osnovica = ukupnaCenaBezParkinga / (1 + pdvProcenat / 100);
        const osnovicaSaRabatom = osnovica - rabat;
        const cenaPoM2 = osnovicaSaRabatom / kvadratura;

        $('#cena_po_m2').val(cenaPoM2.toFixed(2));
        $('#pdv').val(((osnovica * pdvProcenat) / 100).toFixed(2));
      } else {
        $('#ukupna_cena').val('0.00');
        $('#cena_po_m2').val('0.00');
        $('#pdv').val('0.00');
        if (!(document.activeElement && document.activeElement.id === 'realna_cena_po_m2')) {
          $('#realna_cena_po_m2').val('');
        }
      }

      calculatePovratPDV();
    }
    
    
    // Event listeners za automatsko računanje
    function calculateTotalPriceWithParking() {
      if (!$('#prodaje_sa_parking_mestom').is(':checked')) {
        calculateTotalPrice();
        return;
      }

      const kvadratura = parseFloat($('#kvadratura').val()) || 0;
      const cenaPoM2 = parseFloat($('#cena_po_m2').val()) || 0;
      const pdv = parseFloat($('#pdv').val()) || 0;
      const rabat = parseFloat($('#rabat').val()) || 0;
      const parkingCena = parseFloat($('#parking_mesto_id option:selected').data('cena')) || 0;

      const osnovnaCena = (kvadratura * cenaPoM2) + rabat;
      const ukupnaCena = osnovnaCena + pdv + parkingCena;

      $('#ukupna_cena').val(ukupnaCena.toFixed(2));
      calculateRealPricePerM2();
      calculatePovratPDV();
    }

    function setKanalProdajeByName(name) {
      let found = false;
      $('#kanal_prodaje_id option').each(function() {
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
      $('#kanal_prodaje_id').val('').trigger('change');
    }

    function updateStatusFields(initial = false) {
      const kompenzacija = $('#kompenzacija').is(':checked');
      const lokacija = $('#lokacija').is(':checked');

      if (initial && kompenzacija && lokacija) {
        $('#lokacija').prop('checked', false);
        return updateStatusFields(true);
      }

      if (kompenzacija || lokacija) {
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
          $('#kupac_id').val('').trigger('change');
          $('#datum_prodaje').val('');
          $('#datum_predugovora').val('');
          resetKanalProdaje();
        }
      }
    }

    $(document).ready(function() {
      $('.select2-kupci').select2({
        theme: 'bootstrap-5',
        placeholder: 'Izaberite kupca',
        allowClear: true,
        width: '100%'
      });

      $('#kompenzacija, #lokacija').on('change', function() {
        const kompenzacijaChecked = $('#kompenzacija').is(':checked');
        const lokacijaChecked = $('#lokacija').is(':checked');
        if (kompenzacijaChecked && lokacijaChecked) {
          alert('Stan ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.');
          $(this).prop('checked', false);
        }
        updateStatusFields();
      });
      $('#kvadratura, #cena_po_m2, #rabat').on('input', function() {
        calculatePDVFromPercentage();
        if ($('#prodaje_sa_parking_mestom').is(':checked')) {
          calculateTotalPriceWithParking();
        }
      });
      
      // PDV procenti - računanje apsolutne vrednosti
      $('#pdv_procenat').on('input', function() {
        calculatePDVFromPercentage();
        if ($('#prodaje_sa_parking_mestom').is(':checked')) {
          calculateTotalPriceWithParking();
        }
      });
      
      // PDV apsolutna vrednost - računanje procenta
      $('#pdv').on('input', function() {
        calculatePDVPercentage();
        if ($('#prodaje_sa_parking_mestom').is(':checked')) {
          calculateTotalPriceWithParking();
        }
      });

      $('#realna_cena_po_m2').on('input', function() {
        calculateFromRealPricePerM2();
      });

      $('#ukupna_cena').on('input', function() {
        calculateFromTotalPrice();
      });

      // Checkbox za parking mesto
      $('#prodaje_sa_parking_mestom').on('change', function() {
        if ($(this).is(':checked')) {
          $('#parking_mesto_label, #parking_mesto_id').show();
        } else {
          $('#parking_mesto_label, #parking_mesto_id').hide();
          $('#parking_mesto_id').val('');
        }
        calculateTotalPriceWithParking();
      });
      $('#parking_mesto_id').on('change', function() {
        calculateTotalPriceWithParking();
      });
      calculatePDVPercentage();
      updateStatusFields(true);
      if ($('#prodaje_sa_parking_mestom').is(':checked')) {
        $('#parking_mesto_label, #parking_mesto_id').show();
      }
      if ($('#prodaje_sa_parking_mestom').is(':checked')) {
        calculateTotalPriceWithParking();
      } else {
        calculateTotalPrice();
      }
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

          <h3 class="center sekcija">IZMENA STANA <i class="fas fa-edit"></i></h3> <br>

          <?php if (isset($_SESSION['poruka']) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <div class="alert alert-<?= strpos($_SESSION['poruka'], 'Greška') !== false ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
              <?= $_SESSION['poruka'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['poruka']); ?>
          <?php endif; ?>

          <?php if ($stanJeProdat): ?>
            <div class="alert alert-warning w-100" role="alert">
              <i class="fas fa-lock"></i> Stan je trenutno označen kao prodat. Da biste omogućili izmene, prvo raskinite prodaju.
            </div>
            <form method="POST" class="mb-3" data-track-unsaved="false" onsubmit="return confirm('Da li ste sigurni da želite da raskinete prodaju ovog stana?');">
              <input type="hidden" name="akcija" value="raskini_prodaju">
              <input type="hidden" name="stan_id" value="<?= $stan_id ?>">
              <button type="submit" class="btn btn-warning">
                <i class="fas fa-undo"></i> Raskini prodaju
              </button>
            </form>
          <?php endif; ?>

          <form method="POST" id="forma">
            <fieldset <?= $stanJeProdat ? 'disabled' : '' ?>>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="objekat_id" class="col-form-label"><i class="far fa-building"></i> Objekat</label>
                <select class="form-control" id="objekat_id" name="objekat_id" required>
                  <option value="">Izaberite objekat</option>
                  <?php foreach ($objekti as $objekat) : ?>
                    <option value="<?= $objekat['id'] ?>" <?= $objekat['id'] == $stan['objekat_id'] ? 'selected' : '' ?>><?= htmlspecialchars($objekat['naziv']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="kvadratura" class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                <input class="form-control" id="kvadratura" name="kvadratura" type="number" step="0.01" min="0" value="<?= htmlspecialchars($stan['kvadratura']) ?>" required>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-12 col-sm-12 mb-3">
                <label for="naziv" class="col-form-label"><i class="fas fa-tag"></i> Naziv stana</label>
                <input class="form-control" id="naziv" name="naziv" type="text" maxlength="100" value="<?= htmlspecialchars($stan['naziv']) ?>" required>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-3 col-sm-12 mb-3">
                <label for="realna_cena_po_m2" class="col-form-label"><i class="fas fa-chart-line"></i> Cena po m² sa PDV (€)</label>
                <input class="form-control" id="realna_cena_po_m2" name="realna_cena_po_m2" type="number" step="0.01" min="0" value="<?= htmlspecialchars($stan['realna_cena_po_m2'] ?? 0) ?>">
              </div>
              <div class="col-md-3 col-sm-12 mb-3">
                <label for="cena_po_m2" class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² (€)</label>
                <input class="form-control" id="cena_po_m2" name="cena_po_m2" type="number" step="0.01" min="0" value="<?= htmlspecialchars($stan['cena_po_m2']) ?>" required>
              </div>
              <div class="col-md-3 col-sm-12 mb-3">
                <label for="pdv" class="col-form-label"><i class="fas fa-percentage"></i> PDV SUMA €</label>
                <input class="form-control" id="pdv" name="pdv" type="number" step="0.01" min="0" value="<?= htmlspecialchars($stan['pdv']) ?>">
              </div>
              <div class="col-md-3 col-sm-12 mb-3">
                <label for="povrat_pdv" class="col-form-label"><i class="fas fa-undo"></i> Povrat PDV (€)</label>
                <input class="form-control" id="povrat_pdv" name="povrat_pdv" type="number" step="0.01" min="0" readonly>
                <small class="form-text text-muted">Automatski se računa za prvih 40m²</small>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="pdv_procenat" class="col-form-label"><i class="fas fa-percentage"></i> PDV PROCENT %</label>
                <input class="form-control" id="pdv_procenat" name="pdv_procenat" type="number" step="0.01" min="0" max="100" value="10">
              </div>
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="rabat" class="col-form-label"><i class="fas fa-tag"></i> Rabat (€)</label>
                <input class="form-control" id="rabat" name="rabat" type="number" step="0.01" min="0" value="<?= htmlspecialchars($stan['rabat']) ?>">
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-12 col-sm-12 mb-3">
                <label for="ukupna_cena" class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                <input class="form-control" id="ukupna_cena" name="ukupna_cena" type="number" step="0.01" min="0" value="<?= htmlspecialchars($stan['ukupna_cena']) ?>" readonly>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-3">
                <label for="parking_mesto_id" class="col-form-label" id="parking_mesto_label" style="<?= $stan['vezano_parking'] ? '' : 'display: none;' ?>"><i class="fas fa-parking"></i> Parking mesto</label>
                <select class="form-control" id="parking_mesto_id" name="parking_mesto_id" style="<?= $stan['vezano_parking'] ? '' : 'display: none;' ?>">
                  <option value="">Izaberite parking mesto</option>
                  <?php
                  // Učitaj parking mesta za objekat
                  $parkingCrud = new CRUD($_SESSION['godina']);
                  $parkingCrud->table = "parking_mesta";
                  $parking_mesta = $parkingCrud->select(['*'], ['objekat_id' => $stan['objekat_id']]);
                  foreach ($parking_mesta as $parking) :
                    $parkingCena = isset($parking['cena_sa_pdv']) ? $parking['cena_sa_pdv'] : ($parking['cena'] ?? 0);
                  ?>
                    <option value="<?= $parking['id'] ?>" data-cena="<?= htmlspecialchars($parkingCena) ?>" <?= $parking['id'] == ($stan['vezano_parking']['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($parking['naziv']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-12 col-sm-12 mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="prodaje_sa_parking_mestom" name="prodaje_sa_parking_mestom" value="1" <?= ((int)($stan['prodaje_sa_parking_mestom'] ?? 0) === 1 || $stan['vezano_parking']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="prodaje_sa_parking_mestom">
                    <i class="fas fa-parking"></i> Prodaje se sa parking mestom
                  </label>
                </div>
              </div>
            </div>

            <!-- Status prodaje i kanal -->
            <div class="card mb-4">
              <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Status prodaje i kanal</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-4 col-sm-12 mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="kompenzacija" name="kompenzacija" value="1" <?= ((int)($stan['kompenzacija'] ?? 0) === 1) ? 'checked' : '' ?>>
                      <label class="form-check-label" for="kompenzacija">
                        <i class="fas fa-exchange-alt"></i> Kompenzacija
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="lokacija" name="lokacija" value="1" <?= ((int)($stan['lokacija'] ?? 0) === 1) ? 'checked' : '' ?>>
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
                        <option value="<?= $kanal['id'] ?>" <?= $kanal['id'] == ($stan['kanal_prodaje_id'] ?? null) ? 'selected' : '' ?>><?= htmlspecialchars($kanal['naziv']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <?php $prikaziKupca = ((int)($stan['kompenzacija'] ?? 0) === 1) || ((int)($stan['lokacija'] ?? 0) === 1); ?>
                <div class="form-group row" id="kupac_row" style="<?= $prikaziKupca ? '' : 'display: none;' ?>">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="kupac_id" class="col-form-label"><i class="fas fa-user"></i> Kupac</label>
                    <select class="form-control select2-kupci" id="kupac_id" name="kupac_id">
                      <option value="">Izaberite kupca</option>
                      <?php foreach ($kupciLista as $kupacOpcija) : ?>
                        <option value="<?= $kupacOpcija['id'] ?>" <?= $kupacOpcija['id'] == ($stan['kupac_id'] ?? null) ? 'selected' : '' ?>>
                          <?= htmlspecialchars(trim(($kupacOpcija['ime'] ?? '') . ' ' . ($kupacOpcija['prezime'] ?? ''))) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_prodaje" class="col-form-label"><i class="fas fa-calendar"></i> Datum prodaje</label>
                    <input class="form-control" id="datum_prodaje" name="datum_prodaje" type="date" value="<?= !empty($stan['datum_prodaje']) ? htmlspecialchars(date('Y-m-d', strtotime($stan['datum_prodaje']))) : '' ?>">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_predugovora" class="col-form-label"><i class="fas fa-file-contract"></i> Datum predugovora</label>
                    <input class="form-control" id="datum_predugovora" name="datum_predugovora" type="date" value="<?= !empty($stan['datum_predugovora']) ? htmlspecialchars(date('Y-m-d', strtotime($stan['datum_predugovora']))) : '' ?>">
                  </div>
                </div>
              </div>
            </div>

            <!-- Prikaz kupca ako je stan prodat -->
            <?php if ($kupac): ?>
            <div class="form-group row">
              <div class="col-md-12 col-sm-12 mb-3">
                <div class="alert bg-white border border-secondary">
                  <h6><i class="fas fa-user"></i> Informacije o kupcu</h6>
                  <div class="p-3 mb-3 bg-light border rounded text-dark">
                    <p class="mb-1"><strong>Kupac:</strong> <?= htmlspecialchars($kupac['ime'] . ' ' . $kupac['prezime']) ?></p>
                    <p class="mb-1"><strong>Telefon:</strong> <?= htmlspecialchars($kupac['br_telefona']) ?></p>
                    <?php if ($kupac['email']): ?>
                      <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($kupac['email']) ?></p>
                    <?php endif; ?>
                    <?php if ($stan['datum_prodaje']): ?>
                      <p class="mb-1"><strong>Datum prodaje:</strong> <?= formatirajDatum($stan['datum_prodaje']) ?></p>
                    <?php endif; ?>
                    <?php if ($stan['datum_predugovora']): ?>
                      <p class="mb-0"><strong>Datum predugovora:</strong> <?= formatirajDatum($stan['datum_predugovora']) ?></p>
                    <?php endif; ?>
                  </div>
                  <?php if ($planOtplate['ima_plan']): ?>
                    <hr class="my-2">
                    <h6 class="mb-2"><i class="fas fa-list-alt"></i> Plan otplate</h6>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered mb-2">
                        <thead class="thead-light">
                          <tr>
                            <th>Datum rate</th>
                            <th class="text-right">Iznos rate (€)</th>
                            <th class="text-right">Uplaćeno (€)</th>
                            <th class="text-right">Preostalo (€)</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($planOtplate['stavke'] as $stavka): ?>
                            <?php $rowClass = $stavka['preostalo'] > 0.01 ? 'table-warning' : 'table-success'; ?>
                            <tr class="<?= $rowClass ?>">
                              <td><?= htmlspecialchars($stavka['datum'] ?: 'N/A') ?></td>
                              <td class="text-right"><?= number_format($stavka['suma'], 2, ',', '.') ?></td>
                              <td class="text-right"><?= number_format($stavka['uplaceno'], 2, ',', '.') ?></td>
                              <td class="text-right"><?= number_format($stavka['preostalo'], 2, ',', '.') ?></td>
                              <td><?= htmlspecialchars($stavka['status'] ? strtoupper($stavka['status']) : ($stavka['preostalo'] > 0.01 ? 'DELIMIČNO' : 'PLAĆENO')) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                          <tr class="font-weight-bold">
                            <td>Ukupno</td>
                            <td class="text-right"><?= number_format($planOtplate['ukupno'], 2, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($planOtplate['uplaceno'], 2, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($planOtplate['preostalo'], 2, ',', '.') ?></td>
                            <td></td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  <?php else: ?>
                    <hr class="my-2">
                    <p class="mb-0 text-muted"><small>Za ovog kupca nije evidentiran plan otplate za odabrani stan.</small></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <div class="form-group row">
              <div class="col-md-6 col-sm-12 mb-2">
                <button type="submit" class="btn btn-success btn-lg w-100" <?= $stanJeProdat ? 'disabled' : '' ?>> <i class="fas fa-save"></i> <br>
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
