<?php
require_once '../config.php';
$nalog =  $_GET['id'];

$korisnici = new CRUD($_SESSION['godina']);
$korisnici->table = "korisnici";

$korisnici->delete(['id' => $nalog]);
