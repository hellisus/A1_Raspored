<?php
require_once '../config.php';

if (isset($_POST['tip_o_troska_id'])) {
    $tip_o_troska_id = $_POST['tip_o_troska_id'];

    if ($tip_o_troska_id == 6) {
        $podatci = new CRUD($_SESSION['godina']);
        $podatci->table = "vozila";
        $vozila = $podatci->select(['*'], [], "SELECT * FROM `vozila` ORDER BY `vozila`.`naziv` ASC");
        foreach ($vozila as $vozilo) :
?>
            <select id="vozilo" name="vozilo">
                <option value="<?= $vozilo['vozilo_id'] ?>"><?= $vozilo['naziv'] . " - " . $vozilo['registracija'] ?></option>
            <?php endforeach; ?>
            </select>
    <?php
    }
};
    ?>