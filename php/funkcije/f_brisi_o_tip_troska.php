<?php
require_once '../config.php';
$nalog =  $_GET['id'];

$gradilista = new CRUD($_SESSION['godina']);
$gradilista->table = "ostali_troskovi";

$gradilista->delete(['id' => $nalog]);
