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
    <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

    <title>GP RAZ - Pregled kupca detaljno</title>

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
                <div class="row">

                    <div class="col-12">
                        <?php
                        // Uzimanje ID kupca iz URL-a
                        $kupac_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                        
                        if ($kupac_id <= 0) {
                            header("location: pregled_kupaca.php");
                            exit;
                        }

                        // Uzimanje podataka o kupcu
                        $podatci = new CRUD($_SESSION['godina']);
                        $podatci->table = "kupci";
                        $kupac = $podatci->select(['*'], ['id' => $kupac_id]);
                        
                        if (empty($kupac)) {
                            header("location: pregled_kupaca.php");
                            exit;
                        }
                        
                        $kupac = $kupac[0];
                        
                        // Dohvatanje tipa kupca
                        $tipKupcaCrud = new CRUD($_SESSION['godina']);
                        $tipKupcaCrud->table = "tip_kupca";
                        $tipKupca = $tipKupcaCrud->select(['naziv'], ['id_tipa_kupca' => $kupac['tip_kupca_id']]);
                        $nazivTipaKupca = !empty($tipKupca) ? $tipKupca[0]['naziv'] : 'Nepoznat tip';

                        // Dohvatanje stanova koje je kupio
                        $stanCrud = new CRUD($_SESSION['godina']);
                        $stanCrud->table = "stanovi";
                        $stanovi = $stanCrud->select(['*'], ['kupac_id' => $kupac_id, 'prodat' => 1]);

                        // Dohvatanje lokala koje je kupio
                        $lokalCrud = new CRUD($_SESSION['godina']);
                        $lokalCrud->table = "lokali";
                        $lokali = $lokalCrud->select(['*'], ['kupac_id' => $kupac_id, 'prodat' => 1]);

                        // Dohvatanje garaža koje je kupio
                        $garazaCrud = new CRUD($_SESSION['godina']);
                        $garazaCrud->table = "garaze";
                        $garaze = $garazaCrud->select(['*'], ['kupac_id' => $kupac_id, 'prodat' => 1]);

                        // Dohvatanje parking mesta koje je kupio
                        $parkingCrud = new CRUD($_SESSION['godina']);
                        $parkingCrud->table = "parking_mesta";
                        $parking_mesta = $parkingCrud->select(['*'], ['kupac_id' => $kupac_id, 'prodat' => 1]);

                        // Kalkulacija ukupnih suma
                        $ukupna_suma_stanova = 0;
                        $ukupna_suma_lokala = 0;
                        $ukupna_suma_garaza = 0;
                        $ukupna_suma_parking = 0;

                        foreach ($stanovi as $stan) {
                            $ukupna_suma_stanova += $stan['ukupna_cena'];
                        }

                        foreach ($lokali as $lokal) {
                            $ukupna_suma_lokala += $lokal['kvadratura'] * $lokal['cena_po_m2'];
                        }

                        // Računaj sve garaže (koristi cena_sa_pdv umesto kvadratura * cena_kvadrata)
                        foreach ($garaze as $garaza) {
                            $ukupna_suma_garaza += $garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0;
                        }

                        // Računaj samo parking mesta koja nisu vezana za stanove koji se prodaju sa parking mestom
                        foreach ($parking_mesta as $parking) {
                            // Proveri da li je parking mesto vezano za stan koji se prodaje sa parking mestom
                            $stanCrud = new CRUD($_SESSION['godina']);
                            $stanCrud->table = "stanovi";
                            $stan = $stanCrud->select(['prodaje_sa_parking_mestom'], ['id' => $parking['stan_id']]);
                            
                            if (empty($stan) || $stan[0]['prodaje_sa_parking_mestom'] == 0) {
                                $ukupna_suma_parking += $parking['cena'];
                            }
                        }

                        $ukupna_suma_svega = $ukupna_suma_stanova + $ukupna_suma_lokala + $ukupna_suma_garaza + $ukupna_suma_parking;

                        // Dohvatanje uplata
                        $uplataCrud = new CRUD($_SESSION['godina']);
                        $uplataCrud->table = "uplata";
                        $uplata = $uplataCrud->select([], [], "SELECT * FROM uplata WHERE id_kupca = " . $kupac_id . " ORDER BY datum_uplate DESC");

                        // Dohvatanje planiranih rata
                        include_once 'funkcije/f_ucitaj_plan_otplate.php';
                        $planovi_result = ucitajSvePlanoveKupca($kupac_id);
                        $planovi = $planovi_result['success'] ? $planovi_result['planovi'] : [];
                        
                        // Mapiraj uplate na planirane rate
                        include_once 'funkcije/f_mapiraj_uplate_na_planove.php';
                        $mapiranje_result = mapirajSveUplateZaKupca($kupac_id);
                        
                        // Ponovo učitaj planirane rate sa ažuriranim podacima
                        $planovi_result = ucitajSvePlanoveKupca($kupac_id);
                        $planovi = $planovi_result['success'] ? $planovi_result['planovi'] : [];
                        
                        // Kombinuj uplate i planirane rate za prikaz
                        $sve_uplate = [];
                        
                        // Dodaj stvarne uplate
                        foreach ($uplata as $u) {
                            $u['tip'] = 'uplata';
                            $sve_uplate[] = $u;
                        }
                        
                        // Dodaj planirane rate
                        foreach ($planovi as $plan) {
                            if ($plan['status'] !== 'placeno') {
                                $plan['tip'] = 'plan';
                                $plan['datum_uplate'] = $plan['datum_rate'];
                                $plan['iznos_uplate'] = $plan['suma'];
                                $plan['srednji_kurs'] = 0; // Ne koristi se za planirane rate
                                $sve_uplate[] = $plan;
                            }
                        }
                        
                        // Sortiraj po datumu (najstarije prvo)
                        usort($sve_uplate, function($a, $b) {
                            $da = isset($a['datum_uplate']) ? strtotime($a['datum_uplate']) : false;
                            $db = isset($b['datum_uplate']) ? strtotime($b['datum_uplate']) : false;

                            if ($da === false) {
                                $da = PHP_INT_MAX;
                            }
                            if ($db === false) {
                                $db = PHP_INT_MAX;
                            }

                            if ($da === $db) {
                                return 0;
                            }

                            return ($da < $db) ? -1 : 1;
                        });

                        // Kalkulacija ukupnih uplata (iznos_uplate je u evrima)
                        $ukupno_uplaceno = 0;
                        $ukupno_uplaceno_rsd = 0;
                        $ukupno_uplaceno_stanova = 0;
                        $ukupno_uplaceno_lokala = 0;
                        $ukupno_uplaceno_garaza = 0;
                        $ukupno_uplaceno_parking = 0;
                        
                        foreach ($uplata as $u) {
                            $ukupno_uplaceno += $u['iznos_uplate'];
                            
                            $kursZaUplatu = 0;
                            if (!empty($u['srednji_kurs'])) {
                                $kursZaUplatu = (float)$u['srednji_kurs'];
                            } elseif (!empty($u['trenutna_vrednost_eura'])) {
                                $kursZaUplatu = (float)$u['trenutna_vrednost_eura'];
                            } elseif (!empty($_SESSION['euro'])) {
                                $kursZaUplatu = (float)$_SESSION['euro'];
                            }
                            
                            if ($kursZaUplatu > 0) {
                                $ukupno_uplaceno_rsd += $u['iznos_uplate'] * $kursZaUplatu;
                            }
                            
                            // Kalkulacija uplata po tipovima jedinica
                            if (isset($u['tip_jedinice']) && isset($u['id_jedinice'])) {
                                if ($u['tip_jedinice'] == 'stan') {
                                    $ukupno_uplaceno_stanova += $u['iznos_uplate'];
                                } elseif ($u['tip_jedinice'] == 'lokal') {
                                    $ukupno_uplaceno_lokala += $u['iznos_uplate'];
                                } elseif ($u['tip_jedinice'] == 'garaza') {
                                    $ukupno_uplaceno_garaza += $u['iznos_uplate'];
                                } elseif ($u['tip_jedinice'] == 'parking') {
                                    $ukupno_uplaceno_parking += $u['iznos_uplate'];
                                }
                            }
                        }

                        // Koristi istu logiku kao gore - ukupna suma svih jedinica kupca
                        $ukupno_zaduzenje = $ukupna_suma_svega;

                        $dug = $ukupna_suma_svega - $ukupno_uplaceno;

                        // Obrada forme za kreiranje uplate
                        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['datum_uplate'])) {
                            try {
                                $uplataCrud = new CRUD($_SESSION['godina']);
                                $uplataCrud->table = "uplata";
                                
                                 // Konvertuj iznos iz dinara u evre isključivo po srednjem kursu
                                 $iznosRSD = isset($_POST['iznos_uplate']) ? floatval($_POST['iznos_uplate']) : 0;
                                 $srednjiKurs = isset($_POST['srednji_kurs']) ? floatval($_POST['srednji_kurs']) : 0;

                                 if ($srednjiKurs <= 0) {
                                     $_SESSION['poruka'] = "Greška: Morate imati važeći srednji kurs na dan uplate.";
                                     echo "<script>window.location.href = 'kupci_detaljno.php?id=" . $kupac_id . "';</script>";
                                     exit;
                                 }

                                 if ($iznosRSD <= 0) {
                                     $_SESSION['poruka'] = "Greška: Iznos uplate mora biti veći od nule.";
                                     echo "<script>window.location.href = 'kupci_detaljno.php?id=" . $kupac_id . "';</script>";
                                     exit;
                                 }

                                 $iznosEUR = round($iznosRSD / $srednjiKurs, 2);
                                 $vrednostRSD = round($iznosRSD, 2);
                                 
                                 // Proveri da li je uplata veća od preostalog zaduženja za izabranu jedinicu
                                 $tip_jedinice = $_POST['tip_jedinice'];
                                 $id_jedinice = $_POST['id_jedinice'];
                                 
                                 // Kalkulacija cene izabrane jedinice
                                 $cena_jedinice = 0;
                                 if ($tip_jedinice == 'stan') {
                                     $stanCrud = new CRUD($_SESSION['godina']);
                                     $stanCrud->table = "stanovi";
                                     $stan = $stanCrud->select(['ukupna_cena'], ['id' => $id_jedinice]);
                                     $cena_jedinice = !empty($stan) ? $stan[0]['ukupna_cena'] : 0;
                                 } elseif ($tip_jedinice == 'lokal') {
                                     $lokalCrud = new CRUD($_SESSION['godina']);
                                     $lokalCrud->table = "lokali";
                                     $lokal = $lokalCrud->select(['ukupna_cena'], ['id' => $id_jedinice]);
                                     $cena_jedinice = !empty($lokal) ? $lokal[0]['ukupna_cena'] : 0;
                                } elseif ($tip_jedinice == 'garaza') {
                                    $garazaCrud = new CRUD($_SESSION['godina']);
                                    $garazaCrud->table = "garaze";
                                    $garaza = $garazaCrud->select(['cena_sa_pdv', 'cena'], ['id' => $id_jedinice]);
                                    if (!empty($garaza)) {
                                        $cena_jedinice = isset($garaza[0]['cena_sa_pdv']) && $garaza[0]['cena_sa_pdv'] !== null
                                            ? (float)$garaza[0]['cena_sa_pdv']
                                            : (float)($garaza[0]['cena'] ?? 0);
                                    } else {
                                        $cena_jedinice = 0;
                                    }
                                 } elseif ($tip_jedinice == 'parking') {
                                     $parkingCrud = new CRUD($_SESSION['godina']);
                                     $parkingCrud->table = "parking_mesta";
                                     $parking = $parkingCrud->select(['cena'], ['id' => $id_jedinice]);
                                     $cena_jedinice = !empty($parking) ? $parking[0]['cena'] : 0;
                                 }
                                 
                                 // Kalkulacija uplata za izabranu jedinicu
                                 $uplata_jedinice = 0;
                                 foreach ($uplata as $u) {
                                     if (isset($u['tip_jedinice']) && isset($u['id_jedinice']) && 
                                         $u['tip_jedinice'] == $tip_jedinice && $u['id_jedinice'] == $id_jedinice) {
                                         $uplata_jedinice += $u['iznos_uplate'];
                                     }
                                 }
                                 
                                 $imaDefinisanuCenu = $cena_jedinice > 0;
                                 $preostaloZaduzenje = $cena_jedinice - $uplata_jedinice;
                                 if ($imaDefinisanuCenu && $preostaloZaduzenje < 0) {
                                     $preostaloZaduzenje = 0;
                                 }
                                 
                                 if ($imaDefinisanuCenu && ($iznosEUR - $preostaloZaduzenje) > 0.01) {
                                     $_SESSION['poruka'] = "Greška: Uplata ne može biti veća od preostalog zaduženja (" . number_format($preostaloZaduzenje, 2, ',', '.') . " €)";
                                     echo "<script>window.location.href = 'kupci_detaljno.php?id=" . $kupac_id . "';</script>";
                                     exit;
                                 }
                                 
                                 $data = [
                                     'id_kupca' => $kupac_id,
                                     'datum_uplate' => $_POST['datum_uplate'],
                                     'trenutna_vrednost_eura' => $_POST['trenutna_vrednost_eura'],
                                     'srednji_kurs' => $srednjiKurs,
                                     'iznos_uplate' => $iznosEUR, // Čuvaj izračunati iznos u evrima
                                     'vrednost_u_dinarima' => $vrednostRSD,
                                     'tip_jedinice' => $_POST['tip_jedinice'],
            'id_jedinice' => $_POST['id_jedinice'],
            'kes' => isset($_POST['uplata_kes']) ? 1 : 0
                                 ];
                                
                                $result = $uplataCrud->insert($data);
                                if ($result) {
                                    // Mapiraj uplate na planirane rate nakon kreiranja
                                    include_once 'funkcije/f_mapiraj_uplate_na_planove.php';
                                    mapirajSveUplateZaKupca($kupac_id);
                                    
                                    $_SESSION['poruka'] = "Uplata je uspešno kreirana!";
                                    echo "<script>window.location.href = 'kupci_detaljno.php?id=" . $kupac_id . "';</script>";
                                    exit;
                                } else {
                                    $_SESSION['poruka'] = "Greška pri kreiranju uplate!";
                                }
                            } catch (Exception $e) {
                                $_SESSION['poruka'] = "Greška: " . $e->getMessage();
                            }
                        }
                        ?>

                        <div class="container-fluid mt-3 d-flex justify-content-center">
                            <div style="max-width: 1000px; width: 100%;">
                            <?php if (isset($_SESSION['poruka'])): ?>
                                <div class="alert alert-<?= strpos($_SESSION['poruka'], 'Greška') !== false ? 'danger' : 'success' ?>" role="alert">
                                    <?= $_SESSION['poruka'] ?>
                                </div>
                                <?php unset($_SESSION['poruka']); ?>
                            <?php endif; ?>

                            <!-- Ukupne sume -->
                            <div class="alert alert-info" role="alert">
                                <h3 class="mb-3 bg-primary text-white p-3 rounded"><strong>Pregled kupca: <?= htmlspecialchars($kupac['ime'] . ' ' . $kupac['prezime']) ?></strong></h3>
                                <div class="row">
                                    <div class="col-md-3">
                                        <h5><strong>Tip kupca:</strong></h5>
                                        <h4 class="text-info"><?= htmlspecialchars($nazivTipaKupca) ?></h4>
                                    </div>
                                    <div class="col-md-3">
                                        <h5><strong>Ukupna vrednost kupljenih jedinica:</strong></h5>
                                        <h4 class="text-primary"><?= number_format($ukupna_suma_svega, 0, ',', '.') ?> €</h4>
                                    </div>
                                    <div class="col-md-3">
                                        <h5><strong>Ukupno uplaćeno:</strong></h5>
                                        <h4 class="text-success"><?= number_format($ukupno_uplaceno, 0, ',', '.') ?> €</h4>
                                    </div>
                                    <div class="col-md-3">
                                        <h5><strong>Dug:</strong></h5>
                                        <h3 class="<?= $dug > 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= number_format($dug, 0, ',', '.') ?> €
                                        </h3>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <h5><strong>Ukupno uplaćeno u dinarima:</strong></h5>
                                        <h4 class="text-success"><?= number_format($ukupno_uplaceno_rsd, 2, ',', '.') ?> RSD</h4>
                                    </div>
                                </div>
                            </div>

                            <!-- Accordion za sve tabele -->
                            <div class="accordion" id="kupacAccordion">
                                <!-- Stanovi -->
                                <?php if (!empty($stanovi)): ?>
                                <div class="card mb-3 w-100">
                                    <div class="card-header bg-primary text-white p-0" id="headingStanovi">
                                        <button class="btn btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#collapseStanovi" aria-expanded="false" aria-controls="collapseStanovi" style="background: transparent; border: none;">
                                            <h5 class="mb-0 py-2 px-3">
                                                <i class="fas fa-home"></i> Stanovi
                                            </h5>
                                        </button>
                                    </div>
                                    <div id="collapseStanovi" class="collapse" aria-labelledby="headingStanovi">
                                        <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Oznaka</th>
                                                    <th class="text-right">Površina (m²)</th>
                                                    <th class="text-right">Cena / m² (€)</th>
                                                    <th>Plan</th>
                                                    <th class="text-right">Zaduženje / Uplaćeno</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stanovi as $stan): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($stan['naziv']) ?></td>
                                                        <td class="text-right"><?= number_format($stan['kvadratura'], 2, ',', '.') ?></td>
                                                        <td class="text-right"><?= number_format($stan['cena_po_m2'], 0, ',', '.') ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary kreiraj-plan" 
                                                                    data-jedinica-id="<?= $stan['id'] ?>" 
                                                                    data-tip-jedinice="stan"
                                                                    data-kupac-id="<?= $kupac_id ?>">
                                                                <i class="fas fa-calendar"></i> Plan
                                                            </button>
                                                        </td>
                                                        <td class="text-right"><?= number_format($stan['ukupna_cena'], 0, ',', '.') ?> / <?= number_format($ukupno_uplaceno_stanova, 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th class="text-right"><?= number_format(array_sum(array_column($stanovi, 'kvadratura')), 2, ',', '.') ?> m²</th>
                                                    <th class="text-right">-</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= number_format($ukupna_suma_stanova, 0, ',', '.') ?> €</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Lokali -->
                                <?php if (!empty($lokali)): ?>
                                <div class="card mb-3 w-100">
                                    <div class="card-header bg-success text-white p-0" id="headingLokali">
                                        <button class="btn btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#collapseLokali" aria-expanded="false" aria-controls="collapseLokali" style="background: transparent; border: none;">
                                            <h5 class="mb-0 py-2 px-3">
                                                <i class="fas fa-store"></i> Lokali
                                            </h5>
                                        </button>
                                    </div>
                                    <div id="collapseLokali" class="collapse" aria-labelledby="headingLokali">
                                        <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Oznaka</th>
                                                    <th class="text-right">Površina (m²)</th>
                                                    <th class="text-right">Cena / m² (€)</th>
                                                    <th>Plan</th>
                                                    <th class="text-right">Zaduženje / Uplaćeno</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lokali as $lokal): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($lokal['naziv']) ?></td>
                                                        <td class="text-right"><?= number_format($lokal['kvadratura'], 2, ',', '.') ?></td>
                                                        <td class="text-right"><?= number_format($lokal['cena_po_m2'], 0, ',', '.') ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary kreiraj-plan" 
                                                                    data-jedinica-id="<?= $lokal['id'] ?>" 
                                                                    data-tip-jedinice="lokal"
                                                                    data-kupac-id="<?= $kupac_id ?>">
                                                                <i class="fas fa-calendar"></i> Plan
                                                            </button>
                                                        </td>
                                                        <td class="text-right"><?= number_format($lokal['kvadratura'] * $lokal['cena_po_m2'], 0, ',', '.') ?> / <?= number_format($ukupno_uplaceno_lokala, 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th class="text-right"><?= number_format(array_sum(array_column($lokali, 'kvadratura')), 2, ',', '.') ?> m²</th>
                                                    <th class="text-right">-</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= number_format($ukupna_suma_lokala, 0, ',', '.') ?> €</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Garaže -->
                                <?php if (!empty($garaze)): ?>
                                <div class="card mb-3 w-100">
                                    <div class="card-header bg-warning text-white p-0" id="headingGaraze">
                                        <button class="btn btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#collapseGaraze" aria-expanded="false" aria-controls="collapseGaraze" style="background: transparent; border: none;">
                                            <h5 class="mb-0 py-2 px-3">
                                                <i class="fas fa-warehouse"></i> Garaže
                                            </h5>
                                        </button>
                                    </div>
                                    <div id="collapseGaraze" class="collapse" aria-labelledby="headingGaraze">
                                        <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Oznaka</th>
                                                    <th class="text-right">Ukupna cena (€)</th>
                                                    <th>Plan</th>
                                                    <th class="text-right">Zaduženje / Uplaćeno</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($garaze as $garaza): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($garaza['naziv']) ?></td>
                                                        <td class="text-right"><?= number_format($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0, 0, ',', '.') ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary kreiraj-plan" 
                                                                    data-jedinica-id="<?= $garaza['id'] ?>" 
                                                                    data-tip-jedinice="garaza"
                                                                    data-kupac-id="<?= $kupac_id ?>">
                                                                <i class="fas fa-calendar"></i> Plan
                                                            </button>
                                                        </td>
                                                        <td class="text-right"><?= number_format($garaza['cena_sa_pdv'] ?? $garaza['cena'] ?? 0, 0, ',', '.') ?> / <?= number_format($ukupno_uplaceno_garaza, 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th class="text-right"><?= number_format($ukupna_suma_garaza, 0, ',', '.') ?> €</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= number_format($ukupna_suma_garaza, 0, ',', '.') ?> €</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Parking mesta -->
                                <?php if (!empty($parking_mesta)): ?>
                                <div class="card mb-3 w-100">
                                    <div class="card-header bg-info text-white p-0" id="headingParking">
                                        <button class="btn btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#collapseParking" aria-expanded="false" aria-controls="collapseParking" style="background: transparent; border: none;">
                                            <h5 class="mb-0 py-2 px-3">
                                                <i class="fas fa-car"></i> Parking mesta
                                            </h5>
                                        </button>
                                    </div>
                                    <div id="collapseParking" class="collapse" aria-labelledby="headingParking">
                                        <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Naziv</th>
                                                    <th>Napomena</th>
                                                    <th>Plan</th>
                                                    <th class="text-right">Zaduženje / Uplaćeno</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($parking_mesta as $parking): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($parking['naziv']) ?></td>
                                                        <td><?= htmlspecialchars($parking['napomena'] ?? '') ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary kreiraj-plan" 
                                                                    data-jedinica-id="<?= $parking['id'] ?>" 
                                                                    data-tip-jedinice="parking"
                                                                    data-kupac-id="<?= $kupac_id ?>">
                                                                <i class="fas fa-calendar"></i> Plan
                                                            </button>
                                                        </td>
                                                        <td class="text-right"><?= number_format($parking['cena'], 0, ',', '.') ?> / <?= number_format($ukupno_uplaceno_parking, 0, ',', '.') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th>-</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= number_format($ukupna_suma_parking, 0, ',', '.') ?> €</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Spisak uplata -->
                            <div class="card mb-3 w-100">
                                <div class="card-header"><i class="fas fa-money-bill-wave"></i> Spisak uplata</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Datum uplate</th>
                                                    <th>Jedinica</th>
                                                    <th class="text-right">Srednji kurs (RSD)</th>
                                                    <th class="text-right">Iznos uplate (€)</th>
                                                <th class="text-center">Keš</th>
                                                    <th>Status</th>
                                                    <th class="text-right">Preostalo (€)</th>
                                                    <th>Akcije</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($sve_uplate)): ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted">Nema uplata</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($sve_uplate as $index => $u): ?>
                                                        <?php
                                                        // Dohvatanje naziva jedinice - fallback za stare podatke
                                                        $naziv_jedinice = 'Opšta uplata';
                                                        $tip_jedinice = '';
                                                        $id_jedinice = 0;
                                                        
                                                        // Proverava da li postoje nova polja
                                                        if (array_key_exists('tip_jedinice', $u) && array_key_exists('id_jedinice', $u)) {
                                                            $tip_jedinice = $u['tip_jedinice'] ?? '';
                                                            $id_jedinice = $u['id_jedinice'] ?? 0;
                                                            
                                                            if ($tip_jedinice == 'stan' && $id_jedinice > 0) {
                                                                $stanCrud = new CRUD($_SESSION['godina']);
                                                                $stanCrud->table = "stanovi";
                                                                $stan = $stanCrud->select(['naziv'], ['id' => $id_jedinice]);
                                                                $naziv_jedinice = !empty($stan) ? $stan[0]['naziv'] : 'Nepoznato';
                                                            } elseif ($tip_jedinice == 'lokal' && $id_jedinice > 0) {
                                                                $lokalCrud = new CRUD($_SESSION['godina']);
                                                                $lokalCrud->table = "lokali";
                                                                $lokal = $lokalCrud->select(['naziv'], ['id' => $id_jedinice]);
                                                                $naziv_jedinice = !empty($lokal) ? $lokal[0]['naziv'] : 'Nepoznato';
                                                            } elseif ($tip_jedinice == 'garaza' && $id_jedinice > 0) {
                                                                $garazaCrud = new CRUD($_SESSION['godina']);
                                                                $garazaCrud->table = "garaze";
                                                                $garaza = $garazaCrud->select(['naziv'], ['id' => $id_jedinice]);
                                                                $naziv_jedinice = !empty($garaza) ? $garaza[0]['naziv'] : 'Nepoznato';
                                                            } elseif ($tip_jedinice == 'parking' && $id_jedinice > 0) {
                                                                $parkingCrud = new CRUD($_SESSION['godina']);
                                                                $parkingCrud->table = "parking_mesta";
                                                                $parking = $parkingCrud->select(['naziv'], ['id' => $id_jedinice]);
                                                                $naziv_jedinice = !empty($parking) ? $parking[0]['naziv'] : 'Nepoznato';
                                                            }
                                                        }
                                                        ?>
                                                        <?php
                                                        // Odredi CSS klasu na osnovu statusa
                                                        $row_class = '';
                                                        $status_badge = '';
                                                        $preostalo_text = '';
                                                        
                                                        if (isset($u['tip']) && $u['tip'] === 'plan') {
                                                            $status = $u['status'] ?? 'neplaceno';
                                                            $uplaceno = floatval($u['uplaceno'] ?? 0);
                                                            $suma = floatval($u['suma']);
                                                            $preostalo = $suma - $uplaceno;
                                                            
                                                            switch ($status) {
                                                                case 'placeno':
                                                                    $row_class = 'table-success';
                                                                    $status_badge = '<span class="badge badge-success"><i class="fas fa-check"></i> Plaćeno</span>';
                                                                    $preostalo_text = '0,00 €';
                                                                    break;
                                                                case 'delimicno_placeno':
                                                                    $row_class = 'table-warning';
                                                                    $status_badge = '<span class="badge badge-warning"><i class="fas fa-clock"></i> Delimično</span>';
                                                                    $preostalo_text = number_format($preostalo, 0, ',', '.') . ' €';
                                                                    break;
                                                                default:
                                                                    $row_class = 'table-danger';
                                                                    $status_badge = '<span class="badge badge-danger"><i class="fas fa-times"></i> Neplaćeno</span>';
                                                                    $preostalo_text = number_format($preostalo, 0, ',', '.') . ' €';
                                                                    break;
                                                            }
                                                        } else {
                                                            $status_badge = '<span class="badge badge-info"><i class="fas fa-money-bill"></i> Plaćeno</span>';
                                                            $preostalo_text = '-';
                                                        }
                                                        ?>
                                                        <tr class="<?= $row_class ?>">
                                                            <td><?= $index + 1 ?></td>
                                                            <td><?= formatirajDatum($u['datum_uplate']) ?></td>
                                                            <td>
                                                                <?php if (isset($u['tip']) && $u['tip'] === 'plan'): ?>
                                                                    <span class="badge badge-info">
                                                                        <i class="fas fa-calendar"></i> Planirana rata
                                                                    </span>
                                                                    <?php
                                                                    // Dohvati naziv jedinice za planiranu ratu
                                                                    $plan_naziv = '';
                                                                    switch ($u['tip_jedinice']) {
                                                                        case 'stan':
                                                                            $stanCrud = new CRUD($_SESSION['godina']);
                                                                            $stanCrud->table = "stanovi";
                                                                            $stan = $stanCrud->select(['naziv'], ['id' => $u['jedinica_id']]);
                                                                            $plan_naziv = !empty($stan) ? $stan[0]['naziv'] : 'Nepoznato';
                                                                            break;
                                                                        case 'lokal':
                                                                            $lokalCrud = new CRUD($_SESSION['godina']);
                                                                            $lokalCrud->table = "lokali";
                                                                            $lokal = $lokalCrud->select(['naziv'], ['id' => $u['jedinica_id']]);
                                                                            $plan_naziv = !empty($lokal) ? $lokal[0]['naziv'] : 'Nepoznato';
                                                                            break;
                                                                        case 'garaza':
                                                                            $garazaCrud = new CRUD($_SESSION['godina']);
                                                                            $garazaCrud->table = "garaze";
                                                                            $garaza = $garazaCrud->select(['naziv'], ['id' => $u['jedinica_id']]);
                                                                            $plan_naziv = !empty($garaza) ? $garaza[0]['naziv'] : 'Nepoznato';
                                                                            break;
                                                                        case 'parking':
                                                                            $parkingCrud = new CRUD($_SESSION['godina']);
                                                                            $parkingCrud->table = "parking_mesta";
                                                                            $parking = $parkingCrud->select(['naziv'], ['id' => $u['jedinica_id']]);
                                                                            $plan_naziv = !empty($parking) ? $parking[0]['naziv'] : 'Nepoznato';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <span class="badge badge-<?= $u['tip_jedinice'] == 'stan' ? 'primary' : ($u['tip_jedinice'] == 'lokal' ? 'success' : ($u['tip_jedinice'] == 'garaza' ? 'warning' : 'info')) ?>">
                                                                        <?= ucfirst($u['tip_jedinice']) ?>
                                                                    </span>
                                                                    <?= htmlspecialchars($plan_naziv) ?>
                                                                <?php elseif (!empty($tip_jedinice)): ?>
                                                                    <span class="badge badge-<?= $tip_jedinice == 'stan' ? 'primary' : ($tip_jedinice == 'lokal' ? 'success' : ($tip_jedinice == 'garaza' ? 'warning' : 'info')) ?>">
                                                                        <?= ucfirst($tip_jedinice) ?>
                                                                    </span>
                                                                    <?= htmlspecialchars($naziv_jedinice) ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Opšta uplata</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-right"><?= isset($u['srednji_kurs']) && $u['srednji_kurs'] ? number_format($u['srednji_kurs'], 4, ',', '.') : '-' ?></td>
                                                            <td class="text-right"><?= number_format($u['iznos_uplate'], 0, ',', '.') ?> €</td>
                                                            <td class="text-center">
                                                                <?php if (isset($u['tip']) && $u['tip'] === 'plan'): ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php else: ?>
                                                                    <?php if (!empty($u['kes'])): ?>
                                                                        <span class="badge badge-success">Da</span>
                                                                    <?php else: ?>
                                                                        <span class="badge badge-secondary">Ne</span>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= $status_badge ?></td>
                                                            <td class="text-right"><?= $preostalo_text ?></td>
                                                            <td>
                                                                <?php if (isset($u['tip']) && $u['tip'] === 'plan'): ?>
                                                                    <button class="btn btn-sm btn-success potvrdi-uplatu" data-plan-id="<?= $u['id'] ?>">
                                                                        <i class="fas fa-check"></i> Potvrdi
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>-</th>
                                                    <th>UKUPNO UPLAĆENO</th>
                                                    <th>-</th>
                                                    <th class="text-right">-</th>
                                                    <th class="text-right">
                                                        <?php 
                                                        // Izračunaj ukupno zaduženje - samo jedinstvene jedinice
                                                        $ukupno_zaduzenje = 0;
                                                        $obradjene_jedinice = [];
                                                        
                                                        foreach ($uplata as $u) {
                                                            if (!empty($u['tip_jedinice']) && !empty($u['id_jedinice'])) {
                                                                $tip = $u['tip_jedinice'];
                                                                $id_jedinice = $u['id_jedinice'];
                                                                
                                                                // Proveri da li je jedinica već obrađena
                                                                $kljuc = $tip . '_' . $id_jedinice;
                                                                if (!in_array($kljuc, $obradjene_jedinice)) {
                                                                    $obradjene_jedinice[] = $kljuc;
                                                                    
                                                                    // Dohvati cenu jedinice
                                                                    $cena = 0;
                                                                    if ($tip == 'stan') {
                                                                        $stanCrud = new CRUD($_SESSION['godina']);
                                                                        $stanCrud->table = "stanovi";
                                                                        $stan = $stanCrud->select(['ukupna_cena'], ['id' => $id_jedinice]);
                                                                        $cena = !empty($stan) ? $stan[0]['ukupna_cena'] : 0;
                                                                    } elseif ($tip == 'lokal') {
                                                                        $lokalCrud = new CRUD($_SESSION['godina']);
                                                                        $lokalCrud->table = "lokali";
                                                                        $lokal = $lokalCrud->select(['kvadratura', 'cena_po_m2'], ['id' => $id_jedinice]);
                                                                        if (!empty($lokal)) {
                                                                            $cena = $lokal[0]['kvadratura'] * $lokal[0]['cena_po_m2'];
                                                                        }
                                                                    } elseif ($tip == 'garaza') {
                                                                        $garazaCrud = new CRUD($_SESSION['godina']);
                                                                        $garazaCrud->table = "garaze";
                                                                        $garaza = $garazaCrud->select(['cena_sa_pdv', 'cena'], ['id' => $id_jedinice]);
                                                                        if (!empty($garaza)) {
                                                                            $cena = $garaza[0]['cena_sa_pdv'] ?? $garaza[0]['cena'] ?? 0;
                                                                        }
                                                                    } elseif ($tip == 'parking') {
                                                                        $parkingCrud = new CRUD($_SESSION['godina']);
                                                                        $parkingCrud->table = "parking_mesta";
                                                                        $parking = $parkingCrud->select(['cena'], ['id' => $id_jedinice]);
                                                                        $cena = !empty($parking) ? $parking[0]['cena'] : 0;
                                                                    }
                                                                    
                                                                    $ukupno_zaduzenje += $cena;
                                                                }
                                                            }
                                                        }
                                                        
                                                        $razlika = $ukupno_zaduzenje - $ukupno_uplaceno;
                                                        ?>
                                                        <div>
                                                            <div><strong>Uplaćeno:</strong> <?= number_format($ukupno_uplaceno, 0, ',', '.') ?> €</div>
                                                            <div><strong>Zaduženje:</strong> <?= number_format($ukupno_zaduzenje, 0, ',', '.') ?> €</div>
                                                            <div class="<?= $razlika > 0 ? 'text-danger' : ($razlika < 0 ? 'text-success' : 'text-muted') ?>">
                                                                <strong><?= $razlika > 0 ? 'Duguje:' : ($razlika < 0 ? 'Preplata:' : 'Uravnoteženo:') ?> <?= number_format(abs($razlika), 0, ',', '.') ?> €</strong>
                                                            </div>
                                                        </div>
                                                    </th>
                                                <th>-</th>
                                                    <th>-</th>
                                                    <th>-</th>
                                                    <th>-</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Forma za kreiranje uplate -->
                            <div class="card mb-3 w-100">
                                <div class="card-header"><i class="fas fa-plus"></i> Kreiraj uplatu</div>
                                <div class="card-body">
                                     <form method="POST" action="kupci_detaljno.php?id=<?= $kupac_id ?>" id="uplata-form">
                                         <div class="row">
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="datum_uplate">Datum uplate:</label>
                                                     <input type="date" class="form-control" id="datum_uplate" name="datum_uplate" value="<?= date('Y-m-d') ?>" required>
                                                 </div>
                                             </div>
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="tip_jedinice">Tip jedinice:</label>
                                                     <select class="form-control" id="tip_jedinice" name="tip_jedinice" required>
                                                         <option value="">Izaberite tip</option>
                                                         <?php if (!empty($stanovi)): ?>
                                                             <option value="stan">Stan</option>
                                                         <?php endif; ?>
                                                         <?php if (!empty($lokali)): ?>
                                                             <option value="lokal">Lokal</option>
                                                         <?php endif; ?>
                                                         <?php if (!empty($garaze)): ?>
                                                             <option value="garaza">Garaža</option>
                                                         <?php endif; ?>
                                                         <?php if (!empty($parking_mesta)): ?>
                                                             <option value="parking">Parking</option>
                                                         <?php endif; ?>
                                                     </select>
                                                 </div>
                                             </div>
                                         </div>
                                         <div class="row">
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="id_jedinice">Jedinica:</label>
                                                     <select class="form-control" id="id_jedinice" name="id_jedinice" required>
                                                         <option value="">Izaberite jedinicu</option>
                                                     </select>
                                                 </div>
                                             </div>
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="trenutna_vrednost_eura">Trenutna vrednost €:</label>
                                                     <input type="number" step="0.01" class="form-control" id="trenutna_vrednost_eura" name="trenutna_vrednost_eura" value="<?= $_SESSION['euro'] ?? 0 ?>" required>
                                                 </div>
                                             </div>
                                         </div>
                                         <div class="row">
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="srednji_kurs">Srednji kurs (RSD):</label>
                                                     <input type="number" step="0.0001" class="form-control" id="srednji_kurs" name="srednji_kurs" readonly>
                                                 </div>
                                             </div>
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="iznos_uplate">Iznos uplate (RSD):</label>
                                                     <input type="number" step="0.01" class="form-control" id="iznos_uplate" name="iznos_uplate" placeholder="Unesite iznos u dinarima" required>
                                                     <small class="form-text text-muted">
                                                     </small>
                                                 </div>
                                             </div>
                                         </div>
                                         <div class="row">
                                             <div class="col-md-6">
                                                 <div class="form-group">
                                                     <label for="vrednost_u_evrima">Vrednost u evrima (€):</label>
                                                     <input type="number" step="0.01" class="form-control" id="vrednost_u_evrima" name="vrednost_u_evrima" placeholder="Unesite vrednost u evrima">
                                                     <small class="form-text text-muted">Možete uneti direktno u evrima ili dinamički se računa iz dinara</small>
                                                 </div>
                                             </div>
                                             <div class="col-md-6">
                                        <div class="form-group mt-4 pt-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="uplata_kes" name="uplata_kes" value="1">
                                                <label class="form-check-label" for="uplata_kes">
                                                    Uplata izvršena u kešu
                                                </label>
                                            </div>
                                        </div>
                                             </div>
                                         </div>
                                         <button type="submit" class="btn btn-primary">Kreiraj uplatu</button>
                                     </form>
                                </div>
                            </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN - Full version -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous">
    </script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous">
    </script>
     <!-- Bootstrap JS -->
     <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous">
     </script>

     <script>
    function planFormatSerbianDateInput(el) {
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

    function planIsoToSerbian(isoDate) {
        if (!isoDate || typeof isoDate !== 'string') {
            return '';
        }

        const parts = isoDate.split('-');
        if (parts.length !== 3) {
            return '';
        }

        const [year, month, day] = parts;
        if (!year || !month || !day) {
            return '';
        }

        return `${day.padStart(2, '0')}.${month.padStart(2, '0')}.${year}`;
    }

    function planSerbianToIso(value) {
        if (!value) {
            return null;
        }

        const digits = value.replace(/\D/g, '');
        if (digits.length !== 8) {
            return null;
        }

        const day = digits.slice(0, 2);
        const month = digits.slice(2, 4);
        const year = digits.slice(4);
        const iso = `${year}-${month}-${day}`;
        const date = new Date(`${iso}T00:00:00Z`);

        if (Number.isNaN(date.getTime())) {
            return null;
        }

        const validDay = String(date.getUTCDate()).padStart(2, '0');
        const validMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
        const validYear = String(date.getUTCFullYear());

        if (validDay !== day || validMonth !== month || validYear !== year) {
            return null;
        }

        return iso;
    }

    // Automatsko povlačenje kursa evra sa NBS
    $(document).ready(function() {
        $(document).on('input', '.datum-rate', function() {
            planFormatSerbianDateInput(this);
        });

         $('#datum_uplate').on('change', function() {
             const datum = $(this).val();
             if (datum) {
                 // Prikaži loading indikator samo za srednji kurs
                 $('#srednji_kurs').val('Učitavam...');
                 
                 $.ajax({
                     url: 'get_nbs_exchange_rate.php',
                     method: 'POST',
                     data: { datum: datum },
                     dataType: 'json',
                     success: function(response) {
                        if (response.success) {
                            // Popuni samo polje srednji_kurs
                            $('#srednji_kurs').val(response.kurs);
                            
                            // Prikaži uspešnu poruku
                            showAlert('Srednji kurs je uspešno učitán: ' + response.kurs + ' RSD za ' + response.datum, 'success');
                        } else {
                            $('#srednji_kurs').val('');
                            showAlert('Greška pri učitavanju kursa: ' + response.error, 'danger');
                        }
                     },
                     error: function() {
                         $('#srednji_kurs').val('');
                         showAlert('Greška pri komunikaciji sa NBS serverom', 'danger');
                     }
                 });
             }
         });
         
         // Dinamičko računanje iznosa u dinarima kada se menja vrednost u evrima
         $('#vrednost_u_evrima').on('input change', function() {
             const vrednostEUR = parseFloat($(this).val()) || 0;
             const srednjiKurs = parseFloat($('#srednji_kurs').val()) || 0;
             const tipJedinice = $('#tip_jedinice').val();
             const idJedinice = $('#id_jedinice').val();
             
             // Ažuriraj iznos u dinarima kada se menja vrednost u evrima
             if (vrednostEUR > 0 && srednjiKurs > 0) {
                 const iznosRSD = vrednostEUR * srednjiKurs;
                 $('#iznos_uplate').val(iznosRSD.toFixed(2));
                 
                 // Proveri zaduženje ako su svi podaci dostupni
                 if (tipJedinice && idJedinice) {
                     checkZaduzenje(vrednostEUR, tipJedinice, idJedinice);
                 }
             } else if (vrednostEUR === 0) {
                 $('#iznos_uplate').val('');
             }
         });
         
         // Automatsko izračunavanje iznosa u evrima i kontrola zaduženja
         $('#iznos_uplate, #srednji_kurs, #tip_jedinice, #id_jedinice').on('input change', function() {
             const iznosRSD = parseFloat($('#iznos_uplate').val()) || 0;
             const srednjiKurs = parseFloat($('#srednji_kurs').val()) || 0;
             const tipJedinice = $('#tip_jedinice').val();
             const idJedinice = $('#id_jedinice').val();
             
             // Uvek računaj i prikaži vrednost u evrima kada se menja iznos u dinarima
             if (iznosRSD > 0 && srednjiKurs > 0) {
                 const iznosEUR = iznosRSD / srednjiKurs;
                 $('#vrednost_u_evrima').val(iznosEUR.toFixed(2));
             } else if (iznosRSD === 0) {
                 $('#vrednost_u_evrima').val('');
             }
             
             if (iznosRSD > 0 && srednjiKurs > 0 && tipJedinice && idJedinice) {
                 const iznosEUR = iznosRSD / srednjiKurs;
                 checkZaduzenje(iznosEUR, tipJedinice, idJedinice);
             } else if (iznosRSD === 0) {
                 // Resetuj prikaz kada je polje prazno
                 $('#iznos_uplate').removeClass('is-invalid');
             }
         });
     });
     
    // Funkcija za proveru zaduženja
    function checkZaduzenje(iznosEUR, tipJedinice, idJedinice) {
        // Proveri da li su parametri definisani
        if (!tipJedinice || !idJedinice) {
            console.log('Parametri nisu definisani za proveru zaduženja:', tipJedinice, idJedinice);
            return;
        }
        
        $.ajax({
             url: 'get_object_payment_info.php',
             method: 'POST',
             data: { 
                 tip_jedinice: tipJedinice,
                 id_jedinice: idJedinice,
                 kupac_id: <?= $kupac_id ?>
             },
             dataType: 'json',
             success: function(response) {
                if (response.success) {
                     const cenaObjekta = parseFloat(response.cena_objekta) || 0;
                     const uplacenoZaObjekat = parseFloat(response.uplaceno_za_objekat) || 0;
                     const imaDefinisanuCenu = cenaObjekta > 0;
                     let preostaloZaduzenje = cenaObjekta - uplacenoZaObjekat;
                     
                     if (imaDefinisanuCenu && preostaloZaduzenje < 0) {
                         preostaloZaduzenje = 0;
                     }
                     
                     if (imaDefinisanuCenu && iznosEUR > preostaloZaduzenje) {
                         showAlert('Uplata ne može biti veća od preostalog zaduženja (' + preostaloZaduzenje.toFixed(2) + ' €)', 'danger');
                         $('#iznos_uplate').addClass('is-invalid');
                         $('#vrednost_u_evrima').addClass('is-invalid');
                     } else {
                         $('#iznos_uplate').removeClass('is-invalid');
                         $('#vrednost_u_evrima').removeClass('is-invalid');
                     }
                     
                     console.log('Cena objekta:', cenaObjekta);
                     console.log('Uplaćeno za objekat:', uplacenoZaObjekat);
                     console.log('Preostalo zaduženje:', preostaloZaduzenje);
                     console.log('Iznos uplate u EUR:', iznosEUR.toFixed(2));
                 }
            },
            error: function() {
                showAlert('Greška pri učitavanju informacija o objektu', 'warning');
            }
         });
     }
     
     // Funkcija za prikazivanje alert poruka
     function showAlert(message, type) {
         const alertHtml = `
             <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                 ${message}
                 <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>
         `;
         
         // Ukloni postojeće alert-ove
         $('.alert').remove();
         
         // Dodaj novi alert na vrh forme
         $('#uplata-form').prepend(alertHtml);
         
         // Automatski ukloni alert nakon 5 sekundi
         setTimeout(function() {
             $('.alert').fadeOut();
         }, 5000);
     }
     
     // Dinamičko popunjavanje dropdown-a za jedinice
     const tipJedinice = document.getElementById('tip_jedinice');
     const idJedinice = document.getElementById('id_jedinice');
     
     // Podaci o jedinicama
     const jedinice = {
         stan: <?= json_encode($stanovi) ?>,
         lokal: <?= json_encode($lokali) ?>,
         garaza: <?= json_encode($garaze) ?>,
         parking: <?= json_encode($parking_mesta) ?>
     };
     
     tipJedinice.addEventListener('change', function() {
         const tip = this.value;
         idJedinice.innerHTML = '<option value="">Izaberite jedinicu</option>';
         
         if (tip && jedinice[tip]) {
             jedinice[tip].forEach(function(jedinica) {
                 const option = document.createElement('option');
                 option.value = jedinica.id;
                 option.textContent = jedinica.naziv;
                 idJedinice.appendChild(option);
             });
         }
         
     });
     
     // Custom accordion toggle funkcionalnost
     $(document).ready(function() {
         // Ukloni default Bootstrap behavior
         $('button[data-toggle="collapse"]').off('click');
         
         // Dodaj custom click handler
         $('button[data-toggle="collapse"]').on('click', function(e) {
             e.preventDefault();
             e.stopPropagation();
             
             const targetId = $(this).attr('data-target');
             const $target = $(targetId);
             
             // Toggle collapse
             $target.collapse('toggle');
         });
     });
     
     // Plan otplate funkcionalnost
     let ukupnaCenaObjekta = 0;
     let brojRata = 0;
     
     // Klik na dugme "Plan"
     $(document).on('click', '.kreiraj-plan', function() {
         const jedinicaId = $(this).data('jedinica-id');
         const tipJedinice = $(this).data('tip-jedinice');
         const kupacId = $(this).data('kupac-id');
         
         // Postavi vrednosti u modal
         $('#plan_kupac_id').val(kupacId);
         $('#plan_jedinica_id').val(jedinicaId);
         $('#plan_tip_jedinice').val(tipJedinice);
         
         // Učitaj postojeći plan ili pripremi za kreiranje novog
         ucitajPlanOtplate(jedinicaId, tipJedinice);
     });
     
    // Učitaj plan otplate
    function ucitajPlanOtplate(jedinicaId, tipJedinice) {
        // Proveri da li su parametri definisani
        if (!jedinicaId || !tipJedinice) {
            console.log('Parametri nisu definisani:', jedinicaId, tipJedinice);
            return;
        }
        
        console.log('Učitavam plan za:', jedinicaId, tipJedinice);
        $.ajax({
             url: 'funkcije/f_ucitaj_plan_otplate.php',
             method: 'POST',
             data: {
                 jedinica_id: jedinicaId,
                 tip_jedinice: tipJedinice
             },
             dataType: 'json',
             success: function(response) {
                 console.log('AJAX response:', response);
                 if (response.success) {
                     if (response.plan.length > 0) {
                         // Prikaži postojeći plan
                        prikaziPlan(response.plan, response.auto_generated === true, response.datum_prodaje || null);
                     } else {
                        prikaziPlan([], true, response.datum_prodaje || null);
                     }
                 } else {
                     alert('Greška: ' + response.message);
                 }
             },
             error: function(xhr, status, error) {
                 console.log('AJAX error:', xhr.responseText, status, error);
                alert('Greška pri učitavanju plana: ' + xhr.responseText);
             }
         });
     }
     
     // Prikaži plan u tabeli
    function prikaziPlan(plan, autoGenerated = false, datumProdaje = null) {
         const tbody = $('#tabela-rata-tbody');
         tbody.empty();
        
        const sortedPlan = [...plan].sort((a, b) => {
            const da = a && a.datum_rate ? String(a.datum_rate) : '';
            const db = b && b.datum_rate ? String(b.datum_rate) : '';
            return da.localeCompare(db);
        });

        ukupnaCenaObjekta = 0;
        brojRata = sortedPlan.length;
        
        $('#plan_auto_generated').val(autoGenerated ? '1' : '0');
        $('#plan_datum_prodaje').val(datumProdaje || '');
        $('#planAutoInfo').toggleClass('d-none', !autoGenerated);

        const fallbackIso = datumProdaje || new Date().toISOString().slice(0, 10);

        sortedPlan.forEach(function(rata, index) {
             ukupnaCenaObjekta += parseFloat(rata.suma);
            const datumIso = rata.datum_rate || fallbackIso;
            const datumSr = planIsoToSerbian(datumIso) || planIsoToSerbian(fallbackIso);
             
             const row = `
                 <tr>
                     <td>
                        <input type="text" class="form-control form-control-sm datum-rate" 
                               value="${datumSr}" data-index="${index}" placeholder="dd.mm.gggg">
                     </td>
                     <td>
                         <input type="number" step="0.01" class="form-control form-control-sm procenat" 
                                value="${rata.procenat}" data-index="${index}">
                     </td>
                     <td>
                         <input type="number" step="0.01" class="form-control form-control-sm suma" 
                                value="${rata.suma}" data-index="${index}">
                     </td>
                     <td>
                         <button type="button" class="btn btn-sm btn-danger ukloni-ratu" data-index="${index}">
                             <i class="fas fa-trash"></i>
                         </button>
                     </td>
                 </tr>
             `;
             tbody.append(row);
         });
         
         $('#ukupna_cena_objekta').text(ukupnaCenaObjekta.toFixed(2));
         azurirajUkupanProcenat();
         
         // Otvori modal
         $('#planOtplateModal').modal('show');
     }
     
     // Dodaj novu ratu
     $(document).on('click', '#dodaj-ratu', function() {
         console.log('Dodaj ratu kliknut');
         const index = brojRata++;
        const defaultIso = $('#plan_datum_prodaje').val() || new Date().toISOString().slice(0, 10);
        const defaultSr = planIsoToSerbian(defaultIso);
         const row = `
             <tr>
                 <td>
                    <input type="text" class="form-control form-control-sm datum-rate" 
                           value="${defaultSr}" data-index="${index}" placeholder="dd.mm.gggg">
                 </td>
                 <td>
                     <input type="number" step="0.01" class="form-control form-control-sm procenat" 
                            value="0" data-index="${index}">
                 </td>
                 <td>
                     <input type="number" step="0.01" class="form-control form-control-sm suma" 
                            value="0" data-index="${index}">
                 </td>
                 <td>
                     <button type="button" class="btn btn-sm btn-danger ukloni-ratu" data-index="${index}">
                         <i class="fas fa-trash"></i>
                     </button>
                 </td>
             </tr>
         `;
         $('#tabela-rata-tbody').append(row);
     });
     
     // Ukloni ratu
     $(document).on('click', '.ukloni-ratu', function() {
         $(this).closest('tr').remove();
         azurirajUkupanProcenat();
     });
     
     // Bidirekciono računanje procenat ↔ suma
     $(document).on('input', '.procenat', function() {
         const procenat = parseFloat($(this).val()) || 0;
         const suma = (ukupnaCenaObjekta * procenat) / 100;
         $(this).closest('tr').find('.suma').val(suma.toFixed(2));
         azurirajUkupanProcenat();
     });
     
     $(document).on('input', '.suma', function() {
         const suma = parseFloat($(this).val()) || 0;
         const procenat = ukupnaCenaObjekta > 0 ? (suma * 100) / ukupnaCenaObjekta : 0;
         $(this).closest('tr').find('.procenat').val(procenat.toFixed(2));
         azurirajUkupanProcenat();
     });
     
     // Ažuriraj ukupan procenat
     function azurirajUkupanProcenat() {
         let ukupanProcenat = 0;
         $('.procenat').each(function() {
             ukupanProcenat += parseFloat($(this).val()) || 0;
         });
         $('#ukupan_procenat').text(ukupanProcenat.toFixed(2) + '%');
         
         // Promeni boju ako nije 100%
         if (Math.abs(ukupanProcenat - 100) < 0.01) {
             $('#ukupan_procenat').removeClass('text-danger').addClass('text-success');
         } else {
             $('#ukupan_procenat').removeClass('text-success').addClass('text-danger');
         }
     }
     
     // Sačuvaj plan
     $(document).on('click', '#sacuvaj-plan', function() {
         console.log('Sačuvaj plan kliknut');
         const rate = [];
        let valid = true;
        let errorMessage = '';
        let focusElement = null;
        $('.datum-rate').each(function() {
            const datumSr = ($(this).val() || '').trim();
            const procenat = parseFloat($(this).closest('tr').find('.procenat').val()) || 0;
            const suma = parseFloat($(this).closest('tr').find('.suma').val()) || 0;

            if (datumSr === '' || procenat <= 0 || suma <= 0) {
                valid = false;
                errorMessage = 'Sva polja za datume, procente i sume moraju biti popunjena i veća od nule.';
                focusElement = focusElement || this;
                return false; // prekid iteracije
            }

            const datumIso = planSerbianToIso(datumSr);
            if (!datumIso) {
                valid = false;
                errorMessage = 'Datum rate mora biti u formatu dd.mm.gggg.';
                focusElement = focusElement || this;
                return false;
            }

            rate.push({
                datum_rate: datumIso,
                procenat: procenat,
                suma: suma
            });
         });
         
        if (!valid) {
            if (errorMessage) {
                alert(errorMessage);
            }
            if (focusElement) {
                $(focusElement).focus();
            }
            return;
        }

         if (rate.length === 0) {
             alert('Morate dodati najmanje jednu ratu');
             return;
         }
         
         const ukupanProcenat = rate.reduce((sum, rata) => sum + rata.procenat, 0);
         if (Math.abs(ukupanProcenat - 100) > 0.01) {
             alert('Ukupan procenat mora biti 100%');
             return;
         }
         
         $.ajax({
             url: 'funkcije/f_izmeni_plan_otplate.php',
             method: 'POST',
             data: {
                 kupac_id: $('#plan_kupac_id').val(),
                 jedinica_id: $('#plan_jedinica_id').val(),
                 tip_jedinice: $('#plan_tip_jedinice').val(),
                 rate: JSON.stringify(rate)
             },
             dataType: 'json',
             success: function(response) {
                 if (response.success) {
                     alert('Plan otplate je uspešno sačuvan');
                     $('#planOtplateModal').modal('hide');
                     location.reload();
                 } else {
                     alert('Greška: ' + response.message);
                 }
             },
             error: function() {
                 alert('Greška pri čuvanju plana');
             }
         });
     });
     
     // Potvrda uplate funkcionalnost
     $(document).on('click', '.potvrdi-uplatu', function() {
         const planId = $(this).data('plan-id');
         
         $.ajax({
             url: 'funkcije/f_potvrdi_uplatu_iz_plana.php',
             method: 'POST',
             data: {
                 action: 'get_info',
                 plan_id: planId
             },
             dataType: 'json',
             success: function(response) {
                 if (response.success) {
                     $('#plan_id').val(planId);
                     $('#naziv_jedinice').val(response.naziv_jedinice);
                     $('#planirana_suma').val(response.plan.suma);
                     
                     // Postavi datum iz planirane rate
                     $('#datum_uplate_modal').val(response.plan.datum_rate);
                     
                     // Učitaj srednji kurs za taj datum
                     ucitajSrednjiKurs();
                     
                     $('#potvrdaUplateModal').modal('show');
                 } else {
                     alert('Greška: ' + response.message);
                 }
             },
             error: function() {
                 alert('Greška pri učitavanju informacija');
             }
         });
     });
     
     // Učitaj srednji kurs
     function ucitajSrednjiKurs() {
         const datum = $('#datum_uplate_modal').val();
         if (!datum) {
             console.log('Datum nije unet');
             return;
         }
         
         console.log('Učitavam kurs za datum:', datum);
         
         $.ajax({
             url: 'get_nbs_exchange_rate.php',
             method: 'POST',
             data: { datum: datum },
             dataType: 'json',
             success: function(response) {
                 console.log('Kurs response:', response);
                  if (response.success) {
                      $('#srednji_kurs_modal').val(response.kurs);
                      // Ažuriraj iznos u RSD
                      const sumaEUR = parseFloat($('#planirana_suma').val());
                      const kurs = parseFloat(response.kurs);
                      $('#iznos_uplate_rsd').val((sumaEUR * kurs).toFixed(2));
                  } else {
                      console.log('Greška pri učitavanju kursa:', response.error);
                      $('#srednji_kurs_modal').val('');
                  }
             },
             error: function(xhr, status, error) {
                 console.log('AJAX greška:', xhr.responseText);
                 $('#srednji_kurs_modal').val('');
             }
         });
     }
     
     // Event listener za promenu datuma u modalu
     $(document).on('change', '#datum_uplate_modal', function() {
         ucitajSrednjiKurs();
     });
     
     // Potvrdi uplatu
     $(document).on('click', '#potvrdi-uplatu-btn', function() {
         const planId = $('#plan_id').val();
         const iznosRSD = parseFloat($('#iznos_uplate_rsd').val());
         const srednjiKurs = parseFloat($('#srednji_kurs_modal').val());
         
         if (!iznosRSD || !srednjiKurs) {
             alert('Molimo unesite ispravne podatke');
             return;
         }
         
         $.ajax({
             url: 'funkcije/f_potvrdi_uplatu_iz_plana.php',
             method: 'POST',
             data: {
                 action: 'potvrdi',
                 plan_id: planId,
                 iznos_uplate: iznosRSD,
                 srednji_kurs: srednjiKurs
             },
             dataType: 'json',
             success: function(response) {
                 if (response.success) {
                     alert('Uplata je uspešno potvrđena');
                     $('#potvrdaUplateModal').modal('hide');
                     location.reload();
                 } else {
                     alert('Greška: ' + response.message);
                 }
             },
             error: function() {
                 alert('Greška pri potvrđivanju uplate');
             }
         });
     });
     
     </script>

     <!-- Modal za kreiranje/izmenu plana otplate -->
     <div class="modal fade" id="planOtplateModal" tabindex="-1" role="dialog" aria-labelledby="planOtplateModalLabel">
         <div class="modal-dialog modal-lg" role="document">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="planOtplateModalLabel">Plan otplate</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <div class="modal-body">
                     <form id="plan-otplate-form">
                         <input type="hidden" id="plan_kupac_id" name="kupac_id">
                         <input type="hidden" id="plan_jedinica_id" name="jedinica_id">
                         <input type="hidden" id="plan_tip_jedinice" name="tip_jedinice">
                            <input type="hidden" id="plan_auto_generated" value="0">
                            <input type="hidden" id="plan_datum_prodaje" value="">

                            <div id="planAutoInfo" class="alert alert-info py-2 px-3 d-none">
                                Plan otplate je predložen automatski na osnovu faza objekta i datuma predugovora. Proverite datume pre čuvanja.
                            </div>
                         
                         <div class="row mb-3">
                             <div class="col-md-6">
                                 <strong>Ukupna cena objekta:</strong>
                                 <span id="ukupna_cena_objekta" class="text-primary"></span> €
                             </div>
                             <div class="col-md-6">
                                 <strong>Ukupan procenat:</strong>
                                 <span id="ukupan_procenat" class="text-info">0%</span>
                             </div>
                         </div>
                         
                         <div class="table-responsive">
                             <table class="table table-sm" id="tabela-rata">
                                 <thead class="thead-light">
                                     <tr>
                                         <th>Datum rate</th>
                                         <th>Procenat (%)</th>
                                         <th>Suma (€)</th>
                                         <th>Akcije</th>
                                     </tr>
                                 </thead>
                                 <tbody id="tabela-rata-tbody">
                                     <!-- Dinamički se popunjava -->
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="text-center">
                             <button type="button" class="btn btn-success" id="dodaj-ratu">
                                 <i class="fas fa-plus"></i> Dodaj ratu
                             </button>
                         </div>
                     </form>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Otkaži</button>
                     <button type="button" class="btn btn-primary" id="sacuvaj-plan">Sačuvaj plan</button>
                 </div>
             </div>
         </div>
     </div>

     <!-- Modal za potvrdu uplate -->
     <div class="modal fade" id="potvrdaUplateModal" tabindex="-1" role="dialog" aria-labelledby="potvrdaUplateModalLabel">
         <div class="modal-dialog" role="document">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="potvrdaUplateModalLabel">Potvrda uplate</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <div class="modal-body">
                     <form id="potvrda-uplate-form">
                         <input type="hidden" id="plan_id" name="plan_id">
                         
                         <div class="form-group">
                             <label>Jedinica:</label>
                             <input type="text" class="form-control" id="naziv_jedinice" readonly>
                         </div>
                         
                         <div class="form-group">
                             <label>Planirana suma rate:</label>
                             <input type="text" class="form-control" id="planirana_suma" readonly>
                         </div>
                         
                         <div class="form-group">
                             <label for="datum_uplate_modal">Datum uplate:</label>
                             <input type="date" class="form-control" id="datum_uplate_modal" value="<?= date('Y-m-d') ?>" required>
                         </div>
                         
                         <div class="form-group">
                             <label for="iznos_uplate_rsd">Iznos uplate (RSD):</label>
                             <input type="number" step="0.01" class="form-control" id="iznos_uplate_rsd" name="iznos_uplate" required>
                         </div>
                         
                         <div class="form-group">
                             <label>Trenutna vrednost €:</label>
                             <input type="number" step="0.01" class="form-control" id="trenutna_vrednost_eura" value="<?= $_SESSION['euro'] ?? 0 ?>" readonly>
                         </div>
                         
                         <div class="form-group">
                             <label>Srednji kurs (RSD):</label>
                             <input type="number" step="0.0001" class="form-control" id="srednji_kurs_modal" readonly>
                         </div>
                     </form>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Otkaži</button>
                     <button type="button" class="btn btn-success" id="potvrdi-uplatu-btn">Potvrdi uplatu</button>
                 </div>
             </div>
         </div>
     </div>

 </body>

 </html>
