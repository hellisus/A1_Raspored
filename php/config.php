<?php

/**
 * Created by PhpStorm.
 * User: Josip
 * Date: 20.9.2017.
 * Time: 07.55
 */
// pamti 8 sati
ini_set('session.gc_maxlifetime', 28800);

// pamti 8 sati
session_set_cookie_params(28800);
date_default_timezone_set('Europe/Belgrade');
session_start();




// Definicije
define('SALT', 'Icxchll3344');
define('db_name_PUBLIC', 'a1_raspored');

require 'CRUD.php';
require_once __DIR__ . '/funkcije/datum_format.php';

//Funkcije
function hash256($string_za_hesh)
{
	return hash('sha256', $string_za_hesh . SALT);
}