<?php
require_once '../config.php';
$nalog =  $_GET['id'];

$vozila = new CRUD($_SESSION['godina']);
$vozila->table = "vozila";

$vozila->delete(['vozilo_id' => $nalog]);
