<?php
require '../config.php';

if (!isset($_SESSION['Ime'])) {
    echo json_encode(['error' => 'Niste prijavljeni.']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['error' => 'Invalid ID.']);
    exit;
}

$crud = new CRUD('srnalozi_a1_raspored');

try {
    $stmt = $crud->prepare("SELECT * FROM istorija_izmena WHERE job_id = ? ORDER BY change_date DESC");
    $stmt->execute([$id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and labels for display
    $formattedHistory = [];
    foreach ($history as $row) {
        $date = new DateTime($row['change_date']);
        $row['change_date_formatted'] = $date->format('d.m.Y H:i');
        
        // Translate field names if needed
        switch ($row['field_name']) {
            case 'Comment': $row['field_label'] = 'Komentar'; break;
            case 'Assignees': $row['field_label'] = 'Tim'; break;
            case 'Scheduled start': $row['field_label'] = 'Termin'; break;
            default: $row['field_label'] = $row['field_name'];
        }
        
        $formattedHistory[] = $row;
    }

    echo json_encode($formattedHistory);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

