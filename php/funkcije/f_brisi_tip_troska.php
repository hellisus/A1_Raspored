<?php
require_once '../config.php';
$nalog =  $_GET['id'];

$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "tip_troska";

$gradilista->delete(['id' => $nalog]);
