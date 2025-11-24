<?php
require_once '../config.php';

foreach ($_POST['radnik'] as $radnik) {
    $id_radnika = intval($radnik);
    $podatci = new CRUD($_SESSION['godina']);
    $podatci->table = "avansi";

    if ($_POST['dinari'] == 1) {
        $iznos = $_POST['iznos_avansa'] / $_SESSION['euro'];
    } else {
        $iznos = $_POST['iznos_avansa'];
    };


    $komenatar = "Radnik " . $id_radnika . " - Avans : " . $iznos . "€ - tip : " .  $_POST['tip_avansa'];

    $podatci->insert([
        'radnik_id' => $id_radnika,
        'mesec' => $_POST['mesec'],
        'iznos' => $iznos,
        'tip' => $_POST['tip_avansa'],
        'komentar' => $komenatar
    ]);
};




$racun = new CRUD($_SESSION['godina']);
$racun->table = "radnici";
$s_racun = $racun->select(['*'], ['radnik_id' => $id_radnika]);
$smanjenje = $s_racun[0]['stanje'] - $iznos;
$racun->update(['stanje' => $smanjenje], ['radnik_id' =>  $id_radnika]);

