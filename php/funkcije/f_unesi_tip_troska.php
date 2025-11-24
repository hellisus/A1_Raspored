<?php
require_once '../config.php';


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "tip_troska";

$rezultat = $gradilista->select(['*'], [], "SELECT * FROM tip_troska WHERE naziv = '" . $_POST['naziv'] . "' AND  gradiliste_trosak = " . $_POST['tip']);

print_r($rezultat);

if (count($rezultat) == 0) {

    $gradilista->insert([
        'naziv' => $_POST['naziv'],
        'gradiliste_trosak' => $_POST['tip'],
    ]);
}
