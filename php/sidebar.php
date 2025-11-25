   <!-- Sidebar  -->
   <nav id="sidebar">
       <div class="sidebar-header">
           <img src="../img/raz-invest-logo.png" alt="RAZ INVEST"  onclick="window.location.replace('../php/glavni.php');" />
       </div>

       <ul class="list-unstyled components <?php if ($_SESSION['godina']  == 'programr_gpraz_sum') echo "d-none";  ?>">

           <li class="<?php if ($_SESSION['tip']  > 2) echo "d-none" ?>">
               <a href="../php/import.php">Uvoz baze</a>
           </li>
           <li class="<?php if ($_SESSION['tip']  > 2) echo "d-none" ?>">
               <a href="../php/raspored.php">Pregled rasporeda</a>
           </li>
           <li class="<?php if ($_SESSION['tip']  > 2) echo "d-none" ?>">
               <a href="../php/uplate_po_mesecima.php"><i class="fas fa-calendar-alt"></i> UPLATE PO MESECIMA</a>
           </li>
           
           <li class="<?php if ($_SESSION['tip']  > 2) echo "d-none" ?>">
               <a href="../php/kalkulator.php"><i class="fas fa-calculator"></i> KALKULATOR CENA</a>
           </li>
           <li class="<?php if ($_SESSION['tip']  > 2) echo "d-none" ?>">
               <a href="#pregledDropdown" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">PREGLED</a>
               <ul class="collapse list-unstyled" id="pregledDropdown">
                   <li><a href="../php/glavni.php">Pregled objekata</a></li>
                   <li><a href="../php/pregled_stanova.php">Pregled stanova</a></li>
                   <li><a href="../php/pregled_lokala.php">Pregled lokala</a></li>
                   <li><a href="../php/pregled_garaza.php">Pregled garaža</a></li>
                   <li><a href="../php/pregled_parking_mesta.php">Pregled parking mesta</a></li>
                   <li><a href="../php/pregled_kupaca.php">Pregled kupaca</a></li>
               </ul>
           </li>

           <li class="<?php if ($_SESSION['tip']  > 2) echo "d-none" ?>">
               <a href="#unosDropdown" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">UNOS</a>
               <ul class="collapse list-unstyled" id="unosDropdown">
                   <li><a href="../php/unos_objekta.php">Unesi objekat</a></li>
                   <li><a href="../php/unos_stana.php">Unesi stan</a></li>
                   <li><a href="../php/unos_lokala.php">Unesi lokal</a></li>
                   <li><a href="../php/unos_garaze.php">Unesi garažu</a></li>
                   <li><a href="../php/unos_parking_mesta.php">Unesi parking mesto</a></li>
                   <li><a href="../php/unos_kupca.php">Unesi kupca</a></li>
               </ul>
           </li>


           <li class="<?php if ($_SESSION['tip']  > 2) echo "d-none" ?>">
               <a href="#izmenaDropdown" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">IZMENA</a>
               <ul class="collapse list-unstyled" id="izmenaDropdown">
                   <li><a href="../php/lista_objekata.php">Izmeni objekat</a></li>
                   <li><a href="../php/lista_stanova.php">Izmeni stan</a></li>
                   <li><a href="../php/lista_lokala.php">Izmeni lokal</a></li>
                   <li><a href="../php/lista_garaza.php">Izmeni garažu</a></li>
                   <li><a href="../php/lista_parking_mesta.php">Izmeni parking mesto</a></li>
                   <li><a href="../php/lista_kupaca.php">Izmeni kupca</a></li>
               </ul>
           </li>

          <!-- <li class="<?php if ($_SESSION['tip'] != 1) echo "d-none" ?>">
               <a href="../php/bilans.php">BILANS STANJA</a>
           </li>-->

 <!--
           <li class="<?php if ($_SESSION['tip']  > 5) echo "d-none";
                        if ($_SESSION['godina']  == 'programr_gpraz_sum') echo "d-none";  ?>">
               <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle "><i class="fas fa-hard-hat"></i> Radnici</a>
               <ul class="collapse list-unstyled" id="pageSubmenu">
                   <li class="<?php if ($_SESSION['tip'] > 2) echo "d-none" ?>">
                       <a href="#pageSubSubmenu1" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle "><i class="fas fa-print"></i> Unos, izmena, brisanje</a>
                       <ul class="collapse list-unstyled" id="pageSubSubmenu1">
                           <li>
                               <a href="unos_radnika.php">Unos novog radnika</a>
                           </li>
                           <li>
                               <a href="izmena_radnika.php">Izmena ili brisanje radnika</a>
                           </li>
                       </ul>
                   </li>
                   <li class="<?php if ($_SESSION['tip'] > 3) echo "d-none" ?>">
                       <a href="#pageSubSubmenuNS" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle "><i class="fas fa-print"></i> Novi Sad</a>
                       <ul class="collapse list-unstyled" id="pageSubSubmenuNS">
                           <li>
                               <a href="unos_avansa_NS.php">Unos avansa NS</a>
                           </li>
                           <li>
                               <a href="izvestaj_radnici_NS.php">Radnici NS </a>
                           </li>
                           <li>
                               <a href="unos_radnih_sati_NS.php">Unos radnih sati NS</a>
                           </li>
                           <li>
                               <a href="karnet_mesec_selektor_NS.php">Karnet i platna lista NS</a>
                           </li>
                       </ul>
                   </li>

                   <li class="<?php if ($_SESSION['tip'] > 4) echo "d-none" ?>">
                       <a href="#pageSubSubmenuNIS" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-print"></i> NIŠ</a>
                       <ul class="collapse list-unstyled" id="pageSubSubmenuNIS">
                           <li>
                               <a href="unos_avansa_NIS.php">Unos avansa NIŠ</a>
                           </li>
                           <li>
                               <a href="izvestaj_radnici_NIS.php">Radnici NIŠ </a>
                           </li>
                           <li>
                               <a href="unos_radnih_sati_NIS.php">Unos radnih sati NIŠ</a>
                           </li>
                           <li>
                               <a href="karnet_mesec_selektor_NIS.php">Karnet i platna lista NIŠ</a>
                           </li>
                       </ul>
                   </li>

               </ul>
           </li>

           <li>
               <a href="#pageSubmenu2" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="fas fa-print"></i> Izveštaji</a>
               <ul class="collapse list-unstyled" id="pageSubmenu2">
                   <li>
                       <a href="izvestaj_period_forma_PDV.php">Izveštaj PDV po periodu</a>
                   </li>
                   <li>
                       <a href="izvestaj_kupci_gradiliste_forma.php">Izveštaj po kupcu</a>
                   </li>
                   <li>
                       <a href="izvestaj_troskovi_gradiliste_forma.php">Izveštaj troškovi po gradilištu</a>
                   </li>
                   <li>
                       <a href="izvestaj_prihodi_gradiliste_forma.php">Izveštaj prihodi po gradilištu</a>
                   </li>
               </ul>
           </li>

           <!--
       <li>
         <a href="#pageSubmenu3" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="fas fa-car"></i> Vozila</a>
         <ul class="collapse list-unstyled" id="pageSubmenu3">
           <li>
             <a href="vozila_.php">Pregled vozila</a>
           </li>
           <li>
             <a href="izvestaj_mesto_forma.php">Izveštaj po mestu/tipu/računu</a>
           </li>
           <li>
             <a href="izvestaj_mesto_zbir_forma.php">Izveštaj po mestu zbirno</a>
           </li>

         </ul>
       </li>

           <li>
               <a href="#homeSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"> <i class="fas fa-file-signature"></i></i> Izmena
                   kataloga</a>
               <ul class="collapse list-unstyled" id="homeSubmenu">
                   <li>
                       <a href="#pageSubmenu51" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="far fa-user"></i> Korisnici</a>
                       <ul class="collapse list-unstyled" id="pageSubmenu51">
                           <li>
                               <a href="../php/korisinci_unos.php"><i class="far fa-user"></i> Dodaj korisnika</a>
                           </li>
                           <li>
                               <a href="../php/izmena_korisnika.php"><i class="far fa-user"></i> Izmeni ili obriši
                                   korisnika</a>
                           </li>
                       </ul>
                   </li>
                   <li>
                       <a href="#pageSubmenu52" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="fas fa-city"></i> Gradilišta</a>
                       <ul class="collapse list-unstyled" id="pageSubmenu52">
                           <li>
                               <a href="../php/unos_gradilista.php"><i class="fas fa-city"></i> Dodaj gradilište</a>
                           </li>
                           <li>
                               <a href="../php/izmena_gradilista_lista.php"><i class="fas fa-city"></i> Izmeni ili
                                   obriši gradilište</a>
                           </li>
                       </ul>
                   </li>

                   <li>
                       <a href="#pageSubmenu54" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="fas fa-file-invoice-dollar"></i> Troškovi gradilišta</a>
                       <ul class="collapse list-unstyled" id="pageSubmenu54">
                           <li>
                               <a href="../php/unos_tip_troska.php"><i class="fas fa-file-invoice-dollar"></i> Dodaj tip
                                   troška</a>
                           </li>
                           <li>
                               <a href="../php/izmena_tip_troska_lista.php"><i class="fas fa-file-invoice-dollar"></i>
                                   Izmeni ili obriši trošak</a>
                           </li>
                       </ul>
                   </li>

                   <li>
                       <a href="#pageSubmenu64" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="fas fa-file-invoice-dollar"></i> Troškovi ostali</a>
                       <ul class="collapse list-unstyled" id="pageSubmenu64">
                           <li>
                               <a href="../php/unos_o_tip_troska.php"><i class="fas fa-file-invoice-dollar"></i> Dodaj
                                   tip troška</a>
                           </li>
                           <li>
                               <a href="../php/izmena_o_tip_troska_lista.php"><i class="fas fa-file-invoice-dollar"></i>
                                   Izmeni ili obriši trošak</a>
                           </li>
                       </ul>
                   </li>

                   <li>
                       <a href="#pageSubmenu55" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="fas fa-male"></i> Kupci</a>
                       <ul class="collapse list-unstyled" id="pageSubmenu55">
                           <li>
                               <a href="../php/unos_kupca.php"><i class="fas fa-male"></i> Dodaj kupca</a>
                           </li>
                           <li>
                               <a href="../php/izmena_kupca_lista.php"><i class="fas fa-male"></i> Izmeni ili obriši
                                   kupca</a>
                           </li>
                       </ul>
                   </li>

                   <li>
                       <a href="#pageSubmenu53" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle <?php if ($_SESSION['tip'] != 1) echo "d-none" ?>"><i class="fas fa-car"></i> Vozila</a>
                       <ul class="collapse list-unstyled" id="pageSubmenu53">
                           <li>
                               <a href="../php/unos_vozila.php"><i class="fas fa-car"></i> Dodaj vozilo</a>
                           </li>
                           <li>
                               <a href="../php/izmena_vozila_lista.php"><i class="fas fa-car"></i> Izmeni ili obriši
                                   vozilo</a>
                           </li>
                       </ul>
                   </li>

                   <li>

                       <a href="../php/kurs.php">€ Promeni kurs </a>

                   </li>

               </ul>
           </li>
           <li class="<?php if ($_SESSION['tip'] > 2) echo "d-none" ?>">
               <a href="../php/brisanje_transakcije.php"><i class="fas fa-times-circle"></i></i> Obriši transakciju</a>
           </li>
       </ul>!-->
   </nav>