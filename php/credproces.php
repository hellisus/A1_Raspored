<?php

require  'config.php';

$baza = 'srnalozi_a1_raspored';

// Dozvoli samo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../index.php');
	exit;
}

// Bezbednije čitanje unosa
$uid = filter_input(INPUT_POST, 'uid', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$pwd = filter_input(INPUT_POST, 'pwd', FILTER_UNSAFE_RAW); // hešira se, ne escapuje
$euro_val_raw = filter_input(INPUT_POST, 'euro_val', FILTER_UNSAFE_RAW);

// Validacija minimalnih polja
if ($uid === null || $uid === '' || $pwd === null || $pwd === '') {
	$_SESSION['poruka'] = "Nedostaju korisničko ime ili lozinka.";
	header('Location: error.php');
	exit;
}


$korisnici = new CRUD($baza);
$korisnici->table = "korisnici";

$hp = hash256($pwd);

// Autentikacija
$rezultat = $korisnici->select(['*'], ['korisnickoime' => $uid, 'hash' => $hp]);

if (!is_array($rezultat) || count($rezultat) < 1) {
	$_SESSION['poruka'] = "Pogrešno korisničko ime ili lozinka!";
	header('Location: error.php');
	exit;
}

// Uspela prijava: zaštita sesije
session_regenerate_id(true);

$_SESSION['Ime'] = $rezultat[0]['ime'];
$_SESSION['Prezime'] = $rezultat[0]['prezime'];
$_SESSION['korisnickoime'] = $rezultat[0]['korisnickoime'];
$_SESSION['id'] = $rezultat[0]['id'];
$_SESSION['logged_in'] = true;
$_SESSION['tip'] = intval($rezultat[0]['tip']);
$_SESSION['godina'] = $baza;

// Ne preuzimati nestruktuirane GET parametre bez validacije
$_SESSION['godina_info'] = null;

// Logovanje aktivnosti
$korisnici->update(
	['aktivan' => 1, 'vremelogovanja' => date("Y-m-d H:i:s")],
	['korisnickoime' => $_SESSION['korisnickoime']]
);
$korisnici->table = "korisnici_log";
$korisnici->insert([
	'korisnik' => $rezultat[0]['korisnickoime'],
	'vreme_prijave_na_sistem' => date("Y-m-d H:i:s")
]);

header('Location: ../php/glavni.php');
exit;