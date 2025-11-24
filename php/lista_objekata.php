<?php
require 'config.php';
if (!isset($_SESSION['Ime'])) {
  header("location:../index.php");
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta http-equiv="refresh" content="1440;url=../php/logout.php" />

  <title>GP RAZ - Lista objekata za izmenu</title>

  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <!-- Our Custom CSS -->
  <link rel="stylesheet" href="../src/css/style.css" />

  <!-- Font Awesome JS -->
  <script src="https://kit.fontawesome.com/71c0b925fc.js" crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/funkcije.js"></script>

</head>

<body>
  <div class="wrapper">
    <!-- Sidebar  -->
    <?php require_once 'sidebar.php' ?>

    <!-- Page Content  -->
    <div id="content">
      <!-- Topbar  -->
      <?php require_once 'topbar.php' ?>
      <div class="containter">

        <div class="d-flex flex-column justify-content-center align-items-center">

          <h3 class="center sekcija">LISTA OBJEKATA ZA IZMENU <i class="fas fa-edit"></i></h3> <br>

          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Naziv</th>
                  <th scope="col">Broj stanova</th>
                  <th scope="col">Broj lokala</th>
                  <th scope="col">Broj garaža</th>
                  <th scope="col">Broj parking mesta</th>
                  <th scope="col">Akcije</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $crud = new CRUD($_SESSION['godina']);
                $crud->table = "objekti";
                // Sortiranje po nazivu objekta - koristi direktni SQL upit
                $stmt = $crud->query("SELECT * FROM objekti ORDER BY naziv ASC");
                $objekti = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                
                if (count($objekti) === 0): ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted">Nema unetih objekata.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($objekti as $objekat): ?>
                    <tr>
                      <td><?= (int)$objekat['id'] ?></td>
                      <td><?= htmlspecialchars($objekat['naziv']) ?></td>
                      <td><?= (int)$objekat['broj_stanova'] ?></td>
                      <td><?= (int)$objekat['broj_lokala'] ?></td>
                      <td><?= (int)$objekat['broj_garaza'] ?></td>
                      <td><?= (int)$objekat['broj_parkinga'] ?></td>
                      <td>
                        <a href="izmena_objekta.php?id=<?= $objekat['id'] ?>" class="btn btn-primary btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                          <i class="fas fa-edit"></i> Izmeni
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="line"></div>

        </div><!-- containter -->
      </div> <!-- content -->

</body>

</html>
