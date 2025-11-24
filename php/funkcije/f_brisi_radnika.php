<?php
require_once '../config.php';
$nalog =  $_GET['id'];

$radnici = new CRUD($_SESSION['godina']);
$radnici->table = "radnici";

$radnici->delete(['radnik_id' => $nalog]);

$plate = new CRUD($_SESSION['godina']);
$plate->table = "plate";

$plate->delete(['radnik_id' => $nalog]);
