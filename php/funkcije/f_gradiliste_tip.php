<?php
require_once '../config.php';

if (isset($_POST['gradiliste_id'])) {
    $gradiliste_id = $_POST['gradiliste_id'];

    $podatci = new CRUD($_SESSION['godina']);
    $podatci->table = "gradilista";
    $tipovi = $podatci->select(['*'], [], "SELECT tip_troska.id as tip_id, tip_troska.naziv as tip_naziv  FROM `gradilista` LEFT JOIN  tip_troska ON gradilista.tip = tip_troska.gradiliste_trosak WHERE gradilista.id = " . $gradiliste_id . " ORDER BY `tip_troska`.`naziv` ASC ");
    foreach ($tipovi as $tip) :
?>
        <select id="tip_troska" name="tip_troska">
            <option value="<?= $tip['tip_id'] ?>"><?= $tip['tip_naziv'] ?></option>
        <?php endforeach; ?>
        </select>
    <?php
};
    ?>