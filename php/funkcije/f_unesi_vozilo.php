<?php
require_once '../config.php';


$vozila = new CRUD($_SESSION['godina']);
$vozila->table = "vozila";

$rezultat = $vozila->select(['*'], ['naziv' => $_POST['naziv']]);

if (count($rezultat) == 0) {

    $vozila->insert([
        'naziv' => $_POST['naziv'],
        'stanje' => $_POST['stanje'],
        'registracija' => $_POST['registracija'],
        'grad' => $_POST['grad'],
    ]);
}
