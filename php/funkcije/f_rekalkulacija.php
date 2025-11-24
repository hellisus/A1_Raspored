<?php

function rekalkulacija()
{
    require_once '../config.php';

    //gradili分ta

    $podatci2 = new CRUD($_SESSION['godina']);
    $podatci2->table = "gradilista";
    $rezultat2 = $podatci2->select(['*'], [], "SELECT DISTINCT id FROM gradilista ORDER by id");
    $gradilista_koja_ima = [];
    foreach ($rezultat2 as $razvrstaj) {
        array_push($gradilista_koja_ima, $razvrstaj['id']);
    };

    foreach ($gradilista_koja_ima as $gradiliste) {
        //gradili分ta
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "registar";
        $rezultat = $podatci->select(['*'], [], "SELECT * FROM registar WHERE registar.mesto_uplate = " . $gradiliste . " ORDER by mesto_uplate");
        $suma_ukupno = 0;
        foreach ($rezultat as $transakcija_za_gradiliste) {
            if ($transakcija_za_gradiliste['priliv_rashod'] < 3) {
                $suma_ukupno = $suma_ukupno + $transakcija_za_gradiliste['iznos'];
            } else {
                if ($transakcija_za_gradiliste['tip'] != 999) {
                    $suma_ukupno = $suma_ukupno - $transakcija_za_gradiliste['iznos'];
                }
            }
        }
        $update = new CRUD($_SESSION['godina']);
        $update->table = "gradilista";
        $update->update([
            'stanje' => $suma_ukupno
        ], ['id' => $gradiliste]);
    }


    //Ostali tro分kovi

    $podatci2 = new CRUD($_SESSION['godina']);
    $podatci2->table = "ostali_troskovi";
    $rezultat2 = $podatci2->select(['*'], [], "SELECT DISTINCT id FROM ostali_troskovi ORDER by id");
    $troskovi_kojih_ima = [];
    foreach ($rezultat2 as $razvrstaj) {
        array_push($troskovi_kojih_ima, $razvrstaj['id']);
    };

    foreach ($troskovi_kojih_ima as $trosak) {
        //ostali_troskovi
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "registar";
        $rezultat = $podatci->select(['*'], [], "SELECT * FROM registar WHERE registar.priliv_rashod > 3 AND registar.tip = " . $trosak . " ORDER by mesto_uplate");
        $suma_ukupno = 0;

        foreach ($rezultat as $transakcija_za_trosak) {

            $suma_ukupno = $suma_ukupno - $transakcija_za_trosak['iznos'];
        }

        $update = new CRUD($_SESSION['godina']);
        $update->table = "ostali_troskovi";
        $update->update([
            'stanje' => $suma_ukupno
        ], ['id' => $trosak]);
    }

    //uplate

    $podatci2 = new CRUD($_SESSION['godina']);
    $podatci2->table = "uplate";
    $rezultat2 = $podatci2->select(['*'], [], "SELECT DISTINCT uplata_id FROM uplate ORDER by uplata_id");
    $uplate_kojih_ima = [];
    foreach ($rezultat2 as $razvrstaj) {
        array_push($uplate_kojih_ima, $razvrstaj['uplata_id']);
    };

    foreach ($uplate_kojih_ima as $uplata) {
        //ostali_troskovi
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "registar";
        $rezultat = $podatci->select(['*'], [], "SELECT * FROM registar WHERE registar.priliv_rashod < 3 AND registar.tip = " . $uplata . " ORDER by mesto_uplate");
        $suma_ukupno = 0;

        foreach ($rezultat as $transakcija_za_uplatu) {

            $suma_ukupno = $suma_ukupno + $transakcija_za_uplatu['iznos'];
        }

        $update = new CRUD($_SESSION['godina']);
        $update->table = "uplate";
        $update->update([
            'stanje' => $suma_ukupno
        ], ['uplata_id' => $uplata]);
    }


    //vozila

    $podatci2 = new CRUD($_SESSION['godina']);
    $podatci2->table = "vozila";
    $rezultat2 = $podatci2->select(['*'], [], "SELECT DISTINCT vozilo_id FROM vozila ORDER by vozilo_id");
    $vozila_kojih_ima = [];
    foreach ($rezultat2 as $razvrstaj) {
        array_push($vozila_kojih_ima, $razvrstaj['vozilo_id']);
    };

    foreach ($vozila_kojih_ima as $vozilo) {
        //vozila
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "registar";
        $rezultat = $podatci->select(['*'], [], "SELECT * FROM registar WHERE priliv_rashod = 5 AND vozilo = " . $vozilo . " ORDER by vozilo");
        $suma_ukupno = 0;

        foreach ($rezultat as $transakcija_za_uplatu) {

            $suma_ukupno = $suma_ukupno - $transakcija_za_uplatu['iznos'];
        }

        $update = new CRUD($_SESSION['godina']);
        $update->table = "vozila";
        $update->update([
            'stanje' => $suma_ukupno
        ], ['vozilo_id' => $vozilo]);
    }

    //Radnici

    $podatci2 = new CRUD($_SESSION['godina']);
    $podatci2->table = "radnici";
    $rezultat2 = $podatci2->select(['*'], [], "SELECT DISTINCT radnik_id FROM radnici ORDER by radnik_id");
    $radnika_kojih_ima = [];
    foreach ($rezultat2 as $razvrstaj) {
        array_push($radnika_kojih_ima, $razvrstaj['radnik_id']);
    };

    foreach ($radnika_kojih_ima as $radnik) {
        //plate
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "plate";
        $rezultat = $podatci->select(['*'], [], "SELECT * FROM plate  LEFT JOIN radnici ON plate.radnik_id = radnici.radnik_id WHERE plate.radnik_id = " . $radnik . " ORDER by plate.radnik_id");
        $suma_ukupno = 0;

        foreach ($rezultat as $transakcija_za_uplatu) {

            $suma_ukupno = $suma_ukupno + ($transakcija_za_uplatu['sati'] * $transakcija_za_uplatu['satnica']);
        }

        $update2 = new CRUD($_SESSION['godina']);
        $update2->table = "radnici";
        $update2->update([
            'stanje' => $suma_ukupno
        ], ['radnik_id' => $radnik]);
    }



    // Logging
    error_log("Rekalkulacija " . $_SESSION['godina'] . " Završena");
    header("location:../brisanje_transakcije.php");
}

//rekalkulacija();
