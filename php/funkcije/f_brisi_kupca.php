<?php
require_once '../config.php';
$nalog =  $_GET['id'];

$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "kupci";

$gradilista->delete(['kupac_id' => $nalog]);
