<?php
require_once __DIR__ . '/student_access.php';
$stmt = $pdo->prepare('SELECT ht.Hold_Type, ht.Hold_Description, sh.Date_Given FROM Student_Hold sh JOIN Hold_Type ht ON ht.Hold_ID = sh.Hold_ID WHERE sh.Student_ID = :student ORDER BY sh.Date_Given DESC, ht.Hold_Type');
$stmt->execute(['student' => $studentId]);
$holds = $stmt->fetchAll(PDO::FETCH_ASSOC);
pageStart('View Holds');
?>
<p>Review holds associated with your student record. Contact the appropriate university office to resolve a hold.</p>
<?php if (!$holds): ?><p class="portal-notice">No holds are currently recorded for your account.</p>
<?php else: ?><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Hold Type</th><th>Description</th><th>Date Given</th></tr></thead><tbody>
<?php foreach ($holds as $hold): ?><tr><td><?= h($hold['Hold_Type']) ?></td><td><?= h($hold['Hold_Description']) ?></td><td><?= h($hold['Date_Given'] ?: 'Not specified') ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<?php pageEnd(); ?>
