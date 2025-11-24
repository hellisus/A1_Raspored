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

  <title>GP RAZ - Lista kupaca za izmenu</title>

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

          <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['error_message'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
          <?php endif; ?>

          <h3 class="center sekcija">LISTA KUPACA ZA IZMENU <i class="fas fa-edit"></i></h3> <br>

          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Ime</th>
                  <th scope="col">Prezime</th>
                  <th scope="col">Telefon</th>
                  <th scope="col">Email</th>
                  <th scope="col">Napomena</th>
                  <th scope="col">Akcije</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $crud = new CRUD($_SESSION['godina']);
                $crud->table = "kupci";
                $kupci = $crud->select(['*'], [], "SELECT * FROM kupci ORDER BY ime ASC, prezime ASC");
                
                if (count($kupci) === 0): ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted">Nema unetih kupaca.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($kupci as $kupac): ?>
                    <tr>
                      <td><?= (int)$kupac['id'] ?></td>
                      <td><?= htmlspecialchars($kupac['ime']) ?></td>
                      <td><?= htmlspecialchars($kupac['prezime'] ?? '') ?></td>
                      <td><?= htmlspecialchars($kupac['br_telefona']) ?></td>
                      <td><?= isset($kupac['email']) && $kupac['email'] ? '<a href="mailto:' . htmlspecialchars($kupac['email']) . '" class="text-primary"><i class="fas fa-envelope"></i> ' . htmlspecialchars($kupac['email']) . '</a>' : '<span class="text-muted">—</span>' ?></td>
                      <td><?= htmlspecialchars($kupac['napomena'] ?? '') ?></td>
                      <td>
                        <a href="izmena_kupca.php?id=<?= $kupac['id'] ?>" class="btn btn-primary btn-md" style="min-width: 100px; padding: 8px 16px; color: white !important;">
                          <i class="fas fa-edit"></i> Izmeni
                        </a>
                        <a href="obrisi_kupca.php?id=<?= $kupac['id'] ?>" class="btn btn-danger btn-md ml-2" style="min-width: 100px; padding: 8px 16px; color: white !important;" onclick="return confirm('Da li ste sigurni da želite da obrišete ovog kupca?')">
                          <i class="fas fa-trash"></i> Obriši
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
