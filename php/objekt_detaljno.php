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

    <title>GP RAZ - Pregled objekta detaljno</title>

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
                        // Uzimanje ID objekta iz URL-a
                        $objekat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                        
                        if ($objekat_id <= 0) {
                            header("location: glavni.php");
                            exit;
                        }

                        // Uzimanje podataka o objektu
                        $podatci = new CRUD($_SESSION['godina']);
                        $podatci->table = "objekti";
                        $objekat = $podatci->select(['*'], ['id' => $objekat_id]);
                        
                        if (empty($objekat)) {
                            header("location: glavni.php");
                            exit;
                        }
                        
                        $objekat = $objekat[0];

                        // Uzimanje stanova sa podacima o kupcima
                        $podatci->table = "stanovi";
                        $stanovi = $podatci->select([], [], "SELECT s.*, k.ime, k.prezime, s.datum_prodaje, s.datum_predugovora, s.kupac_id
                                                          FROM stanovi s 
                                                          LEFT JOIN kupci k ON s.kupac_id = k.id 
                                                          WHERE s.objekat_id = " . $objekat_id . " 
                                                          ORDER BY CAST(SUBSTRING(s.naziv, 2) AS UNSIGNED) ASC, s.naziv ASC");

                        // Uzimanje lokala
$podatci->table = "lokali";
$lokali = $podatci->select([], [], "SELECT l.*, k.ime AS kupac_ime, k.prezime AS kupac_prezime FROM lokali l LEFT JOIN kupci k ON l.kupac_id = k.id WHERE l.objekat_id = " . $objekat_id . " ORDER BY CAST(SUBSTRING(l.naziv, 2) AS UNSIGNED) ASC, l.naziv ASC");

                        // Uzimanje garaža
                        $podatci->table = "garaze";
$garaze = $podatci->select([], [], "SELECT g.*, k.ime AS kupac_ime, k.prezime AS kupac_prezime FROM garaze g LEFT JOIN kupci k ON g.kupac_id = k.id WHERE g.objekat_id = " . $objekat_id . " ORDER BY CAST(SUBSTRING(g.naziv, 2) AS UNSIGNED) ASC, g.naziv ASC");

                        // Uzimanje parking mesta
                        $podatci->table = "parking_mesta";
$parking_mesta = $podatci->select([], [], "SELECT pm.*, k.ime AS kupac_ime, k.prezime AS kupac_prezime FROM parking_mesta pm LEFT JOIN kupci k ON pm.kupac_id = k.id WHERE pm.objekat_id = " . $objekat_id . " ORDER BY CAST(SUBSTRING(pm.naziv, 2) AS UNSIGNED) ASC, pm.naziv ASC");

                        // Brojanje prodatih jedinica na osnovu kolone prodat
                        $podatci->table = "stanovi";
                        $prodato_stanova = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM stanovi WHERE objekat_id = " . $objekat_id . " AND prodat = 1");
                        
                        $podatci->table = "lokali";
                        $prodato_lokala = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM lokali WHERE objekat_id = " . $objekat_id . " AND prodat = 1");
                        
                        $podatci->table = "garaze";
                        $prodato_garaza = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM garaze WHERE objekat_id = " . $objekat_id . " AND prodat = 1");
                        
                        $podatci->table = "parking_mesta";
                        $prodato_parking = $podatci->select(['*'], [], "SELECT COUNT(*) as count FROM parking_mesta WHERE objekat_id = " . $objekat_id . " AND prodat = 1");

                        // Dohvatanje ID-ova prodatih jedinica za kalkulacije
                        $prodati_stanovi_ids = [];
                        $podatci->table = "stanovi";
                        foreach ($podatci->select(['id'], ['objekat_id' => $objekat_id, 'prodat' => 1]) as $stan) {
                            $prodati_stanovi_ids[] = $stan['id'];
                        }
                        
                        $prodati_lokali_ids = [];
                        $podatci->table = "lokali";
                        foreach ($podatci->select(['id'], ['objekat_id' => $objekat_id, 'prodat' => 1]) as $lokal) {
                            $prodati_lokali_ids[] = $lokal['id'];
                        }
                        
                        $prodati_garaze_ids = [];
                        $podatci->table = "garaze";
                        foreach ($podatci->select(['id'], ['objekat_id' => $objekat_id, 'prodat' => 1]) as $garaza) {
                            $prodati_garaze_ids[] = $garaza['id'];
                        }
                        
                        $prodati_parking_ids = [];
                        $podatci->table = "parking_mesta";
                        foreach ($podatci->select(['id'], ['objekat_id' => $objekat_id, 'prodat' => 1]) as $parking) {
                            $prodati_parking_ids[] = $parking['id'];
                        }

                        // Dohvatanje uplata za sve prodane jedinice ovog objekta
                        $uplataCrud = new CRUD($_SESSION['godina']);
                        $uplataCrud->table = "uplata";
                        
                        // Dohvatanje svih kupaca koji su kupili jedinice u ovom objektu
                        $kupci_ids = [];
                        foreach ($prodati_stanovi_ids as $stan_id) {
                            $stan = $podatci->select(['kupac_id'], ['id' => $stan_id]);
                            if (!empty($stan) && !in_array($stan[0]['kupac_id'], $kupci_ids)) {
                                $kupci_ids[] = $stan[0]['kupac_id'];
                            }
                        }
                        foreach ($prodati_lokali_ids as $lokal_id) {
                            $lokal = $podatci->select(['kupac_id'], ['id' => $lokal_id]);
                            if (!empty($lokal) && !in_array($lokal[0]['kupac_id'], $kupci_ids)) {
                                $kupci_ids[] = $lokal[0]['kupac_id'];
                            }
                        }
                        foreach ($prodati_garaze_ids as $garaza_id) {
                            $garaza = $podatci->select(['kupac_id'], ['id' => $garaza_id]);
                            if (!empty($garaza) && !in_array($garaza[0]['kupac_id'], $kupci_ids)) {
                                $kupci_ids[] = $garaza[0]['kupac_id'];
                            }
                        }
                        foreach ($prodati_parking_ids as $parking_id) {
                            $parking = $podatci->select(['kupac_id'], ['id' => $parking_id]);
                            if (!empty($parking) && !in_array($parking[0]['kupac_id'], $kupci_ids)) {
                                $kupci_ids[] = $parking[0]['kupac_id'];
                            }
                        }
                        
                        // Kalkulacija uplata po tipovima jedinica
                        $ukupno_uplaceno_stanova = 0;
                        $ukupno_uplaceno_lokala = 0;
                        $ukupno_uplaceno_garaza = 0;
                        $ukupno_uplaceno_parking = 0;
                        
                        if (!empty($kupci_ids) && count($kupci_ids) > 0) {
                            $kupci_ids_str = implode(',', array_filter($kupci_ids, 'is_numeric'));
                            if (!empty($kupci_ids_str)) {
                                $uplata = $uplataCrud->select([], [], "SELECT * FROM uplata WHERE id_kupca IN (" . $kupci_ids_str . ")");
                                foreach ($uplata as $u) {
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
                            }
                        }
                        ?>

                        <div class="container-fluid mt-3 d-flex justify-content-center">
                            <div style="max-width: 1000px; width: 100%;">
                            <h4 class="mb-3">Pregled objekta <?= htmlspecialchars($objekat['naziv']) ?></h4>

                            <div class="alert alert-info" role="alert">
                                <strong>Prodatih stanova:</strong> <?= $prodato_stanova[0]['count'] ?> / <?= count($stanovi) ?>
                                <?php if ($objekat['broj_lokala'] > 0): ?>
                                <br><strong>Prodatih lokala:</strong> <?= $prodato_lokala[0]['count'] ?> / <?= count($lokali) ?>
                                <?php endif; ?>
                                <?php if ($objekat['broj_garaza'] > 0): ?>
                                <br><strong>Prodatih garaža:</strong> <?= $prodato_garaza[0]['count'] ?> / <?= count($garaze) ?>
                                <?php endif; ?>
                                <?php if ($objekat['broj_parkinga'] > 0): ?>
                                <br><strong>Prodatih parking mesta:</strong> <?= $prodato_parking[0]['count'] ?> / <?= count($parking_mesta) ?>
                                <?php endif; ?>
                            </div>

                            <div class="card mb-3 w-100">
                                <div class="card-header"><i class="fas fa-home"></i> Stanovi</div>
                                <div class="card-body p-0">
                                    <?php
                                    $totalStanKv = 0;
                                    $totalStanValue = 0;
                                    $prodataStanValue = 0;
                                    foreach ($stanovi as $stanCalc) {
                                        $kv = (float)($stanCalc['kvadratura'] ?? 0);
                                        $cenaSaPdvCalc = isset($stanCalc['realna_cena_po_m2']) && (float)$stanCalc['realna_cena_po_m2'] > 0
                                            ? (float)$stanCalc['realna_cena_po_m2']
                                            : (float)($stanCalc['cena_po_m2'] ?? 0);
                                        $totalStanKv += $kv;
                                        $totalStanValue += $kv * $cenaSaPdvCalc;
                                        if (in_array($stanCalc['id'], $prodati_stanovi_ids, true)) {
                                            $prodataStanValue += $kv * $cenaSaPdvCalc;
                                        }
                                    }
                                    $prosecnaStanCena = $totalStanKv > 0 ? $totalStanValue / $totalStanKv : 0;
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Oznaka</th>
                                                    <th class="text-right">Površina (m²)</th>
                                                    <th class="text-right">Cena / m² (€) sa PDV</th>
                                                    <th class="text-right">Ukupno (€)</th>
                                                    <th>Kupac</th>
                                                    <th>Datum prodaje</th>
                                                    <th>Datum predugovora</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($stanovi as $s) :
                                                    $cenaSaPdv = isset($s['realna_cena_po_m2']) && (float)$s['realna_cena_po_m2'] > 0
                                                        ? (float)$s['realna_cena_po_m2']
                                                        : (float)$s['cena_po_m2'];
                                                    $ukupno = (float)$s['kvadratura'] * $cenaSaPdv;
                                                    $prodat = ((int)$s['prodat']) === 1;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($s['naziv']) ?></td>
                                                        <td class="text-right"><?= number_format($s['kvadratura'], 2, ',', '.') ?></td>
                                                        <td class="text-right"><?= number_format($cenaSaPdv, 0, ',', '.') ?></td>
                                                        <td class="text-right"><?= number_format($ukupno, 0, ',', '.') ?></td>
                                                        <td>
                                                            <?php if ($prodat && !empty($s['ime'])) : ?>
                                                                <a href="kupci_detaljno.php?id=<?= $s['kupac_id'] ?>" class="badge badge-primary" style="text-decoration: none; color: white;">
                                                                    <?= htmlspecialchars($s['ime'] . ' ' . $s['prezime']) ?>
                                                                </a>
                                                            <?php else : ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($prodat && !empty($s['datum_prodaje'])) : ?>
                                                                <?= formatirajDatum($s['datum_prodaje']) ?>
                                                            <?php else : ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($prodat && !empty($s['datum_predugovora'])) : ?>
                                                                <?= formatirajDatum($s['datum_predugovora']) ?>
                                                            <?php else : ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($prodat) : ?>
                                                                <?php if ((int)($s['kompenzacija'] ?? 0) === 1) : ?>
                                                                    <span class="badge badge-warning">Kompenzacija</span>
                                                                <?php elseif ((int)($s['lokacija'] ?? 0) === 1) : ?>
                                                                    <span class="badge badge-danger">Lokacija</span>
                                                                <?php else : ?>
                                                                    <span class="badge badge-success">Prodato</span>
                                                                <?php endif; ?>
                                                            <?php else : ?>
                                                                <span class="badge badge-secondary">Nije prodato</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th class="text-right"><?= number_format($totalStanKv, 2, ',', '.') ?> m²</th>
                                                    <th class="text-right"><?= number_format($prosecnaStanCena, 0, ',', '.') ?> €/m² sa PDV</th>
                                                    <th class="text-right"><?= number_format($totalStanValue, 0, ',', '.') ?> € / <?= number_format($prodataStanValue, 0, ',', '.') ?> € / <?= number_format($ukupno_uplaceno_stanova, 0, ',', '.') ?> €</th>
                                                    <th>-</th>
                                                    <th>-</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= count($stanovi) ?>/<?= $prodato_stanova[0]['count'] ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <?php if ($objekat['broj_lokala'] > 0): ?>
                            <div class="card mb-3 w-100">
                                <div class="card-header"><i class="fas fa-store"></i> Lokali</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Oznaka</th>
                                                    <th class="text-right">Površina (m²)</th>
                                                    <th class="text-right">Cena / m² (€) sa PDV</th>
                                                    <th class="text-right">Ukupno (€)</th>
                                                    <th>Kupac</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($lokali as $l) :
                                                    $ukupno = $l['kvadratura'] * $l['cena_po_m2'];
                                                    $prodat = ((int)$l['prodat']) === 1;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($l['naziv']) ?></td>
                                                        <td class="text-right"><?= number_format($l['kvadratura'], 2, ',', '.') ?></td>
                                                        <td class="text-right"><?= number_format($l['cena_po_m2'], 0, ',', '.') ?></td>
                                                        <td class="text-right"><?= number_format($ukupno, 0, ',', '.') ?></td>
                                                        <td>
                                                            <?php
                                                            $kupacNazivLokal = trim(($l['kupac_ime'] ?? '') . ' ' . ($l['kupac_prezime'] ?? ''));
                                                            if ($kupacNazivLokal === '' && !empty($l['kupac_id'])) {
                                                                $kupacNazivLokal = 'Kupac #' . $l['kupac_id'];
                                                            }
                                                            ?>
                                                            <?php if ($prodat && !empty($l['kupac_id']) && $kupacNazivLokal !== '') : ?>
                                                                <a href="kupci_detaljno.php?id=<?= $l['kupac_id'] ?>" class="badge badge-primary" style="text-decoration: none; color: white;">
                                                                    <?= htmlspecialchars($kupacNazivLokal) ?>
                                                                </a>
                                                            <?php else : ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($prodat) : ?>
                                                                <?php if ((int)($l['kompenzacija'] ?? 0) === 1) : ?>
                                                                    <span class="badge badge-warning">Kompenzacija</span>
                                                                <?php elseif ((int)($l['lokacija'] ?? 0) === 1) : ?>
                                                                    <span class="badge badge-danger">Lokacija</span>
                                                                <?php else : ?>
                                                                    <span class="badge badge-success">Prodato</span>
                                                                <?php endif; ?>
                                                            <?php else : ?>
                                                                <span class="badge badge-secondary">Nije prodato</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th class="text-right"><?= number_format(array_sum(array_column($lokali, 'kvadratura')), 2, ',', '.') ?> m²</th>
                                                    <th class="text-right"><?= count($lokali) > 0 ? number_format(array_sum(array_map(function($l) { return $l['kvadratura'] * $l['cena_po_m2']; }, $lokali)) / array_sum(array_column($lokali, 'kvadratura')), 0, ',', '.') : '0' ?> €/m² sa PDV</th>
                                                    <th class="text-right"><?= number_format(array_sum(array_map(function($l) { return $l['kvadratura'] * $l['cena_po_m2']; }, $lokali)), 0, ',', '.') ?> € / <?= number_format(array_sum(array_map(function($l) { return $l['kvadratura'] * $l['cena_po_m2']; }, array_filter($lokali, function($l) use ($prodati_lokali_ids) { return in_array($l['id'], $prodati_lokali_ids); }))), 0, ',', '.') ?> € / <?= number_format($ukupno_uplaceno_lokala, 0, ',', '.') ?> €</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= count($lokali) ?>/<?= $prodato_lokala[0]['count'] ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($objekat['broj_garaza'] > 0): ?>
                            <div class="card mb-3 w-100">
                                <div class="card-header"><i class="fas fa-warehouse"></i> Garaže</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Oznaka</th>
                                                    <th class="text-right">Ukupno (€)</th>
                                                    <th>Kupac</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($garaze as $g) :
                                                    $ukupno = $g['cena_sa_pdv'] ?? $g['cena'];
                                                    $prodat = ((int)$g['prodat']) === 1;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($g['naziv']) ?></td>
                                                        <td class="text-right"><?= number_format($ukupno, 0, ',', '.') ?></td>
                                                        <td>
                                                            <?php
                                                            $kupacNazivGaraza = trim(($g['kupac_ime'] ?? '') . ' ' . ($g['kupac_prezime'] ?? ''));
                                                            if ($kupacNazivGaraza === '' && !empty($g['kupac_id'])) {
                                                                $kupacNazivGaraza = 'Kupac #' . $g['kupac_id'];
                                                            }
                                                            ?>
                                                            <?php if ($prodat && !empty($g['kupac_id']) && $kupacNazivGaraza !== '') : ?>
                                                                <a href="kupci_detaljno.php?id=<?= $g['kupac_id'] ?>" class="badge badge-primary" style="text-decoration: none; color: white;">
                                                                    <?= htmlspecialchars($kupacNazivGaraza) ?>
                                                                </a>
                                                            <?php else : ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($prodat) : ?>
                                                                <?php if (($g['tip_prodaje'] ?? null) === 'kompenzacija') : ?>
                                                                    <span class="badge badge-warning">Kompenzacija</span>
                                                                <?php elseif (($g['tip_prodaje'] ?? null) === 'lokacija') : ?>
                                                                    <span class="badge badge-danger">Lokacija</span>
                                                                <?php else : ?>
                                                                    <span class="badge badge-success">Prodato</span>
                                                                <?php endif; ?>
                                                            <?php else : ?>
                                                                <span class="badge badge-secondary">Nije prodato</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th class="text-right"><?= number_format(array_sum(array_map(function($g) { return $g['cena_sa_pdv'] ?? $g['cena']; }, $garaze)), 0, ',', '.') ?> € / <?= number_format(array_sum(array_map(function($g) { return $g['cena_sa_pdv'] ?? $g['cena']; }, array_filter($garaze, function($g) use ($prodati_garaze_ids) { return in_array($g['id'], $prodati_garaze_ids); }))), 0, ',', '.') ?> € / <?= number_format($ukupno_uplaceno_garaza, 0, ',', '.') ?> €</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= count($garaze) ?>/<?= $prodato_garaza[0]['count'] ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($objekat['broj_parkinga'] > 0): ?>
                            <div class="card mb-3 w-100">
                                <div class="card-header"><i class="fas fa-car"></i> Parking mesta</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Naziv</th>
                                                    <th class="text-right">Cena (€)</th>
                                                    <th>Kupac</th>
                                                    <th>Napomena</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($parking_mesta as $p) :
                                                    $prodat = ((int)$p['prodat']) === 1;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($p['naziv']) ?></td>
                                                        <td class="text-right"><?= number_format($p['cena'], 0, ',', '.') ?></td>
                                                        <td>
                                                            <?php
                                                            $kupacNazivParking = trim(($p['kupac_ime'] ?? '') . ' ' . ($p['kupac_prezime'] ?? ''));
                                                            if ($kupacNazivParking === '' && !empty($p['kupac_id'])) {
                                                                $kupacNazivParking = 'Kupac #' . $p['kupac_id'];
                                                            }
                                                            ?>
                                                            <?php if ($prodat && !empty($p['kupac_id']) && $kupacNazivParking !== '') : ?>
                                                                <a href="kupci_detaljno.php?id=<?= $p['kupac_id'] ?>" class="badge badge-primary" style="text-decoration: none; color: white;">
                                                                    <?= htmlspecialchars($kupacNazivParking) ?>
                                                                </a>
                                                            <?php else : ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($p['napomena'] ?? '') ?></td>
                                                        <td>
                                                            <?php if ($prodat) : ?>
                                                                <?php if (($p['tip_prodaje'] ?? null) === 'kompenzacija') : ?>
                                                                    <span class="badge badge-warning">Kompenzacija</span>
                                                                <?php elseif (($p['tip_prodaje'] ?? null) === 'lokacija') : ?>
                                                                    <span class="badge badge-danger">Lokacija</span>
                                                                <?php else : ?>
                                                                    <span class="badge badge-success">Prodato</span>
                                                                <?php endif; ?>
                                                            <?php else : ?>
                                                                <span class="badge badge-secondary">Nije prodato</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th>UKUPNO</th>
                                                    <th class="text-right"><?= number_format(array_sum(array_column($parking_mesta, 'cena')), 0, ',', '.') ?> € / <?= number_format(array_sum(array_map(function($p) { return $p['cena']; }, array_filter($parking_mesta, function($p) use ($prodati_parking_ids) { return in_array($p['id'], $prodati_parking_ids); }))), 0, ',', '.') ?> € / <?= number_format($ukupno_uplaceno_parking, 0, ',', '.') ?> €</th>
                                                    <th>-</th>
                                                    <th>-</th>
                                                    <th class="text-right"><?= count($parking_mesta) ?>/<?= $prodato_parking[0]['count'] ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
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

</body>

</html>