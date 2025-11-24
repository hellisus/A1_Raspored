<?php
require_once '../config.php';

$id =  $_POST['id'];


$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "ostali_troskovi";


$gradilista->update([
    'naziv' => $_POST['naziv'],
], ['id' => $id]);
