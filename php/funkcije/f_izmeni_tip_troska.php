<?php
require_once '../config.php';

$id =  $_POST['id'];


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "tip_troska";


$gradilista->update([
    'naziv' => $_POST['naziv'],
    'gradiliste_trosak' => $_POST['tip']
], ['id' => $id]);
