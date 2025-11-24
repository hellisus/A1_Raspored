

<!DOCTYPE html>
<html lang="RS">

<head>
    <!--Import bootstrap.css-->
    <link rel="stylesheet" href="dep/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="src/css/style.css">
    <title>A1 Nalozi --- Prijava na sistem</title>
    <meta charset="UTF-8">
    <!--Fontovi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,100..900;1,100..900&family=League+Gothic&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!--Let browser know website is optimized for mobile-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />


</head>

<body class="exo-2-standard">

    <div class="d-flex flex-column min-vh-100 justify-content-center align-items-center">
        <img src="img/raz-invest-logo.png" alt="RAZ INVEST logo">
        <h1>Prijavite se na A1 naloge</h1>
        <p>Unesite svoje korisničko ime i lozinku</p>

        <form action=<?php echo "php/credproces.php";?> method="POST">
            <div class="form-group row">
                <label for="uid" class="col-sm-3 col-form-label">Nalog: </label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="uid" name="uid" placeholder="Naziv korisničkog naloga">
                </div>
            </div>

            <div class="form-group row">
                <label for="pwd" class="col-sm-3 col-form-label">Lozinka: </label>
                <div class="col-sm-9">
                    <input type="password" class="form-control" id="pwd" name="pwd" placeholder="Lozinka">
                </div>
            </div>

            <button type="submit" class="btn btn-success col-sm-12">Prijavi se </button>
        </form>
    </div>

    <script type="text/javascript" src="dep/jquery/jquery-3.6.0.min.js"></script>
    <script src="dep/bootstrap/js/bootstrap.min.js"></script>


</body>

</html>