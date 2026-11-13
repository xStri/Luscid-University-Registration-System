<?php
require_once __DIR__ . '/student_access.php';
if (empty($_SESSION['declaration_csrf'])) $_SESSION['declaration_csrf'] = bin2hex(random_bytes(32));
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['declaration_csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(400); $error = 'Invalid request token. Please reload and try again.'; }
    else {
        $kind = (string)($_POST['kind'] ?? '');
        $action = (string)($_POST['action'] ?? '');
        $id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
        $config = ['major' => ['table' => 'Student_Major', 'column' => 'Major_ID', 'lookup' => 'Major'], 'minor' => ['table' => 'Student_Minor', 'column' => 'Minor_ID', 'lookup' => 'Minor']];
        if (!isset($config[$kind]) || !in_array($action, ['add', 'remove'], true) || !$id || $id < 1) $error = 'Invalid selection.';
        else {
            $c = $config[$kind];
            try {
                $check = $pdo->prepare("SELECT 1 FROM {$c['lookup']} WHERE {$c['column']} = :id");
                $check->execute(['id' => $id]);
                if (!$check->fetchColumn()) $error = 'The selected program does not exist.';
                else {
                    if ($action === 'add') {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO {$c['table']} (Student_ID, {$c['column']}) VALUES (:student, :id)");
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM {$c['table']} WHERE Student_ID = :student AND {$c['column']} = :id");
                    }
                    $stmt->execute(['student' => $studentId, 'id' => $id]);
                    $success = ucfirst($kind) . ($action === 'add' ? ' added.' : ' removed.');
                }
            } catch (PDOException $e) { error_log($e->getMessage()); $error = 'Unable to save your selection. Please contact support.'; }
        }
    }
}
$majors = $pdo->query('SELECT Major_ID AS id, Major_Name AS name FROM Major ORDER BY Major_Name')->fetchAll(PDO::FETCH_ASSOC);
$minors = $pdo->query('SELECT Minor_ID AS id, Minor_Name AS name FROM Minor ORDER BY Minor_Name')->fetchAll(PDO::FETCH_ASSOC);
$majorStmt = $pdo->prepare('SELECT m.Major_ID AS id, m.Major_Name AS name FROM Student_Major sm JOIN Major m ON m.Major_ID = sm.Major_ID WHERE sm.Student_ID = :id ORDER BY m.Major_Name');
$majorStmt->execute(['id' => $studentId]); $myMajors = $majorStmt->fetchAll(PDO::FETCH_ASSOC);
$minorStmt = $pdo->prepare('SELECT m.Minor_ID AS id, m.Minor_Name AS name FROM Student_Minor sm JOIN Minor m ON m.Minor_ID = sm.Minor_ID WHERE sm.Student_ID = :id ORDER BY m.Minor_Name');
$minorStmt->execute(['id' => $studentId]); $myMinors = $minorStmt->fetchAll(PDO::FETCH_ASSOC);
pageStart('Declare or Change Major / Minor');
?>
<p class="portal-notice">Demo workflow: these changes save immediately. For an official university system, require advisor or registrar approval before updating student declarations.</p>
<?php if ($error): ?><p role="alert" class="portal-error"><?= h($error) ?></p><?php endif; ?>
<?php if ($success): ?><p role="status" class="portal-success"><?= h($success) ?></p><?php endif; ?>
<?php foreach (['major' => [$majors, $myMajors], 'minor' => [$minors, $myMinors]] as $kind => $data): ?>
<section class="portal-card"><h2><?= ucfirst($kind) ?>s</h2><h3>Current <?= h($kind) ?>s</h3>
<?php if (!$data[1]): ?><p>None declared.</p><?php else: ?><ul class="portal-declarations"><?php foreach ($data[1] as $item): ?><li><span><?= h($item['name']) ?></span><form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['declaration_csrf']) ?>"><input type="hidden" name="kind" value="<?= h($kind) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>"><button type="submit" onclick="return confirm('Remove this declaration?')">Remove</button></form></li><?php endforeach; ?></ul><?php endif; ?>
<form method="post" class="portal-search"><input type="hidden" name="csrf" value="<?= h($_SESSION['declaration_csrf']) ?>"><input type="hidden" name="kind" value="<?= h($kind) ?>"><input type="hidden" name="action" value="add"><label for="<?= h($kind) ?>_id">Add a <?= h($kind) ?></label><select name="item_id" id="<?= h($kind) ?>_id" required><option value="">Select <?= h($kind) ?></option><?php foreach ($data[0] as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['name']) ?></option><?php endforeach; ?></select><button type="submit">Add <?= ucfirst($kind) ?></button></form></section>
<?php endforeach; ?>
<?php pageEnd(); ?>
