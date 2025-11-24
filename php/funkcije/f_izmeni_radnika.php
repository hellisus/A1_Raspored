<?php
require_once '../config.php';

$nalog =  $_POST['id'];


$radnici = new CRUD($_SESSION['godina']);
$radnici->table = "radnici";

$radnici->update([
    'ime' => $_POST['ime'],
    'prezime' => $_POST['prezime'],
    'opis' => $_POST['opis'],
    'satnica' => $_POST['satnica'],
    'grad' => $_POST['grad'],
    'aktivan' => $_POST['aktivan'],
    'stanje' => $_POST['stanje'],
    'obrok' => $_POST['obrok'],
], ['radnik_id' => $nalog]);
