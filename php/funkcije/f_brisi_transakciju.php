<?php
require_once '../config.php';


$nalog =  $_GET['id'];

$registar = new CRUD($_SESSION['godina']);
$registar->table = "registar";

$registar->delete(['id' => $nalog]);

$plate = new CRUD($_SESSION['godina']);
$plate->table = "plate";
$plate->delete(['registar_id' => $nalog]);


require_once '../funkcije/f_rekalkulacija.php';
rekalkulacija();


