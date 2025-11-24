<?php
require_once '../config.php';


$radnici = new CRUD($_SESSION['godina']);
$radnici->table = "radnici";

print_r($radnici);

$radnici->insert([
    'ime' => $_POST['ime'],
    'prezime' => $_POST['prezime'],
    'opis' => $_POST['opis'],
    'satnica' => $_POST['satnica'],
    'grad' => $_POST['grad'],
    'aktivan' => $_POST['aktivan'],
    'stanje' => $_POST['stanje'],
    'obrok' => $_POST['obrok'],
]);
