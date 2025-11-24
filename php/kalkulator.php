<?php
require  'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Kalkulator cena</title>

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
     // ========================================
     // GENERIČKE FUNKCIJE ZA KALKULACIJU
     // ========================================
     
     // Generička funkcija za kalkulaciju (refaktorisano za obe forme)
     class PriceCalculator {
       constructor(formId) {
         this.formId = formId;
         this.prefix = formId === 1 ? '' : '_2';
         this.kvadraturaId = formId === 1 ? '#kvadratura' : '#kvadratura2';
         this.isCalculating = false; // Zaštita od beskonačnih petlji
       }
       
       // Validacija unosa
       validateInput(value, min = 0, max = Infinity) {
         const num = parseFloat(value);
         if (isNaN(num) || num < min || num > max) {
           return 0;
         }
         return num;
       }
       
       // Dohvatanje vrednosti
       getValues() {
         return {
           kvadratura: this.validateInput($(this.kvadraturaId).val(), 0, 10000),
           cenaPoM2: this.validateInput($('#cena_po_m2' + this.prefix).val(), 0),
           pdv: this.validateInput($('#pdv' + this.prefix).val(), 0),
           pdvProcenat: this.validateInput($('#pdv_procenat' + this.prefix).val(), 0, 100),
           rabat: this.validateInput($('#rabat' + this.prefix).val(), 0),
           ukupnaCena: this.validateInput($('#ukupna_cena' + this.prefix).val(), 0),
           realnaCenaPoM2: this.validateInput($('#realna_cena_po_m2' + this.prefix).val(), 0)
         };
       }
       
       // Setovanje vrednosti
       setValue(fieldName, value) {
         $('#' + fieldName + this.prefix).val(value.toFixed(2));
       }
       
       // Računanje ukupne cene
       calculateTotalPrice() {
         if (this.isCalculating) return;
         this.isCalculating = true;
         
         const v = this.getValues();
        const osnovnaCena = (v.kvadratura * v.cenaPoM2) + v.rabat;
         const ukupnaCena = osnovnaCena + v.pdv;
         
         this.setValue('ukupna_cena', ukupnaCena);
         this.calculateRealPricePerM2();
         this.calculatePovratPDV();
         
         this.isCalculating = false;
       updateDifferences();
       }
       
       // Računanje PDV-a iz procenta
       calculatePDVFromPercentage() {
         if (this.isCalculating) return;
         this.isCalculating = true;
         
         const v = this.getValues();
        const osnovnaCena = (v.kvadratura * v.cenaPoM2) + v.rabat;
         const pdvVrednost = (osnovnaCena * v.pdvProcenat) / 100;
         
         this.setValue('pdv', pdvVrednost);
        this.isCalculating = false;
        this.calculateTotalPrice();
       }
       
       // Računanje PDV procenta iz apsolutne vrednosti
       calculatePDVPercentage() {
         if (this.isCalculating) return;
         this.isCalculating = true;
         
         const v = this.getValues();
        const osnovnaCena = (v.kvadratura * v.cenaPoM2) + v.rabat;
         const pdvProcenat = osnovnaCena > 0 ? (v.pdv / osnovnaCena) * 100 : 0;
         
         this.setValue('pdv_procenat', pdvProcenat);
         
         this.isCalculating = false;
       }
       
       // Računanje realne cene po m²
       calculateRealPricePerM2() {
         const v = this.getValues();
         
         if (v.kvadratura > 0) {
           const realnaCena = v.ukupnaCena / v.kvadratura;
           this.setValue('realna_cena_po_m2', realnaCena);
         } else {
           this.setValue('realna_cena_po_m2', 0);
         }
       }
       
       // Računanje povrata PDV-a (samo za prvih 40m²)
       calculatePovratPDV() {
         const v = this.getValues();
         
         if (v.kvadratura > 0 && v.cenaPoM2 > 0) {
           const kvadraturaZaPovrat = Math.min(v.kvadratura, 40);
           const osnovicaZaPovrat = kvadraturaZaPovrat * v.cenaPoM2;
           const povratPDV = (osnovicaZaPovrat * v.pdvProcenat) / 100;
           
           this.setValue('povrat_pdv', povratPDV);
         } else {
           this.setValue('povrat_pdv', 0);
         }
       }
       
       // Računanje na osnovu ukupne cene
       calculateFromTotalPrice() {
         if (this.isCalculating) return;
         this.isCalculating = true;
         
         const v = this.getValues();
         
         if (v.kvadratura > 0) {
           const osnovica = v.ukupnaCena / (1 + v.pdvProcenat/100);
          const osnovicaSaRabatom = osnovica - v.rabat;
           const cenaPoM2 = osnovicaSaRabatom / v.kvadratura;
           
           this.setValue('cena_po_m2', cenaPoM2);
           this.setValue('pdv', osnovica * v.pdvProcenat / 100);
           
           const realnaCena = v.ukupnaCena / v.kvadratura;
           this.setValue('realna_cena_po_m2', realnaCena);
         } else {
           this.setValue('cena_po_m2', 0);
           this.setValue('realna_cena_po_m2', 0);
           this.setValue('pdv', 0);
         }
         
         this.calculatePovratPDV();
        this.isCalculating = false;
        updateDifferences();
       }
       
       // Računanje na osnovu cene po m² sa PDV
       calculateFromRealPricePerM2() {
         if (this.isCalculating) return;
         this.isCalculating = true;
         
         const v = this.getValues();
         
         if (v.kvadratura > 0) {
           const ukupnaCena = v.realnaCenaPoM2 * v.kvadratura;
           this.setValue('ukupna_cena', ukupnaCena);
           
           const osnovica = ukupnaCena / (1 + v.pdvProcenat/100);
          const osnovicaSaRabatom = osnovica - v.rabat;
           const cenaPoM2 = osnovicaSaRabatom / v.kvadratura;
           
           this.setValue('cena_po_m2', cenaPoM2);
           this.setValue('pdv', osnovica * v.pdvProcenat / 100);
         } else {
           this.setValue('ukupna_cena', 0);
           this.setValue('cena_po_m2', 0);
           this.setValue('pdv', 0);
         }
         
         this.calculatePovratPDV();
        this.isCalculating = false;
        updateDifferences();
       }
       
       // Resetovanje forme
       reset() {
         $(this.kvadraturaId).val('');
         $('#cena_po_m2' + this.prefix).val('');
         $('#rabat' + this.prefix).val('0');
         $('#ukupna_cena' + this.prefix).val('');
         $('#realna_cena_po_m2' + this.prefix).val('');
         $('#pdv' + this.prefix).val('0');
         $('#pdv_procenat' + this.prefix).val('10');
         $('#povrat_pdv' + this.prefix).val('0.00');
        updateDifferences();
       }
     }
     
     // ========================================
     // INICIJALIZACIJA I EVENT LISTENERI
     // ========================================
     
     let calc1, calc2;
     let updateTimeout1, updateTimeout2;
     
     $(document).ready(function() {
       // Kreiraj kalkulatore
       calc1 = new PriceCalculator(1);
       calc2 = new PriceCalculator(2);
       
       // Inicijalno računanje
       calc1.calculateTotalPrice();
       calc2.calculateTotalPrice();
      updateDifferences();
       
       // ========================================
       // EVENT LISTENERS ZA PRVU FORMU
       // ========================================
       
       $('#kvadratura').on('input', function() {
         clearTimeout(updateTimeout1);
         updateTimeout1 = setTimeout(function() {
           const v = calc1.getValues();
           if (v.ukupnaCena > 0) {
             calc1.calculateFromTotalPrice();
           } else if (v.realnaCenaPoM2 > 0) {
             calc1.calculateFromRealPricePerM2();
           } else {
             calc1.calculateTotalPrice();
           }
         }, 100);
       });
       
       $('#cena_po_m2').on('input', function() {
         clearTimeout(updateTimeout1);
         updateTimeout1 = setTimeout(function() {
           calc1.calculatePDVFromPercentage();
         }, 100);
       });
       
       $('#rabat').on('input', function() {
         clearTimeout(updateTimeout1);
         updateTimeout1 = setTimeout(function() {
           calc1.calculatePDVFromPercentage();
         }, 100);
       });
       
       $('#ukupna_cena').on('input', function() {
         clearTimeout(updateTimeout1);
         updateTimeout1 = setTimeout(function() {
           calc1.calculateFromTotalPrice();
         }, 100);
       });
       
       $('#realna_cena_po_m2').on('input', function() {
         clearTimeout(updateTimeout1);
         updateTimeout1 = setTimeout(function() {
           calc1.calculateFromRealPricePerM2();
         }, 100);
       });
       
       $('#pdv_procenat').on('input', function() {
         clearTimeout(updateTimeout1);
         updateTimeout1 = setTimeout(function() {
           calc1.calculatePDVFromPercentage();
         }, 100);
       });
       
       let lastPDVValue = 0;
       $('#pdv').on('focus', function() {
         lastPDVValue = parseFloat($(this).val()) || 0;
       });
       
       $('#pdv').on('input', function() {
         clearTimeout(updateTimeout1);
         const currentValue = parseFloat($(this).val()) || 0;
         
         // Proveri da li je korisnik ručno menjao PDV
         if (Math.abs(currentValue - lastPDVValue) > 0.01) {
           updateTimeout1 = setTimeout(function() {
             calc1.calculatePDVPercentage();
             calc1.calculateTotalPrice();
           }, 100);
         }
         lastPDVValue = currentValue;
       });
       
       // ========================================
       // EVENT LISTENERS ZA DRUGU FORMU
       // ========================================
       
       $('#kvadratura2').on('input', function() {
         clearTimeout(updateTimeout2);
         updateTimeout2 = setTimeout(function() {
           const v = calc2.getValues();
           if (v.ukupnaCena > 0) {
             calc2.calculateFromTotalPrice();
           } else if (v.realnaCenaPoM2 > 0) {
             calc2.calculateFromRealPricePerM2();
           } else {
             calc2.calculateTotalPrice();
           }
         }, 100);
       });
       
       $('#cena_po_m2_2').on('input', function() {
         clearTimeout(updateTimeout2);
         updateTimeout2 = setTimeout(function() {
           calc2.calculatePDVFromPercentage();
         }, 100);
       });
       
       $('#rabat_2').on('input', function() {
         clearTimeout(updateTimeout2);
         updateTimeout2 = setTimeout(function() {
           calc2.calculatePDVFromPercentage();
         }, 100);
       });
       
       $('#ukupna_cena_2').on('input', function() {
         clearTimeout(updateTimeout2);
         updateTimeout2 = setTimeout(function() {
           calc2.calculateFromTotalPrice();
         }, 100);
       });
       
       $('#realna_cena_po_m2_2').on('input', function() {
         clearTimeout(updateTimeout2);
         updateTimeout2 = setTimeout(function() {
           calc2.calculateFromRealPricePerM2();
         }, 100);
       });
       
       $('#pdv_procenat_2').on('input', function() {
         clearTimeout(updateTimeout2);
         updateTimeout2 = setTimeout(function() {
           calc2.calculatePDVFromPercentage();
         }, 100);
       });
       
       let lastPDVValue2 = 0;
       $('#pdv_2').on('focus', function() {
         lastPDVValue2 = parseFloat($(this).val()) || 0;
       });
       
       $('#pdv_2').on('input', function() {
         clearTimeout(updateTimeout2);
         const currentValue = parseFloat($(this).val()) || 0;
         
         if (Math.abs(currentValue - lastPDVValue2) > 0.01) {
           updateTimeout2 = setTimeout(function() {
             calc2.calculatePDVPercentage();
             calc2.calculateTotalPrice();
           }, 100);
         }
         lastPDVValue2 = currentValue;
       });
     });
     
     // ========================================
     // POMOĆNE FUNKCIJE
     // ========================================
     
     // Funkcija za kopiranje podataka između kalkulatora
     function copyCalculator(from, to) {
       if (from === 1) {
         const v = calc1.getValues();
         $('#kvadratura2').val(v.kvadratura > 0 ? v.kvadratura : '');
         $('#cena_po_m2_2').val(v.cenaPoM2 > 0 ? v.cenaPoM2 : '');
         $('#pdv_procenat_2').val(v.pdvProcenat > 0 ? v.pdvProcenat : '10');
         $('#rabat_2').val(v.rabat > 0 ? v.rabat : '0');
         calc2.calculatePDVFromPercentage();
       } else {
         const v = calc2.getValues();
         $('#kvadratura').val(v.kvadratura > 0 ? v.kvadratura : '');
         $('#cena_po_m2').val(v.cenaPoM2 > 0 ? v.cenaPoM2 : '');
         $('#pdv_procenat').val(v.pdvProcenat > 0 ? v.pdvProcenat : '10');
         $('#rabat').val(v.rabat > 0 ? v.rabat : '0');
         calc1.calculatePDVFromPercentage();
       }
       
       showToast('Podaci kopirani!', 'success');
      updateDifferences();
     }
    
    // Funkcija za prikaz razlika između kalkulacija
    function updateDifferences() {
      if (!calc1 || !calc2) {
        return;
      }
      
      const total1 = parseFloat($('#ukupna_cena').val()) || 0;
      const total2 = parseFloat($('#ukupna_cena_2').val()) || 0;
      const diffTotal = total1 - total2;
      
      const povrat1 = parseFloat($('#povrat_pdv').val()) || 0;
      const povrat2 = parseFloat($('#povrat_pdv_2').val()) || 0;
      const diffPovrat = povrat1 - povrat2;
      
      $('#diff_ukupna_cena').val(diffTotal.toFixed(2));
      $('#diff_povrat_pdv').val(diffPovrat.toFixed(2));
    }
     
     // Toast notifikacija
     function showToast(message, type = 'info') {
       const toastClass = type === 'success' ? 'toast-success' : (type === 'error' ? 'toast-error' : 'toast-info');
       const toast = $('<div class="custom-toast ' + toastClass + '">' + message + '</div>');
       $('body').append(toast);
       
       setTimeout(function() {
         toast.addClass('show');
       }, 100);
       
       setTimeout(function() {
         toast.removeClass('show');
         setTimeout(function() {
           toast.remove();
         }, 300);
       }, 2500);
     }
  </script>

  <style>
    /* Stilovi za kalkulator */
    .calculator-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .calculator-card {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      transition: transform 0.2s ease;
    }
    
    .calculator-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    
    .result-highlight {
      background-color: #e8f5e8;
      border-left: 4px solid #28a745;
      padding: 15px;
      margin-top: 20px;
      border-radius: 5px;
    }
    
    .form-control:focus {
      border-color: #007bff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .form-control:invalid {
      border-color: #dc3545;
    }
    
    /* Toast notifikacije */
    .custom-toast {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      border-radius: 8px;
      color: white;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      opacity: 0;
      transform: translateX(400px);
      transition: all 0.3s ease;
    }
    
    .custom-toast.show {
      opacity: 1;
      transform: translateX(0);
    }
    
    .toast-success {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }
    
    .toast-error {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }
    
    .toast-info {
      background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }
    
    /* Dugmad za kopiranje */
    .copy-btn {
      background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
      border: none;
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.9rem;
    }
    
    .copy-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .copy-btn i {
      margin-right: 5px;
    }
    
    /* Dodatni stilovi za responsive dizajn */
    @media (max-width: 768px) {
      .custom-toast {
        right: 10px;
        left: 10px;
        top: 10px;
      }
    }
    
    /* Animacija za input polja */
    .form-control {
      transition: all 0.2s ease;
    }
    
    .form-control:focus {
      transform: scale(1.02);
    }
    
    /* Stilovi za readonly polja */
    .form-control[readonly] {
      background-color: #f8f9fa;
      cursor: not-allowed;
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
      
      <div class="calculator-container">
        <div class="d-flex flex-column justify-content-center align-items-center">
          <h3 class="center sekcija mb-4">
            <i class="fas fa-calculator"></i> Kalkulator cena stambenih jedinica
          </h3>

          <!-- Kalkulator forma 1 -->
          <div class="card calculator-card mb-4 w-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-calculator"></i> Kalkulacija cena 1</h5>
              <button class="copy-btn" onclick="copyCalculator(1, 2)" title="Kopiraj u Kalkulaciju 2">
                <i class="fas fa-arrow-down"></i> Kopiraj u 2
              </button>
            </div>
            <div class="card-body">
              <!-- Osnovni podaci -->
              <div class="form-group row mb-4">
                <div class="col-md-12 col-sm-12 mb-3">
                  <label for="kvadratura" class="col-form-label">
                    <i class="fas fa-ruler-combined"></i> Kvadratura (m²)
                  </label>
                  <input class="form-control" id="kvadratura" name="kvadratura" type="number" step="0.01" min="0" placeholder="Unesite kvadraturu">
                </div>
              </div>

              <!-- Cene i kalkulacije -->
              <div class="form-group row">
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="cena_po_m2" class="col-form-label">
                    <i class="fas fa-euro-sign"></i> Cena po m² (€)
                  </label>
                  <input class="form-control" id="cena_po_m2" name="cena_po_m2" type="number" step="0.01" min="0" placeholder="Unesite cenu po m²">
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="realna_cena_po_m2" class="col-form-label">
                    <i class="fas fa-chart-line"></i> Cena po m² sa PDV (€)
                  </label>
                  <input class="form-control" id="realna_cena_po_m2" name="realna_cena_po_m2" type="number" step="0.01" min="0" placeholder="Automatski se računa">
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="pdv_procenat" class="col-form-label">
                    <i class="fas fa-percentage"></i> PDV PROCENT %
                  </label>
                  <input class="form-control" id="pdv_procenat" name="pdv_procenat" type="number" step="0.01" min="0" max="100" value="10">
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="pdv" class="col-form-label">
                    <i class="fas fa-percentage"></i> PDV SUMA €
                  </label>
                  <input class="form-control" id="pdv" name="pdv" type="number" step="0.01" min="0" value="0" placeholder="Automatski se računa">
                </div>
              </div>
              
              <div class="form-group row">
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="povrat_pdv" class="col-form-label">
                    <i class="fas fa-undo"></i> Povrat PDV (€)
                  </label>
                  <input class="form-control" id="povrat_pdv" name="povrat_pdv" type="number" step="0.01" min="0" readonly>
                  <small class="form-text text-muted">Automatski se računa za prvih 40m²</small>
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="rabat" class="col-form-label">
                    <i class="fas fa-tag"></i> Rabat (€)
                  </label>
                  <input class="form-control" id="rabat" name="rabat" type="number" step="0.01" min="0" value="0" placeholder="Unesite rabat">
                </div>
                <div class="col-md-6 col-sm-12 mb-3">
                  <label for="ukupna_cena" class="col-form-label">
                    <i class="fas fa-calculator"></i> Ukupna cena (€)
                  </label>
                  <input class="form-control" id="ukupna_cena" name="ukupna_cena" type="number" step="0.01" min="0" placeholder="Automatski se računa">
                  <small class="form-text text-muted">Možete uneti direktno ili se računa automatski</small>
                </div>
              </div>
            </div>
          </div>

        <!-- Sekcija za razlike između kalkulacija -->
        <div class="card calculator-card mb-4 w-100">
          <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Razlika između kalkulacija</h5>
          </div>
          <div class="card-body">
            <div class="result-highlight mb-0">
              <div class="form-group row">
                <div class="col-md-6 col-sm-12 mb-3">
                  <label for="diff_ukupna_cena" class="col-form-label">
                    <i class="fas fa-balance-scale"></i> Razlika ukupne cene (€)
                  </label>
                  <input class="form-control" id="diff_ukupna_cena" type="text" readonly value="0.00">
                  <small class="form-text text-muted">Prikazuje vrednost Kalkulacija 1 - Kalkulacija 2</small>
                </div>
                <div class="col-md-6 col-sm-12 mb-3">
                  <label for="diff_povrat_pdv" class="col-form-label">
                    <i class="fas fa-undo-alt"></i> Razlika povrata PDV (€)
                  </label>
                  <input class="form-control" id="diff_povrat_pdv" type="text" readonly value="0.00">
                  <small class="form-text text-muted">Pozitivna vrednost znači da Kalkulacija 1 ima veći povrat</small>
                </div>
              </div>
            </div>
          </div>
        </div>

          <!-- Kalkulator forma 2 -->
          <div class="card calculator-card mb-4 w-100">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-calculator"></i> Kalkulacija cena 2</h5>
              <button class="copy-btn" onclick="copyCalculator(2, 1)" title="Kopiraj u Kalkulaciju 1">
                <i class="fas fa-arrow-up"></i> Kopiraj u 1
              </button>
            </div>
            <div class="card-body">
              <!-- Osnovni podaci -->
              <div class="form-group row mb-4">
                <div class="col-md-12 col-sm-12 mb-3">
                  <label for="kvadratura2" class="col-form-label">
                    <i class="fas fa-ruler-combined"></i> Kvadratura (m²)
                  </label>
                  <input class="form-control" id="kvadratura2" name="kvadratura2" type="number" step="0.01" min="0" placeholder="Unesite kvadraturu">
                </div>
              </div>

              <!-- Cene i kalkulacije -->
              <div class="form-group row">
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="cena_po_m2_2" class="col-form-label">
                    <i class="fas fa-euro-sign"></i> Cena po m² (€)
                  </label>
                  <input class="form-control" id="cena_po_m2_2" name="cena_po_m2_2" type="number" step="0.01" min="0" placeholder="Unesite cenu po m²">
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="realna_cena_po_m2_2" class="col-form-label">
                    <i class="fas fa-chart-line"></i> Cena po m² sa PDV (€)
                  </label>
                  <input class="form-control" id="realna_cena_po_m2_2" name="realna_cena_po_m2_2" type="number" step="0.01" min="0" placeholder="Automatski se računa">
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="pdv_procenat_2" class="col-form-label">
                    <i class="fas fa-percentage"></i> PDV PROCENT %
                  </label>
                  <input class="form-control" id="pdv_procenat_2" name="pdv_procenat_2" type="number" step="0.01" min="0" max="100" value="10">
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="pdv_2" class="col-form-label">
                    <i class="fas fa-percentage"></i> PDV SUMA €
                  </label>
                  <input class="form-control" id="pdv_2" name="pdv_2" type="number" step="0.01" min="0" value="0" placeholder="Automatski se računa">
                </div>
              </div>
              
              <div class="form-group row">
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="povrat_pdv_2" class="col-form-label">
                    <i class="fas fa-undo"></i> Povrat PDV (€)
                  </label>
                  <input class="form-control" id="povrat_pdv_2" name="povrat_pdv_2" type="number" step="0.01" min="0" readonly>
                  <small class="form-text text-muted">Automatski se računa za prvih 40m²</small>
                </div>
                <div class="col-md-3 col-sm-12 mb-3">
                  <label for="rabat_2" class="col-form-label">
                    <i class="fas fa-tag"></i> Rabat (€)
                  </label>
                  <input class="form-control" id="rabat_2" name="rabat_2" type="number" step="0.01" min="0" value="0" placeholder="Unesite rabat">
                </div>
                <div class="col-md-6 col-sm-12 mb-3">
                  <label for="ukupna_cena_2" class="col-form-label">
                    <i class="fas fa-calculator"></i> Ukupna cena (€)
                  </label>
                  <input class="form-control" id="ukupna_cena_2" name="ukupna_cena_2" type="number" step="0.01" min="0" placeholder="Automatski se računa">
                  <small class="form-text text-muted">Možete uneti direktno ili se računa automatski</small>
                </div>
              </div>
            </div>
          </div>


          <!-- Dugmad za akcije -->
          <div class="card calculator-card mt-4 w-100">
            <div class="card-body">
              <div class="form-group row">
                <div class="col-md-4 col-sm-12 mb-2">
                  <button type="button" class="btn btn-warning btn-lg w-100" id="resetCalc1Btn">
                    <i class="fas fa-redo"></i> <br>
                    Resetuj 1
                  </button>
                </div>
                <div class="col-md-4 col-sm-12 mb-2">
                  <button type="button" class="btn btn-info btn-lg w-100" id="resetCalc2Btn">
                    <i class="fas fa-redo"></i> <br>
                    Resetuj 2
                  </button>
                </div>
                <div class="col-md-4 col-sm-12 mb-2">
                  <a href="glavni.php" class="btn btn-danger btn-lg w-100">
                    <i class="fas fa-arrow-left"></i> <br>
                    Nazad
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // ========================================
    // DODATNE EVENT HANDLERS - RESET DUGMAD
    // ========================================
    
    $(document).ready(function() {
      // Reset dugme za prvu formu
      $('#resetCalc1Btn').on('click', function() {
        calc1.reset();
        showToast('Kalkulacija 1 resetovana!', 'info');
      });
      
      // Reset dugme za drugu formu
      $('#resetCalc2Btn').on('click', function() {
        calc2.reset();
        showToast('Kalkulacija 2 resetovana!', 'info');
      });
    });
  </script>

</body>

</html>
