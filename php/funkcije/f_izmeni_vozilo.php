<?php
require_once '../config.php';

$nalog =  $_POST['id'];


$vozila = new CRUD($_SESSION['godina']);
$vozila->table = "vozila";

$vozila->update([
    'naziv' => $_POST['naziv'],
    'registracija' => $_POST['registracija'],
    'grad' => $_POST['grad'],
    'stanje' => $_POST['stanje']
], ['vozilo_id' => $nalog]);
