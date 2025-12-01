<?php
require '../config.php';

if (!isset($_SESSION['Ime'])) {
    echo json_encode(['error' => 'Niste prijavljeni.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$scheduledTo = isset($_POST['scheduled_to']) ? trim($_POST['scheduled_to']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$time = isset($_POST['time']) ? trim($_POST['time']) : '';
$currentUser = $_SESSION['Ime']; // Pretpostavljamo da je ovo korisničko ime

if (!$id) {
    echo json_encode(['error' => 'Invalid ID.']);
    exit;
}

// Validate date and time
if ($date && $time) {
    $scheduledStart = $date . ' ' . $time . ':00';
    // Validate format
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $scheduledStart);
    if (!$d || $d->format('Y-m-d H:i:s') !== $scheduledStart) {
        echo json_encode(['error' => 'Neispravan format datuma ili vremena.']);
        exit;
    }
} else {
    echo json_encode(['error' => 'Datum i vreme su obavezni.']);
    exit;
}

$crud = new CRUD('srnalozi_a1_raspored');

try {
    // 1. Fetch current data from lokalna_tabela
    $stmt = $crud->prepare("SELECT `Comment`, `Assignees`, `Scheduled start` FROM lokalna_tabela WHERE `ID` = ? LIMIT 1");
    $stmt->execute([$id]);
    $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentData) {
        echo json_encode(['error' => 'Nalog nije pronađen.']);
        exit;
    }

    // 2. Compare and log changes
    $changes = [];
    
    // Compare Comment
    $oldComment = trim((string)$currentData['Comment']);
    if ($oldComment !== $comment) {
        $changes[] = [
            'field' => 'Comment',
            'old' => $oldComment,
            'new' => $comment
        ];
    }

    // Compare Assignees
    $oldAssignee = trim((string)$currentData['Assignees']);
    if ($oldAssignee !== $scheduledTo) {
        $changes[] = [
            'field' => 'Assignees',
            'old' => $oldAssignee,
            'new' => $scheduledTo
        ];
    }

    // Compare Scheduled start
    $oldStart = isset($currentData['Scheduled start']) ? date('Y-m-d H:i:s', strtotime($currentData['Scheduled start'])) : '';
    $newStart = date('Y-m-d H:i:s', strtotime($scheduledStart));
    
    if ($oldStart !== $newStart) {
        $changes[] = [
            'field' => 'Scheduled start',
            'old' => $oldStart,
            'new' => $newStart
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

    // 4. Update lokalna_tabela
    $stmtUpdate = $crud->prepare("UPDATE lokalna_tabela SET `Comment` = ?, `Assignees` = ?, `Scheduled start` = ? WHERE `ID` = ?");
    $stmtUpdate->execute([$comment, $scheduledTo, $scheduledStart, $id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
