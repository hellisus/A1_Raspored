<?php
require_once '../config.php';

foreach ($_POST['radnik'] as $radnik) {
    $id_radnika = intval($radnik);
    $podatci = new CRUD($_SESSION['godina']);
    $podatci->table = "plate";

    $podatci->insert([
        'radnik_id' => $id_radnika,
        'datum' => $_POST['datum'],
        'sati' => $_POST['sati'],
        'gradiliste_id' => $_POST['mesto_uplate']
    ]);

    $stanje_avansa = new CRUD($_SESSION['godina']);
    $stanje_avansa->table = "radnici";

    $racun = $stanje_avansa->select(['*'], ['radnik_id' => $id_radnika]);
    $ime = $stanje_avansa->select(['ime'], ['radnik_id' => $id_radnika]);
    $prezime =  $stanje_avansa->select(['prezime'], ['radnik_id' => $id_radnika]);
    $eura_po_satu = intval($_POST['sati']) * $racun[0]['satnica'];
    $povecanje = $racun[0]['stanje'] +  $eura_po_satu;
    $stanje_avansa->update(['stanje' => $povecanje], ['radnik_id' =>  $id_radnika]);

    $trosak_gradilista = new CRUD($_SESSION['godina']);
    $trosak_gradilista->table = "registar";

    $trosak_gradilista->insert([
        'priliv_rashod' => 3,
        'mesto_uplate' => $_POST['mesto_uplate'],
        'tip' => 999, //unos kroz karnet da se ne broji
        'iznos' => $eura_po_satu,
        'pdv' => 0,
        'placeno' => 1,
        'komentar' => "Dnevnice " . $eura_po_satu . "€  za radnika " . $ime[0]['ime'] . " " . $prezime[0]['prezime'] . " za mesec " . $_POST['datum'] . " za gradilište " . $_POST['mesto_uplate'],
        'korisnik' => $_SESSION['Ime'] . " " . $_SESSION['Prezime']
    ]);
    /*
    $trosak_gradilista_stanje = new CRUD($_SESSION['godina']);
    $trosak_gradilista_stanje->table = "gradilista";
    $racun = $trosak_gradilista_stanje->select(['*'], ['id' => $_POST['mesto_uplate']]);
    $smanjenje = $racun[0]['stanje'] -  $eura_po_satu;
    $trosak_gradilista_stanje->update(['stanje' => $smanjenje], ['id' =>  $_POST['mesto_uplate']]);


    //----------------------------------------

*/

    $vrati_id_na_platu = new CRUD($_SESSION['godina']);
    $vrati_id_na_platu->table = "registar";
    $novi_id = $vrati_id_na_platu->select(['*'], [], "SELECT MAX(registar.id) as id_registar FROM registar");
    $novi_id_plata = $podatci->select(['*'], [], "SELECT MAX(plate.id_plate) as id_plate FROM plate");
    $podatci->update(['registar_id' => $novi_id[0]['id_registar']], ['id_plate' => $novi_id_plata[0]['id_plate']]);
}
