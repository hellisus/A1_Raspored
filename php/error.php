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
      <?php
    if (!isset($_SESSION['poruka'])) {
	$_SESSION['poruka'] = "Pogrešno korisničko ime ili lozinka!";

}?>
      <h1><?php echo($_SESSION["poruka"]) ; ?></h1>
      <p>Pokušajte ponovo</p> 
      <button class="btn btn-success col-sm-12" onclick="back()">Povratak na unos korisničkog imena i lozinke </button>
    </div>
  </div>

    <!-- jQuery CDN - Full version -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous">
    </script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous">
    </script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous">
    </script>
</body>

</html>