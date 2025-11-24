<?php
require_once '../config.php';



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
    'priliv_rashod' => 3,
    'mesto_uplate' => $_POST['mesto_uplate'],
    'tip' => $_POST['tip_troska'],
    'iznos' => $iznos,
    'pdv' => $_POST['pdv'],
    'komentar' => $_POST['komentar'],
    'korisnik' => $korisnik,
    'placeno' => $_POST['placeno'],
    'pdv_datum' => $_POST['pdv_datum'],
]);


$racuni = new CRUD($_SESSION['godina']);
$racuni->table = "gradilista";

$id = intval($_POST['mesto_uplate']);

$racun = $racuni->select(['*'], ['id' => $id]);

$smanjenje = $racun[0]['stanje'] - $iznos;


$racuni->update(['stanje' => $smanjenje], ['id' =>  $id]);
