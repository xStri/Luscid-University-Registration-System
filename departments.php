<?php
require_once __DIR__ . '/student_access.php';
$search = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT d.Dept_ID, d.Dept_Name, d.Email, d.Phone_No, d.Dept_Manager, u.First_Name AS Chair_First, u.Last_Name AS Chair_Last, GROUP_CONCAT(DISTINCT m.Major_Name ORDER BY m.Major_Name SEPARATOR ", ") AS Majors FROM Department d LEFT JOIN Faculty f ON f.Faculty_ID = d.Chair_ID LEFT JOIN User u ON u.User_ID = f.Faculty_ID LEFT JOIN Major m ON m.Dept_ID = d.Dept_ID';
$params = [];
if ($search !== '') { $sql .= ' WHERE d.Dept_Name LIKE :q OR m.Major_Name LIKE :major_q'; $params = ['q' => "%$search%", 'major_q' => "%$search%"]; }
$sql .= ' GROUP BY d.Dept_ID, d.Dept_Name, d.Email, d.Phone_No, d.Dept_Manager, u.First_Name, u.Last_Name ORDER BY d.Dept_Name';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
pageStart('View Departments');
?>
<form method="get" class="portal-search"><label for="q">Search departments or majors</label><input id="q" name="q" value="<?= h($search) ?>" placeholder="e.g. Computer Science"><button type="submit">Search</button></form>
<?php if (!$departments): ?><p class="portal-notice">No matching departments found.</p><?php endif; ?>
<div class="portal-grid"><?php foreach ($departments as $dept): ?><section class="portal-card"><h2><?= h($dept['Dept_Name']) ?></h2><p><strong>Chair:</strong> <?= h(trim(($dept['Chair_First'] ?? '') . ' ' . ($dept['Chair_Last'] ?? '')) ?: 'Not listed') ?></p><p><strong>Manager:</strong> <?= h($dept['Dept_Manager'] ?: 'Not listed') ?></p><p><strong>Email:</strong> <?= h($dept['Email'] ?: 'Not listed') ?></p><p><strong>Phone:</strong> <?= h($dept['Phone_No'] ?: 'Not listed') ?></p><p><strong>Majors:</strong> <?= h($dept['Majors'] ?: 'None listed') ?></p></section><?php endforeach; ?></div>
<?php pageEnd(); ?>
