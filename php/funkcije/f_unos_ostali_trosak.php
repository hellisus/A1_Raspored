<?php
require_once '../config.php';

if ($_POST['tip_o_troska']  == '6') {
    $priliv_rashod = 5;
} else {
    $priliv_rashod = 4;
}

$registri = new CRUD($_SESSION['godina']);
$registri->table = "registar";

$korisnik = $_SESSION['Ime'] . " " . $_SESSION['Prezime'];


if ($_POST['pdv'] == 1) {
    $iznos = $_POST['iznos'] + $_POST['iznos'] / 100 * 20;
} else {
    $iznos = $_POST['iznos'];
};


if ($_POST['dinari'] == 1) {
    $iznos = $_POST['iznos'] / $_SESSION['euro'];
} else {
    $iznos = $_POST['iznos'];
};


$registri->insert([
    'priliv_rashod' => $priliv_rashod,
    'tip' => $_POST['tip_o_troska'],
    'iznos' => $iznos,
    'pdv' => $_POST['pdv'],
    'komentar' => $_POST['komentar'],
    'korisnik' => $korisnik,
    'placeno' => $_POST['placeno'],
    'vozilo' => $_POST['vozilo'],
    'pdv_datum' => $_POST['pdv_datum'],
]);


$racuni = new CRUD($_SESSION['godina']);
$racuni->table = "vozila";

$id = intval($_POST['vozilo']);
$racun = $racuni->select(['*'], ['vozilo_id' => $id]);
$smanjenje = $racun[0]['stanje'] - $iznos;
$racuni->update(['stanje' => $smanjenje], ['vozilo_id' =>  $id]);


$racuni = new CRUD($_SESSION['godina']);
$racuni->table = "ostali_troskovi";
$id = $_POST['tip_o_troska'];


$racun = $racuni->select(['*'], ['id' => $id]);
$smanjenje = $racun[0]['stanje'] - $iznos;
$racuni->update(['stanje' => $smanjenje], ['id' =>  $id]);
