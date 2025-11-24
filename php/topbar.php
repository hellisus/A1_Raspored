   <!-- Topbar  -->
   <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
          <button type="button" id="sidebarCollapse" class="btn btn-info" onclick="
            console.log('Button clicked directly');
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
              sidebar.classList.toggle('active');
              console.log('Sidebar toggled, classes:', sidebar.className);
            } else {
              console.log('Sidebar not found');
            }
            return false;
          ">
            <i class="fas fa-align-left"></i>
            <span>Prikaži/sakri meni</span>
          </button>
          <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse"
            data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
            <i class="fas fa-align-justify"></i>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="nav navbar-nav ml-auto">
              <li class="nav-item active">
                <a class="nav-link" href="#"><i class="far fa-user"></i> Ulogovan :
                  <?php echo (isset($_SESSION['Ime']) ? $_SESSION['Ime'] : "Neregistrovani korisnik"); ?> </a>
              </li>
              <li class="nav-item active">

              <span>                    </span>
                <a id="time" class="nav-link" href="#">  
                  <script>
                  (function() {
                    function checkTime(i) {
                      return i < 10 ? '0' + i : i;
                    }

                    function startTime() {
                      var today = new Date(),
                        h = checkTime(today.getHours()),
                        m = checkTime(today.getMinutes());
                      document.getElementById('time').innerHTML =
                      '<i class="far fa-clock"></i> ' + h + ':' + m;
                      t = setTimeout(function() {
                        startTime();
                      }, 500);
                    }
                    startTime();
                  })();
                  </script>
                   
                </a>
              </li>
            </ul>
          </div>
        </div>
      </nav>