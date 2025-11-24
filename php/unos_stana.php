<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}

// Obrada forme za unos stana
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['objekat_id'])) {
    try {
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "stanovi";

        // Validacija i provera duplikata naziva
        $naziv = isset($_POST['naziv']) ? trim($_POST['naziv']) : '';
        $sprat = isset($_POST['sprat']) ? trim($_POST['sprat']) : null;
        $poslovni_apartman = isset($_POST['poslovni_apartman']) ? 1 : 0;
        $kompenzacija = isset($_POST['kompenzacija']) ? 1 : 0;
        $lokacija = isset($_POST['lokacija']) ? 1 : 0;
        
        if ($naziv === '') {
            $_SESSION['poruka'] = "Greška: Naziv stana je obavezan.";
        } elseif ($kompenzacija && $lokacija) {
            $_SESSION['poruka'] = "Greška: Stan ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.";
        } else {
            $check = new CRUD($_SESSION['godina']);
            $check->table = "stanovi";
            $duplikat = $check->select(['id'], ['objekat_id' => $_POST['objekat_id'], 'naziv' => $naziv]);
            if (!empty($duplikat)) {
                $_SESSION['poruka'] = "Greška: Već postoji stan sa tim nazivom u izabranom objektu.";
            } else {
                // Automatsko označavanje kao prodat ako je kompenzacija ili lokacija
                $prodat = ($kompenzacija || $lokacija) ? 1 : 0;
                
                // Automatsko postavljanje kanala prodaje
                $kanal_prodaje_id = $_POST['kanal_prodaje_id'] ?? null;
                if ($kompenzacija) {
                    // Pronađi ID za "Kompenzacija" kanal
                    $kanalCrud = new CRUD($_SESSION['godina']);
                    $kanalCrud->table = "kanal_prodaje";
                    $kompenzacija_kanal = $kanalCrud->select(['id'], ['naziv' => 'Kompenzacija']);
                    if (!empty($kompenzacija_kanal)) {
                        $kanal_prodaje_id = $kompenzacija_kanal[0]['id'];
                    }
                } elseif ($lokacija) {
                    // Pronađi ID za "Lokacija" kanal
                    $kanalCrud = new CRUD($_SESSION['godina']);
                    $kanalCrud->table = "kanal_prodaje";
                    $lokacija_kanal = $kanalCrud->select(['id'], ['naziv' => 'Lokacija']);
                    if (!empty($lokacija_kanal)) {
                        $kanal_prodaje_id = $lokacija_kanal[0]['id'];
                    }
                }
                
                 $data = [
                     'objekat_id' => $_POST['objekat_id'],
                     'naziv' => $naziv,
                     'sprat' => $sprat,
                     'poslovni_apartman' => $poslovni_apartman,
                     'kvadratura' => $_POST['kvadratura'],
                     'cena_po_m2' => $_POST['cena_po_m2'],
                     'pdv' => $_POST['pdv'] ?? 0,
                     'rabat' => $_POST['rabat'] ?? 0,
                     'prodaje_sa_parking_mestom' => isset($_POST['prodaje_sa_parking_mestom']) ? 1 : 0,
                     'ukupna_cena' => $_POST['ukupna_cena'],
                     'realna_cena_po_m2' => $_POST['realna_cena_po_m2'] ?? 0,
                     'kompenzacija' => $kompenzacija,
                     'lokacija' => $lokacija,
                     'kanal_prodaje_id' => $kanal_prodaje_id,
                     'prodat' => $prodat,
                     'kupac_id' => ($kompenzacija || $lokacija) ? ($_POST['kupac_id'] ?? null) : null,
                     'datum_prodaje' => ($kompenzacija || $lokacija) ? ($_POST['datum_prodaje'] ?? null) : null,
                     'datum_predugovora' => ($kompenzacija || $lokacija) ? ($_POST['datum_predugovora'] ?? null) : null
                 ];

                $result = $podatci->insert($data);
            }
        }
        
        if (isset($result) && $result) {
            // Prikaz kreiranih se računa u glavni.php preko COUNT podupita
            // Ako se prodaje sa parking mestom, ažuriraj parking mesto da pokazuje na stan
            if (isset($_POST['prodaje_sa_parking_mestom']) && !empty($_POST['parking_mesto_id'])) {
                $parking_mesto_id = $_POST['parking_mesto_id'];
                $stan_id = $result; // ID novog stana
                
                // Ažuriraj parking mesto da pokazuje na stan i označi kao prodato
                $parkingCrud = new CRUD($_SESSION['godina']);
                $parkingCrud->table = "parking_mesta";
                $parkingUpdate = $parkingCrud->update(
                    ['stan_id' => $stan_id, 'prodat' => 1],
                    ['id' => $parking_mesto_id]
                );
            }
            
            // Ažuriraj broj_kvadrata u tabeli objekti - na kraju, nakon svih ostalih operacija
            $objekatCrud = new CRUD($_SESSION['godina']);
            $objekatCrud->table = "objekti";
            
            // Uzmi trenutnu vrednost broj_kvadrata
            $trenutniObjekat = $objekatCrud->select(['broj_kvadrata'], ['id' => $_POST['objekat_id']]);
            $trenutnaKvadratura = isset($trenutniObjekat[0]['broj_kvadrata']) ? floatval($trenutniObjekat[0]['broj_kvadrata']) : 0;
            $novaKvadratura = floatval($_POST['kvadratura']);
            $ukupnaKvadratura = $trenutnaKvadratura + $novaKvadratura;
            
            // Ažuriraj objekat sa novom ukupnom kvadraturom
            $objekatCrud->update(
                ['broj_kvadrata' => $ukupnaKvadratura],
                ['id' => $_POST['objekat_id']]
            );
            
            $_SESSION['poruka'] = "Stan je uspešno dodat!";
        } else {
            $_SESSION['poruka'] = "Greška pri dodavanju stana!";
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


  <title>GP RAZ - Stan - Unos</title>

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
    
     // Učitavanje garaža za izabrani objekat
     function loadParkingMesta(objekatId) {
       if (objekatId) {
         $.ajax({
           url: 'get_parking_mesta.php',
           method: 'POST',
           data: { objekat_id: objekatId },
           dataType: 'json',
           success: function(response) {
             const parkingSelect = $('#parking_mesto_id');
             parkingSelect.empty();
             parkingSelect.append('<option value="">Izaberite parking mesto</option>');
             
             if (response.success && response.parking_mesta.length > 0) {
               response.parking_mesta.forEach(function(parking) {
                 parkingSelect.append(`<option value="${parking.id}" data-cena="${parking.cena}">${parking.naziv}</option>`);
               });
             } else {
               parkingSelect.append('<option value="">Nema dostupnih parking mesta</option>');
             }
           },
           error: function() {
             $('#parking_mesto_id').empty().append('<option value="">Greška pri učitavanju parking mesta</option>');
           }
         });
       }
     }
     
     // Računanje ukupne cene sa parking mestom
     function calculateTotalPriceWithParking() {
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
     
     // Računanje realne cene po m²
     function calculateRealPricePerM2() {
       const ukupnaCena = parseFloat($('#ukupna_cena').val()) || 0;
       const kvadratura = parseFloat($('#kvadratura').val()) || 0;
       
       if (kvadratura > 0) {
         const realnaCena = ukupnaCena / kvadratura;
         $('#realna_cena_po_m2').val(realnaCena.toFixed(2));
       } else {
         $('#realna_cena_po_m2').val('0.00');
       }
       
       // Računaj povrat PDV-a
       calculatePovratPDV();
     }
     
     // Računanje povrata PDV-a (samo za prvih 40m²)
     function calculatePovratPDV() {
       const kvadratura = parseFloat($('#kvadratura').val()) || 0;
       const cenaPoM2 = parseFloat($('#cena_po_m2').val()) || 0;
       const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
       const rabat = parseFloat($('#rabat').val()) || 0;
       
       if (kvadratura > 0 && cenaPoM2 > 0) {
         // Računaj povrat PDV-a samo za prvih 40m²
         const kvadraturaZaPovrat = Math.min(kvadratura, 40);
         
         // Osnovica za povrat (bez rabata)
         const osnovicaZaPovrat = kvadraturaZaPovrat * cenaPoM2;
         
         // PDV za povrat
         const povratPDV = (osnovicaZaPovrat * pdvProcenat) / 100;
         
         $('#povrat_pdv').val(povratPDV.toFixed(2));
       } else {
         $('#povrat_pdv').val('0.00');
       }
     }
     
     // Računanje na osnovu ukupne cene
     function calculateFromTotalPrice() {
       const ukupnaCena = parseFloat($('#ukupna_cena').val()) || 0;
       const kvadratura = parseFloat($('#kvadratura').val()) || 0;
       const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
       const rabat = parseFloat($('#rabat').val()) || 0;
       const parkingCena = parseFloat($('#parking_mesto_id option:selected').data('cena')) || 0;
       
       if (kvadratura > 0) {
         // Oduzmi parking cenu od ukupne cene
         const ukupnaCenaBezParkinga = ukupnaCena - parkingCena;
         
         // Izračunaj osnovicu na osnovu ukupne cene i PDV procenta
         // ukupnaCena = osnovica + (osnovica * pdvProcenat/100)
         // ukupnaCena = osnovica * (1 + pdvProcenat/100)
         // osnovica = ukupnaCena / (1 + pdvProcenat/100)
         const osnovica = ukupnaCenaBezParkinga / (1 + pdvProcenat/100);
         
         // Dodaj rabat da dobiješ originalnu cenu po m²
        const osnovicaSaRabatom = osnovica - rabat;
         const cenaPoM2 = osnovicaSaRabatom / kvadratura;
         
         // Ažuriraj polja
         $('#cena_po_m2').val(cenaPoM2.toFixed(2));
         $('#pdv').val((osnovica * pdvProcenat / 100).toFixed(2));
         
         // Ažuriraj realnu cenu po m²
         const realnaCena = ukupnaCena / kvadratura;
         $('#realna_cena_po_m2').val(realnaCena.toFixed(2));
       } else {
         $('#cena_po_m2').val('0.00');
         $('#realna_cena_po_m2').val('0.00');
         $('#pdv').val('0.00');
       }
       
       // Računaj povrat PDV-a
       calculatePovratPDV();
     }
     
     // Računanje na osnovu cene po m² sa PDV
     function calculateFromRealPricePerM2() {
       const realnaCenaPoM2 = parseFloat($('#realna_cena_po_m2').val()) || 0;
       const kvadratura = parseFloat($('#kvadratura').val()) || 0;
       const pdvProcenat = parseFloat($('#pdv_procenat').val()) || 0;
       const rabat = parseFloat($('#rabat').val()) || 0;
       const parkingCena = parseFloat($('#parking_mesto_id option:selected').data('cena')) || 0;
       
       if (kvadratura > 0) {
         // Računaj ukupnu cenu na osnovu realne cene po m²
         const ukupnaCena = realnaCenaPoM2 * kvadratura;
         $('#ukupna_cena').val(ukupnaCena.toFixed(2));
         
         // Oduzmi parking cenu od ukupne cene
         const ukupnaCenaBezParkinga = ukupnaCena - parkingCena;
         
         // Izračunaj osnovicu na osnovu ukupne cene i PDV procenta
         const osnovica = ukupnaCenaBezParkinga / (1 + pdvProcenat/100);
         
         // Dodaj rabat da dobiješ originalnu cenu po m²
        const osnovicaSaRabatom = osnovica - rabat;
         const cenaPoM2 = osnovicaSaRabatom / kvadratura;
         
         // Ažuriraj polja
         $('#cena_po_m2').val(cenaPoM2.toFixed(2));
         $('#pdv').val((osnovica * pdvProcenat / 100).toFixed(2));
       } else {
         $('#ukupna_cena').val('0.00');
         $('#cena_po_m2').val('0.00');
         $('#pdv').val('0.00');
       }
       
       // Računaj povrat PDV-a
       calculatePovratPDV();
     }

    function updatePdvPercentForPoslovniApartman(shouldRecalculate) {
      const isPoslovniApartman = $('#poslovni_apartman').is(':checked');
      const defaultPdv = isPoslovniApartman ? 20 : 10;
      $('#pdv_procenat').val(defaultPdv);
      if (shouldRecalculate) {
        calculatePDVFromPercentage();
      }
    }
    
    // Funkcija za generisanje sledećeg naziva stana
    function generateNextStanName() {
      const objekatId = $('#objekat_id').val();
      if (objekatId) {
        $.ajax({
          url: 'get_next_stan_name.php',
          method: 'POST',
          data: { objekat_id: objekatId },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              $('#naziv').val(response.nextName);
            }
          },
          error: function() {
            console.log('Greška pri učitavanju sledećeg naziva stana');
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
         '#realna_cena_po_m2', '#pdv_procenat', '#pdv', '#cena_po_m2', 
         '#rabat', '#ukupna_cena', '#povrat_pdv'
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
     
     // Event listeners za automatsko računanje
     $(document).ready(function() {
       // Inicijalizacija Select2 za kupce
       $('.select2-kupci').select2({
         theme: 'bootstrap-5',
         placeholder: 'Izaberite kupca',
         allowClear: true,
         width: '100%'
       });
       
       // Inicijalno onemogući polja u drugoj sekciji
       toggleSecondSectionFields();
       // Event listener za kvadraturu - omogući/onemogući polja u drugoj sekciji
       $('#kvadratura').on('input', function() {
         toggleSecondSectionFields();
         
         const ukupnaCena = parseFloat($('#ukupna_cena').val()) || 0;
         const realnaCenaPoM2 = parseFloat($('#realna_cena_po_m2').val()) || 0;
         if (ukupnaCena > 0) {
           calculateFromTotalPrice();
         } else if (realnaCenaPoM2 > 0) {
           calculateFromRealPricePerM2();
         }
       });
       
       $('#cena_po_m2, #pdv').on('input', function() {
         if ($('#prodaje_sa_parking_mestom').is(':checked')) {
           calculateTotalPriceWithParking();
         } else {
           calculateTotalPrice();
         }
       });
       
       // Event listener za rabat - triggeruje preračunavanje PDV-a
       $('#rabat').on('input', function() {
         calculatePDVFromPercentage();
         if ($('#prodaje_sa_parking_mestom').is(':checked')) {
           calculateTotalPriceWithParking();
         } else {
           calculateTotalPrice();
         }
       });

      $('#poslovni_apartman').on('change', function() {
        updatePdvPercentForPoslovniApartman(true);
      });
       
       // Dinamičko računanje na osnovu ukupne cene
       $('#ukupna_cena').on('input', function() {
         calculateFromTotalPrice();
       });
       
       // Dinamičko računanje na osnovu cene po m² sa PDV
       $('#realna_cena_po_m2').on('input', function() {
         calculateFromRealPricePerM2();
       });
       
      
       // PDV procenti - računanje apsolutne vrednosti
       $('#pdv_procenat').on('input', function() {
         calculatePDVFromPercentage();
         if ($('#prodaje_sa_parking_mestom').is(':checked')) {
           calculateTotalPriceWithParking();
         } else {
           calculateTotalPrice();
         }
       });
       
       // PDV apsolutna vrednost - računanje procenta
       $('#pdv').on('input', function() {
         calculatePDVPercentage();
         if ($('#prodaje_sa_parking_mestom').is(':checked')) {
           calculateTotalPriceWithParking();
         } else {
           calculateTotalPrice();
         }
       });

      updatePdvPercentForPoslovniApartman(false);
      
      // Promena objekta - učitavanje garaža i generisanje naziva
      $('#objekat_id').on('change', function() {
        const objekatId = $(this).val();
        loadParkingMesta(objekatId);
        generateNextStanName();
      });
      
       // Checkbox za parking mesto
       $('#prodaje_sa_parking_mestom').on('change', function() {
         if ($(this).is(':checked')) {
           $('#parking_mesto_label, #parking_mesto_id').show();
           const objekatId = $('#objekat_id').val();
           if (objekatId) {
             loadParkingMesta(objekatId);
           }
         } else {
           $('#parking_mesto_label, #parking_mesto_id').hide();
           $('#parking_mesto_id').val('');
           // Vrati na obično računanje bez parking mesta
           calculateTotalPrice();
         }
       });
       
       // Promena parking mesta - ažuriraj ukupnu cenu
       $('#parking_mesto_id').on('change', function() {
         if ($('#prodaje_sa_parking_mestom').is(':checked')) {
           calculateTotalPriceWithParking();
         }
       });
      
      // Validacija kompenzacija/lokacija i automatsko postavljanje kanala prodaje
      $('#kompenzacija, #lokacija').on('change', function() {
        const kompenzacija = $('#kompenzacija').is(':checked');
        const lokacija = $('#lokacija').is(':checked');
        
        if (kompenzacija && lokacija) {
          alert('Stan ne može biti i kompenzacija i lokacija istovremeno. Izaberite samo jedno.');
          $(this).prop('checked', false);
        } else {
          // Prikaz/skrivanje dropdown-a za kupca
          if (kompenzacija || lokacija) {
            $('#kupac_row').show();
            $('#kupac_id').prop('required', true);
            $('#datum_prodaje').prop('required', true);
            // Ponovo inicijalizuj Select2 kada se prikaže
            $('#kupac_id').select2({
              theme: 'bootstrap-5',
              placeholder: 'Izaberite kupca',
              allowClear: true,
              width: '100%'
            });
          } else {
            $('#kupac_row').hide();
            $('#kupac_id').prop('required', false);
            $('#datum_prodaje').prop('required', false);
            $('#kupac_id').val('').trigger('change');
            $('#datum_prodaje').val('');
            $('#datum_predugovora').val('');
          }
          
          // Automatsko postavljanje kanala prodaje
          if (kompenzacija) {
            // Pronađi i postavi "Kompenzacija" kanal
            $('#kanal_prodaje_id option').each(function() {
              if ($(this).text().trim() === 'Kompenzacija') {
                $('#kanal_prodaje_id').val($(this).val());
                return false;
              }
            });
          } else if (lokacija) {
            // Pronađi i postavi "Lokacija" kanal
            $('#kanal_prodaje_id option').each(function() {
              if ($(this).text().trim() === 'Lokacija') {
                $('#kanal_prodaje_id').val($(this).val());
                return false;
              }
            });
          } else {
            // Ako nijedno nije označeno, vrati na prvu opciju
            $('#kanal_prodaje_id').val('');
          }
        }
      });
      
      // Validacija forme
      $('#forma').on('submit', function(e) {
        const objekatId = $('#objekat_id').val();
        const kvadratura = $('#kvadratura').val();
        const cenaPoM2 = $('#cena_po_m2').val();
        const prodajeSaParkingMestom = $('#prodaje_sa_parking_mestom').is(':checked');
        const parkingMestoId = $('#parking_mesto_id').val();
        const kompenzacija = $('#kompenzacija').is(':checked');
        const lokacija = $('#lokacija').is(':checked');
        const kupacId = $('#kupac_id').val();
        const datumProdaje = $('#datum_prodaje').val();
        
        if (!objekatId) {
          alert('Molimo izaberite objekat!');
          e.preventDefault();
          return false;
        }
        
        if (!kvadratura || kvadratura <= 0) {
          alert('Molimo unesite validnu kvadraturu!');
          e.preventDefault();
          return false;
        }
        
        if (!cenaPoM2 || cenaPoM2 <= 0) {
          alert('Molimo unesite validnu cenu po m²!');
          e.preventDefault();
          return false;
        }
        
        if (prodajeSaParkingMestom && !parkingMestoId) {
          alert('Molimo izaberite parking mesto!');
          e.preventDefault();
          return false;
        }
        
        if ((kompenzacija || lokacija) && !kupacId) {
          alert('Molimo izaberite kupca za kompenzaciju/lokaciju!');
          e.preventDefault();
          return false;
        }
        
        if ((kompenzacija || lokacija) && !datumProdaje) {
          alert('Molimo unesite datum prodaje za kompenzaciju/lokaciju!');
          e.preventDefault();
          return false;
        }
      });
    });
  </script>

  <style>
    /* Veći checkbox-ovi */
    .form-check-input[type="checkbox"] {
      width: 1.5rem;
      height: 1.5rem;
      margin-top: 0.25rem;
    }
    
    .form-check-label {
      font-size: 1.1rem;
      margin-left: 0.5rem;
      padding-top: 0.25rem;
    }
    
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

          <h3 class="center sekcija">Kreiranje nove stambene jedinice <i class="fas fa-house-user"></i></h3> <br>

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

            <!-- Osnovne informacije o stanu -->
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-home"></i> Osnovne informacije o stanu</h5>
              </div>
              <div class="card-body">
                <div class="form-group row">
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="objekat_id" class="col-form-label"><i class="far fa-building"></i> Objekat</label>
                    <select class="form-control" id="objekat_id" name="objekat_id" required>
                      <option value="">Izaberite objekat</option>
                      <?php
                      $podatci = new CRUD($_SESSION['godina']);
                      $podatci->table = "objekti";
                      $objekti = $podatci->select(['*'], [], "SELECT o.* FROM objekti o 
                        WHERE COALESCE(o.broj_stanova, 0) > (SELECT COUNT(*) FROM stanovi s WHERE s.objekat_id = o.id)
                        ORDER BY o.naziv ASC");
                      foreach ($objekti as $objekat) : 
                        $maxStanova = intval($objekat['broj_stanova']);
                        $stanCrud = new CRUD($_SESSION['godina']);
                        $stanCrud->table = "stanovi";
                        $trenutniStanovi = $stanCrud->select(['id'], ['objekat_id' => $objekat['id']]);
                        $trenutniBroj = count($trenutniStanovi);
                        ?>
                        <option value="<?= $objekat['id'] ?>"><?= $objekat['naziv'] ?> (<?= $trenutniBroj . '/' . $maxStanova ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="naziv" class="col-form-label"><i class="fas fa-tag"></i> Naziv stana</label>
                    <input class="form-control" id="naziv" name="naziv" type="text" maxlength="100" required placeholder="npr. S1, S2, S3, ...">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="sprat" class="col-form-label"><i class="fas fa-layer-group"></i> Sprat</label>
                    <input class="form-control" id="sprat" name="sprat" type="text" maxlength="50" placeholder="npr. 1, 2, 3, P+1, S+1...">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="kvadratura" class="col-form-label"><i class="fas fa-ruler-combined"></i> Kvadratura (m²)</label>
                    <input class="form-control" id="kvadratura" name="kvadratura" type="number" step="0.01" min="0" required>
                  </div>
                </div>
                <div class="form-group row">
                  <div class="col-md-3 col-sm-12 mb-3">
                    <div class="form-check mt-4">
                      <input class="form-check-input" type="checkbox" id="poslovni_apartman" name="poslovni_apartman" value="1">
                      <label class="form-check-label" for="poslovni_apartman">
                        <i class="fas fa-briefcase"></i> Poslovni apartman
                      </label>
                    </div>
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
                    <label for="realna_cena_po_m2" class="col-form-label"><i class="fas fa-chart-line"></i> Cena po m² sa PDV (€)</label>
                    <input class="form-control" id="realna_cena_po_m2" name="realna_cena_po_m2" type="number" step="0.01" min="0">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="pdv_procenat" class="col-form-label"><i class="fas fa-percentage"></i> PDV PROCENT %</label>
                    <input class="form-control" id="pdv_procenat" name="pdv_procenat" type="number" step="0.01" min="0" max="100" value="10">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="pdv" class="col-form-label"><i class="fas fa-percentage"></i> PDV SUMA €</label>
                    <input class="form-control" id="pdv" name="pdv" type="number" step="0.01" min="0" value="0">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="povrat_pdv" class="col-form-label"><i class="fas fa-undo"></i> Povrat PDV (€)</label>
                    <input class="form-control" id="povrat_pdv" name="povrat_pdv" type="number" step="0.01" min="0" readonly>
                    <small class="form-text text-muted">Automatski se računa za prvih 40m²</small>
                  </div>
                </div>
                
                <div class="form-group row">
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="cena_po_m2" class="col-form-label"><i class="fas fa-euro-sign"></i> Cena po m² (€)</label>
                    <input class="form-control" id="cena_po_m2" name="cena_po_m2" type="number" step="0.01" min="0" required>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="rabat" class="col-form-label"><i class="fas fa-tag"></i> Rabat (€)</label>
                    <input class="form-control" id="rabat" name="rabat" type="number" step="0.01" min="0" value="0">
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="ukupna_cena" class="col-form-label"><i class="fas fa-calculator"></i> Ukupna cena (€)</label>
                    <input class="form-control" id="ukupna_cena" name="ukupna_cena" type="number" step="0.01" min="0" placeholder="Unesite ukupnu cenu">
                    <small class="form-text text-muted">Možete uneti direktno ili se računa automatski</small>
                  </div>
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
                      <input class="form-check-input" type="checkbox" id="kompenzacija" name="kompenzacija" value="1">
                      <label class="form-check-label" for="kompenzacija">
                        <i class="fas fa-exchange-alt"></i> Kompenzacija
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="lokacija" name="lokacija" value="1">
                      <label class="form-check-label" for="lokacija">
                        <i class="fas fa-map-marker-alt"></i> Lokacija
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-12 mb-3">
                    <label for="kanal_prodaje_id" class="col-form-label"><i class="fas fa-shopping-cart"></i> Kanal prodaje</label>
                    <select class="form-control" id="kanal_prodaje_id" name="kanal_prodaje_id">
                      <option value="">Izaberite kanal prodaje</option>
                      <?php
                      $kanalCrud = new CRUD($_SESSION['godina']);
                      $kanalCrud->table = "kanal_prodaje";
                      $kanali = $kanalCrud->select(['*'], [], "SELECT * FROM kanal_prodaje ORDER BY id ASC");
                      foreach ($kanali as $kanal) : ?>
                        <option value="<?= $kanal['id'] ?>" <?= $kanal['id'] == 1 ? 'selected' : '' ?>><?= htmlspecialchars($kanal['naziv']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                
                <!-- Dropdown za kupca - prikazuje se samo za kompenzaciju/lokaciju -->
                <div class="form-group row" id="kupac_row" style="display: none;">
                  <div class="col-md-6 col-sm-12 mb-3">
                    <label for="kupac_id" class="col-form-label"><i class="fas fa-user"></i> Kupac</label>
                    <select class="form-control select2-kupci" id="kupac_id" name="kupac_id">
                      <option value="">Izaberite kupca</option>
                      <?php
                      $kupacCrud = new CRUD($_SESSION['godina']);
                      $kupacCrud->table = "kupci";
                      $kupci = $kupacCrud->select(['*'], [], "SELECT * FROM kupci ORDER BY ime ASC, prezime ASC");
                      foreach ($kupci as $kupac) : ?>
                        <option value="<?= $kupac['id'] ?>"><?= htmlspecialchars($kupac['ime'] . ' ' . $kupac['prezime']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_prodaje" class="col-form-label"><i class="fas fa-calendar"></i> Datum prodaje</label>
                    <input class="form-control" id="datum_prodaje" name="datum_prodaje" type="date">
                  </div>
                  <div class="col-md-3 col-sm-12 mb-3">
                    <label for="datum_predugovora" class="col-form-label"><i class="fas fa-file-contract"></i> Datum predugovora</label>
                    <input class="form-control" id="datum_predugovora" name="datum_predugovora" type="date">
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