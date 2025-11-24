<?php
require_once '../config.php';

$id =  $_POST['id'];


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "gradilista";


$gradilista->update([
    'naziv' => $_POST['naziv'],
    'grad' => $_POST['grad'],
    'tip' => $_POST['tip'],
    'stanje' => $_POST['stanje']
], ['id' => $id]);
