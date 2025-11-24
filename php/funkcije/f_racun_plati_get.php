<?php
require_once '../config.php';

if (isset($_POST['racun_id'])) {
    $racun_id = $_POST['racun_id'];

    $podatci = new CRUD($_SESSION['godina']);
    $podatci->table = "registar";
    $tipovi = $podatci->update(['placeno' => 1], ['id' => $racun_id]);
};
