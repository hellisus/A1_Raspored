<?php
require_once '../config.php';


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "kupci";

$rezultat = $gradilista->select(['*'], [], "SELECT * FROM kupci WHERE naziv = '" . $_POST['naziv'] . "' AND  gradiliste = " . $_POST['tip']);

print_r($rezultat);

if (count($rezultat) == 0) {

    $gradilista->insert([
        'naziv' => $_POST['naziv'],
        'gradiliste' => $_POST['tip'],
    ]);
}
