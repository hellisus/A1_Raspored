<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nepodržan metod.']);
    exit;
}

if (!isset($_SESSION['Ime'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesija je istekla. Prijavite se ponovo.']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Finalized';
$currentUser = $_SESSION['Ime'];
$allowedStatuses = ['Finalized', 'Canceled'];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Neispravan ID naloga.']);
    exit;
}

if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neispravan status.']);
    exit;
}

try {
    $crud = new CRUD('srnalozi_a1_raspored');

    // 1. Fetch current data to get old state
    $checkStmt = $crud->prepare("SELECT `ID`, `Current state` FROM `lokalna_tabela` WHERE `ID` = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $currentData = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentData) {
        http_response_code(404);
        echo json_encode(['error' => 'Nalog nije pronađen.']);
        exit;
    }

    $oldState = isset($currentData['Current state']) ? $currentData['Current state'] : null;
    $newState = $status;

    // 2. Log change in history if state is different
    if ($oldState !== $newState) {
        $stmtHistory = $crud->prepare("INSERT INTO istorija_izmena (`job_id`, `user`, `field_name`, `old_value`, `new_value`) VALUES (?, ?, ?, ?, ?)");
        $stmtHistory->execute([
            $id,
            $currentUser,
            'Current state',
            $oldState,
            $newState
        ]);
    }

    // 3. Update table
    $updateStmt = $crud->prepare("UPDATE `lokalna_tabela` SET `Current state` = ? WHERE `ID` = ? LIMIT 1");
    $updateStmt->execute([$newState, $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Status naloga je uspešno izmenjen.',
        'id' => $id,
        'status' => $newState
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Greška pri ažuriranju statusa: ' . $e->getMessage()]);
}

