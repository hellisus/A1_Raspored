<?php
require_once '../config.php';


$racuni = new CRUD($_SESSION['godina']);
$racuni->table = "racuni";




$racuni->update(['vrednost' => $_POST['iznos_nlb']], ['id_racun' => 1]);
$racuni->update(['vrednost' => $_POST['iznos_inteza_raz']], ['id_racun' => 2]);
$racuni->update(['vrednost' => $_POST['iznos_inteza_raz_invest']], ['id_racun' => 3]);
$racuni->update(['vrednost' => $_POST['iznos_unicredit_raz']], ['id_racun' => 4]);
$racuni->update(['vrednost' => $_POST['iznos_unicredit_raz_invest']], ['id_racun' => 5]);
$racuni->update(['vrednost' => $_POST['iznos_gotovina']], ['id_racun' => 6]);
$racuni->update(['vrednost' => $_POST['iznos_bolovanja']], ['id_racun' => 7]);
$racuni->update(['vrednost' => $_POST['iznos_inteza_opk']], ['id_racun' => 8]);
$racuni->update(['vrednost' => $_POST['iznos_unicredit_opk']], ['id_racun' => 9]);
