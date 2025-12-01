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
$newDate = isset($_POST['date']) ? trim($_POST['date']) : '';
$timeText = isset($_POST['time']) ? trim($_POST['time']) : '';
$assignee = isset($_POST['assignee']) ? trim($_POST['assignee']) : '';
$currentUser = $_SESSION['Ime'];

if ($id <= 0 || $newDate === '' || $timeText === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Nedostaju obavezni parametri.']);
    exit;
}

$dateTime = DateTime::createFromFormat('Y-m-d H:i', $newDate . ' ' . $timeText);
if (!$dateTime) {
    // fallback ako stignu i sekunde
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $newDate . ' ' . $timeText);
}

if (!$dateTime) {
    http_response_code(400);
    echo json_encode(['error' => 'Nevažeći format datuma ili vremena.']);
    exit;
}

$formattedDateTime = $dateTime->format('Y-m-d H:i:s');

try {
    $crud = new CRUD('srnalozi_a1_raspored');

    // 1. Fetch current data
    $checkStmt = $crud->prepare("SELECT `ID`, `Scheduled start`, `Assignees` FROM `lokalna_tabela` WHERE `ID` = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $currentData = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentData) {
        http_response_code(404);
        echo json_encode(['error' => 'Nalog nije pronađen.']);
        exit;
    }

    // 2. Compare and log changes
    $changes = [];
    $assigneeValue = $assignee !== '' ? $assignee : null;

    // Compare Assignees
    $oldAssignee = trim((string)$currentData['Assignees']);
    $newAssigneeCheck = trim((string)$assigneeValue);
    if ($oldAssignee !== $newAssigneeCheck) {
        $changes[] = [
            'field' => 'Assignees',
            'old' => $oldAssignee,
            'new' => $newAssigneeCheck
        ];
    }

    // Compare Scheduled start
    $oldStart = isset($currentData['Scheduled start']) ? date('Y-m-d H:i:s', strtotime($currentData['Scheduled start'])) : '';
    if ($oldStart !== $formattedDateTime) {
        $changes[] = [
            'field' => 'Scheduled start',
            'old' => $oldStart,
            'new' => $formattedDateTime
        ];
    }

    // 3. Insert changes into history table
    if (!empty($changes)) {
        $stmtHistory = $crud->prepare("INSERT INTO istorija_izmena (`job_id`, `user`, `field_name`, `old_value`, `new_value`) VALUES (?, ?, ?, ?, ?)");
        foreach ($changes as $change) {
            $stmtHistory->execute([
                $id,
                $currentUser,
                $change['field'],
                $change['old'],
                $change['new']
            ]);
        }
    }

    // 4. Update table
    $updateStmt = $crud->prepare("
        UPDATE `lokalna_tabela`
        SET `Scheduled start` = ?, `Assignees` = ?, `Scheduled to` = ?
        WHERE `ID` = ?
        LIMIT 1
    ");

    $updateStmt->execute([
        $formattedDateTime,
        $assigneeValue,
        $assigneeValue,
        $id
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $id,
            'scheduled_start' => $formattedDateTime,
            'assignee' => $assigneeValue
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Greška pri snimanju promene: ' . $e->getMessage()]);
}
