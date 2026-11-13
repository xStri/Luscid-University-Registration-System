<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../db.php';
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: ../index.html'); exit;
}
$studentId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT s.Student_ID, u.First_Name, u.Last_Name FROM Student s JOIN User u ON u.User_ID = s.Student_ID WHERE s.Student_ID = :id');
$stmt->execute(['id' => $studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) { http_response_code(403); exit('Student access required.'); }
function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function pageStart(string $title): void { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= h($title) ?> | Luscid University</title><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="portal-additions.css"></head><body><header class="banner"><div class="logo">Luscid University Portal</div><nav><a href="student.php">Dashboard</a> <a href="../account/logout.php">Log Out</a></nav></header><div class="dashboard-container"><aside class="sidebar"><h2>Student Menu</h2><nav><ul><li><a href="student.php">Dashboard</a></li><li><a href="holds.php">View Holds</a></li><li><a href="departments.php">View Departments</a></li><li><a href="declarations.php">Declare / Change Major or Minor</a></li></ul></nav></aside><main class="main-content"><h1 class="welcome-head"><?= h($title) ?></h1>
<?php }
function pageEnd(): void { echo '</main></div></body></html>'; }
