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
    $crud = new CRUD('A1_Raspored');

    $checkStmt = $crud->prepare("SELECT `ID` FROM `lokalna_tabela` WHERE `ID` = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $taskExists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$taskExists) {
        http_response_code(404);
        echo json_encode(['error' => 'Nalog nije pronađen.']);
        exit;
    }

    $updateStmt = $crud->prepare("
        UPDATE `lokalna_tabela`
        SET `Scheduled start` = ?, `Assignees` = ?, `Scheduled to` = ?
        WHERE `ID` = ?
        LIMIT 1
    ");

    $assigneeValue = $assignee !== '' ? $assignee : null;

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
    echo json_encode(['error' => 'Greška pri snimanju promene.']);
}

