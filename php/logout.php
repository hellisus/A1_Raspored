<?php
require 'config.php';
?>

<!DOCTYPE html>
<html lang="RS">

<head>
  <!--Import bootstrap.css-->
  <link rel="stylesheet" href="../dep/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../src/css/style.css">
  <title>A1 nalozi --- Odjava sa sistema</title>
  <meta charset="UTF-8">
  <script src="../src/js/funkcije.js"></script>
  <!--Let browser know website is optimized for mobile-->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">
</head>

<body class="exo-2-standard">

  <div class="d-flex flex-column min-vh-100 justify-content-center align-items-center">
    <img src="../img/raz-invest-logo.png" alt="RAZ INVEST logo"></img>
    <div class="auth-card">
      <h1>Odjavljeni ste sa sistema usled neaktivnosti</h1>
      <p>Prijavite se ponovo</p>
      <button class="btn btn-success col-sm-12" onclick="logovanje()">Povratak na prijavu na sistem </button>
    </div>
  </div>


  <script type="text/javascript" src="../dep/jquery/jquery-3.6.0.min.js"></script>
  <script src="../dep/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>