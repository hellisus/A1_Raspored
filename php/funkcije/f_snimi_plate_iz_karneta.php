<?php
require_once '../config.php';



global $gradilista_koja_ima;
$gradilista_koja_ima = [];
$podatci = new CRUD($_SESSION['godina']);
$podatci->table = "plate";
$rezultat = $podatci->select(['*'], [], "SELECT gradilista.id as gradiliste_koje_ima FROM plate LEFT JOIN gradilista ON plate.gradiliste_id = gradilista.id WHERE gradilista.grad = 1 AND plate.datum  = " . $_POST['mesec'] . " GROUP BY gradilista.id");
print_r($gradilista_koja_ima);
foreach ($rezultat as $gradiliste) {
    array_push($gradilista_koja_ima, $gradiliste['gradiliste_koje_ima']);
};

foreach ($gradilista_koja_ima as $br_gradilista) {
    $podatci4 = new CRUD($_SESSION['godina']);
    $podatci4->table = "plate";
    $rezultat4 = $podatci4->select(['*'], [], "SELECT SUM(plate.sati * radnici.satnica) as sumsati FROM plate JOIN radnici ON plate.radnik_id = radnici.radnik_id WHERE gradiliste_id = " . $br_gradilista . " AND datum = " . $_POST['mesec']);


    $komenatar = "Dnevnice za gradilište " . $br_gradilista . " za mesec : " .  trim($_POST['mesec']);
    $korisnik = $_SESSION['Ime'] . " " . $_SESSION['Prezime'];

    $podatciupis = new CRUD($_SESSION['godina']);
    $podatciupis->table = "registar";
    $podatciupis->insert([
        'priliv_rashod' => 3,
        'mesto_uplate' => $br_gradilista,
        'iznos' => $rezultat4[0]['sumsati'],
        'tip' => 4,
        'komentar' => $komenatar,
        'pdv' => 1,
        'placeno' => 1,
        'korisnik' => $korisnik,
    ]);

    $podatciupis = new CRUD($_SESSION['godina']);
    $podatciupis->table = "registar";

    echo "Suma sati za gradilište " . $br_gradilista . " je " . $rezultat4[0]['sumsati'] . " <br>";
}
