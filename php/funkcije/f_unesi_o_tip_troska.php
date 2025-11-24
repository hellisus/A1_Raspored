<?php
require_once '../config.php';


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "ostali_troskovi";

$rezultat = $gradilista->select(['*'], [], "SELECT * FROM ostali_troskovi WHERE naziv = '" . $_POST['naziv'] . "'");


if (count($rezultat) == 0) {

    $gradilista->insert([
        'naziv' => $_POST['naziv'],
        'stanje'  => 0,
    ]);
}
