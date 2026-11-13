<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.html");
    exit;
}

$userID = $_SESSION["user_id"];

// Fetch Student User Details
$userStmt = $pdo->prepare("SELECT First_Name, Last_Name FROM User WHERE User_ID = :user_id");
$userStmt->execute([':user_id' => $userID]);
$user = $userStmt->fetch();

if (!$user) {
    echo "User profile not found.";
    exit;
}

// Helper function to query courses based on semester timeline
function getCoursesBySemesterTimeline($pdo, $studentID, $timelineCondition) {
    $sql = "SELECT 
                cs.CRN,
                cs.Section_No,
                c.Course_Name,
                c.Course_Credits,
                iu.First_Name AS Inst_First,
                iu.Last_Name AS Inst_Last,
                r.Room_No,
                r.Room_Type,
                s.Semester_Name,
                s.Year
            FROM Enrollment e
            JOIN Course_Section cs ON e.CRN = cs.CRN
            JOIN Course c ON cs.Course_ID = c.Course_ID
            JOIN User iu ON cs.Instructor_ID = iu.User_ID
            JOIN Room r ON cs.Lecture_ID = r.Room_ID
            JOIN Semester s ON cs.Semester_ID = s.Semester_ID
            WHERE e.Student_ID = :student_id AND $timelineCondition
            ORDER BY s.Start_Date DESC, c.Course_Name ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => $studentID]);
    return $stmt->fetchAll();
}

$currentCourses = getCoursesBySemesterTimeline($pdo, $userID, "CURRENT_DATE() BETWEEN s.Start_Date AND s.End_Date");
$pastCourses    = getCoursesBySemesterTimeline($pdo, $userID, "s.End_Date < CURRENT_DATE()");
$nextCourses    = getCoursesBySemesterTimeline($pdo, $userID, "s.Start_Date > CURRENT_DATE()");

function renderCourseTable($courses) {
    if (empty($courses)) {
        echo "<p>No course registrations found for this period.</p>";
        return;
    }
    ?>
    <table>
        <thead>
            <tr>
                <th>CRN</th>
                <th>Section No.</th>
                <th>Course Name</th>
                <th>Course Credits</th>
                <th>Instructor</th>
                <th>Room No.</th>
                <th>Course Type</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?= htmlspecialchars($course['CRN']) ?></td>
                    <td><?= htmlspecialchars($course['Section_No']) ?></td>
                    <td><?= htmlspecialchars($course['Course_Name']) ?></td>
                    <td><?= htmlspecialchars($course['Course_Credits']) ?></td>
                    <td><?= htmlspecialchars($course['Inst_Last'] . ', ' . $course['Inst_First']) ?></td>
                    <td><?= htmlspecialchars($course['Room_No']) ?></td>
                    <td><?= htmlspecialchars($course['Room_Type']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header class="banner">
        <div class="logo">Luscid University Portal</div>
        <nav class="menu">
            <a href="../account/logout.php">Log Out</a>
        </nav>
    </header>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <h2>Student Menu</h2>
            <nav>
                <ul> 
                    <li><a href="holds.php">View Holds</a></li>
                    <li><a href="department.php">View Departments</a></li>
                    <li><a href="declarations.php">Declare or Change Major/Minor</a></li>
                    <li><a href="account_settings.php">Account Settings</a></li>
                    <li><a href="grades.php">View Unofficial Transcript</a></li>
                    <li><a href="degree_audit.php">View Degree Audit</a></li>
                    <li><a href="courses.php">Register for Classes</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <h1 class="welcome-header">Welcome <?= htmlspecialchars($user['First_Name']) ?>, <?= htmlspecialchars($user['Last_Name']) ?></h1>

            <h2 class="section-title">Current Schedule</h2>
            <?php renderCourseTable($currentCourses); ?>

            <h2 class="section-title">Past Courses</h2>
            <?php renderCourseTable($pastCourses); ?>

            <h2 class="section-title">Courses for Next Semester</h2>
            <?php renderCourseTable($nextCourses); ?>
        </main>
    </div>
</body>
</html>
