<?php
require_once '../config.php';

$registri = new CRUD($_SESSION['godina']);
$registri->table = "registar";

$korisnik = $_SESSION['Ime'] . " " . $_SESSION['Prezime'];

if ($_POST['dinari'] == 1) {
    $iznos = intval($_POST['iznos']) / $_SESSION['euro'];
} else {
    $iznos = intval($_POST['iznos']);
};


$registri->insert([
    'priliv_rashod' => 2,
    'tip' => $_POST['tip_uplate'],
    'iznos' => $iznos,
    'pdv' => $_POST['pdv'],
    'placeno' => $_POST['placeno'],
    'komentar' => $_POST['komentar'],
    'korisnik' => $korisnik,
]);


//ubaci na račun uplate

$racuni = new CRUD($_SESSION['godina']);
$racuni->table = "uplate";

$racun = $racuni->select(['*'], ['uplata_id' => $_POST['tip_uplate']]);

$povecanje = $racun[0]['stanje'] +  $iznos;
$racuni->update(['stanje' => $povecanje], ['uplata_id' =>   $_POST['tip_uplate']]);
