<?php
require_once '../config.php';

$id =  $_POST['id'];


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "kupci";


$gradilista->update([
    'naziv' => $_POST['naziv'],
    'gradiliste' => $_POST['tip']
], ['kupac_id' => $id]);
