<?php
require_once '../config.php';


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "gradilista";

$rezultat = $gradilista->select(['*'], ['naziv' => $_POST['naziv']]);

if (count($rezultat) == 0) {

    $gradilista->insert([
        'naziv' => $_POST['naziv'],
        'stanje' => $_POST['stanje'],
        'tip' => $_POST['tip'],
        'grad' => $_POST['grad'],
    ]);
}
