<?php
require_once '../config.php';

$hovano = hash256($_POST['lozinka']);
$nalog =  $_POST['id'];


$korisnici = new CRUD($_SESSION['godina']);
$korisnici->table = "korisnici";




$korisnici->update(['ime' => $_POST['ime'], 
'prezime' => $_POST['prezime'],
'korisnickoime' => $_POST['korisnickoime'],
'lozinka' => $_POST['lozinka'],
'hash' => $hovano,
'tip' => $_POST['tip'],
'aktivan' => 1 ], ['id' => $nalog] );