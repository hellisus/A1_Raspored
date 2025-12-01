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
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Nevažeći ID.']);
    exit;
}

try {
    $crud = new CRUD('A1_Raspored');

    $checkStmt = $crud->prepare("SELECT `ID` FROM `lokalna_tabela` WHERE `ID` = ? LIMIT 1");
    $checkStmt->execute([$id]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(['error' => 'Nalog nije pronađen u lokalnoj tabeli.']);
        exit;
    }

    $updateStmt = $crud->prepare("UPDATE `lokalna_tabela` SET `Comment` = ? WHERE `ID` = ? LIMIT 1");
    $updateStmt->execute([
        $comment !== '' ? $comment : null,
        $id
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $id,
            'comment' => $comment
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Greška pri snimanju komentara.']);
}

