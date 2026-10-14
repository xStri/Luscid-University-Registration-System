<?php
    session_start();

    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
        header("Location: index.html");
        exit;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = mysqli_connect(
        "localhost",  // sql server
        "root",  // sql user
        "",  // sql user password
        "registration_system",  // sql database name
    );

    mysqli_set_charset($conn, "utf8mb4");

    $userID = $_SESSION["user_id"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            u.User_ID,
            u.First_Name,
            u.Middle_Name,
            u.Last_Name,
            u.City,
            u.State,
            u.Country,
            u.Street_No,
            u.Street_Name,
            u.Zip_Code,
            u.User_Type,
            l.Email
        FROM `User` u
        LEFT JOIN Login l ON u.User_ID = l.User_ID
        WHERE u.User_ID = ?
        LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);

    $userResult = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($userResult);

    if (!$user) {
        session_unset();
        session_destroy();
        header("Location: index.html");
        exit;
    }

    $firstName = $user["First_Name"];
    $lastName = $user["Last_Name"];
    $fullName = trim($firstName . " " . $lastName);
    $email = $user["Email"] ?? "N/A";
    $userType = $user["User_Type"];

    $initials = strtoupper(
        substr($firstName, 0, 1) . substr($lastName, 0, 1)
    );

    $studentID = $userID;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT Student_Type, Student_Status
        FROM Student
        WHERE Student_ID = ?
        LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $studentID);
    mysqli_stmt_execute($stmt);

    $studentResult = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($studentResult);

    $studentType = $student["Student_Type"] ?? "N/A";
    $studentStatus = $student["Student_Status"] ?? "N/A";

    $academicLevel = "N/A";
    $studentYear = null;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT Student_Year
        FROM Undergraduate
        WHERE Student_ID = ?
        LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $studentID);
    mysqli_stmt_execute($stmt);

    $undergradResult = mysqli_stmt_get_result($stmt);
    $undergraduate = mysqli_fetch_assoc($undergradResult);

    if ($undergraduate) {
        $academicLevel = "Undergraduate";
        $studentYear = $undergraduate["Student_Year"];
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT Student_Year, Program_Type, Credits_Earned
            FROM Graduate
            WHERE Student_ID = ?
            LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "i", $studentID);
        mysqli_stmt_execute($stmt);

        $graduateResult = mysqli_stmt_get_result($stmt);
        $graduate = mysqli_fetch_assoc($graduateResult);

        if ($graduate) {
            $academicLevel = "Graduate";
            $studentYear = $graduate["Student_Year"];
        }
    }

    $majorName = "Undeclared";
    $majorID = null;
    $majorCreditsRequired = 120;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            m.Major_ID,
            m.Major_Name,
            m.Num_Credits_Required
        FROM Student_Major sm
        JOIN Major m ON sm.Major_ID = m.Major_ID
        WHERE sm.Student_ID = ?
        ORDER BY m.Major_ID
        LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $studentID);
    mysqli_stmt_execute($stmt);

    $majorResult = mysqli_stmt_get_result($stmt);
    $major = mysqli_fetch_assoc($majorResult);

    if ($major) {
        $majorID = $major["Major_ID"];
        $majorName = $major["Major_Name"];
        $majorCreditsRequired = $major["Num_Credits_Required"] ?? 120;
    }

    $minorName = "Not declared";

    $semesterID = null;
    $semesterName = "Fall";
    $semesterYear = 2026;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT Semester_ID, Semester_Name, Year
        FROM Semester
        WHERE Semester_Name = 'Fall'
        AND Year = 2026
        LIMIT 1"
    );

    mysqli_stmt_execute($stmt);
    $semesterResult = mysqli_stmt_get_result($stmt);
    $currentSemester = mysqli_fetch_assoc($semesterResult);

    if ($currentSemester) {
        $semesterID = $currentSemester["Semester_ID"];
        $semesterName = $currentSemester["Semester_Name"];
        $semesterYear = $currentSemester["Year"];
    }

    $semesterLabel = $semesterName . " " . $semesterYear;

    $currentCourses = [];
    $currentCredits = 0;

    if ($semesterID !== null) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                cs.CRN,
                cs.Section_No,
                c.Course_ID,
                c.Course_Name,
                c.Course_Credits,
                cs.Avail_Seats,
                r.Room_No,
                b.Bldg_Name,
                CONCAT(fu.First_Name, ' ', fu.Last_Name) AS Instructor,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        ts.Day,
                        ' ',
                        DATE_FORMAT(ts.Start_Time, '%h:%i %p'),
                        ' - ',
                        DATE_FORMAT(ts.End_Time, '%h:%i %p')
                    )
                    ORDER BY ts.Time_Slot_ID
                    SEPARATOR ', '
                ) AS Schedule
            FROM Enrollment e
            JOIN Course_Section cs ON e.CRN = cs.CRN
            JOIN Course c ON cs.Course_ID = c.Course_ID
            JOIN Room r ON cs.Room_ID = r.Room_ID
            JOIN Building b ON r.Bldg_ID = b.Bldg_ID
            JOIN Faculty f ON cs.Instructor_ID = f.Faculty_ID
            JOIN `User` fu ON f.Faculty_ID = fu.User_ID
            LEFT JOIN Section_Time_Slot sts ON cs.CRN = sts.CRN
            LEFT JOIN Time_Slot ts ON sts.Time_Slot_ID = ts.Time_Slot_ID
            WHERE e.Student_ID = ?
            AND cs.Semester_ID = ?
            GROUP BY
                cs.CRN,
                cs.Section_No,
                c.Course_ID,
                c.Course_Name,
                c.Course_Credits,
                cs.Avail_Seats,
                r.Room_No,
                b.Bldg_Name,
                fu.First_Name,
                fu.Last_Name
            ORDER BY c.Course_ID"
        );

        mysqli_stmt_bind_param($stmt, "ii", $studentID, $semesterID);
        mysqli_stmt_execute($stmt);

        $scheduleResult = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($scheduleResult)) {
            $currentCourses[] = $row;
            $currentCredits += (int) ($row["Course_Credits"] ?? 0);
        }
    }

    $completedCredits = 0;
    $gpaPoints = 0;
    $gradedCredits = 0;
    $grades = [];

    $gradePoints = [
        "A+" => 4.0,
        "A"  => 4.0,
        "A-" => 3.7,
        "B+" => 3.3,
        "B"  => 3.0,
        "B-" => 2.7,
        "C+" => 2.3,
        "C"  => 2.0,
        "C-" => 1.7,
        "D+" => 1.3,
        "D"  => 1.0,
        "D-" => 0.7,
        "F"  => 0.0
    ];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            e.CRN,
            e.Grade,
            c.Course_ID,
            c.Course_Name,
            c.Course_Credits,
            s.Semester_Name,
            s.Year
        FROM Enrollment e
        JOIN Course_Section cs ON e.CRN = cs.CRN
        JOIN Course c ON cs.Course_ID = c.Course_ID
        JOIN Semester s ON cs.Semester_ID = s.Semester_ID
        WHERE e.Student_ID = ?
        ORDER BY s.Year DESC, c.Course_ID"
    );

    mysqli_stmt_bind_param($stmt, "i", $studentID);
    mysqli_stmt_execute($stmt);

    $gradeResult = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($gradeResult)) {
        $grades[] = $row;

        if (
            $row["Grade"] !== null &&
            $row["Grade"] !== "" &&
            isset($gradePoints[strtoupper($row["Grade"])])
        ) {
            $credits = (int) $row["Course_Credits"];
            $grade = strtoupper($row["Grade"]);

            $completedCredits += $credits;
            $gradedCredits += $credits;
            $gpaPoints += $gradePoints[$grade] * $credits;
        }
    }

    $gpa = $gradedCredits > 0
        ? number_format($gpaPoints / $gradedCredits, 2)
        : "N/A";

    if ($academicLevel === "Graduate" && isset($graduate["Credits_Earned"])) {
        $completedCredits = max(
            $completedCredits,
            (int) $graduate["Credits_Earned"]
        );
    }

    $degreeProgress = $majorCreditsRequired > 0
        ? min(100, ($completedCredits / $majorCreditsRequired) * 100)
        : 0;

    $degreeProgressFormatted = number_format($degreeProgress, 1);

    $requiredCourses = [];
    $completedMajorCourses = 0;

    if ($majorID !== null) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                c.Course_ID,
                c.Course_Name,
                c.Course_Credits
            FROM Major_Requirement mr
            JOIN Course c ON mr.Course_ID = c.Course_ID
            WHERE mr.Major_ID = ?
            ORDER BY c.Course_ID"
        );

        mysqli_stmt_bind_param($stmt, "i", $majorID);
        mysqli_stmt_execute($stmt);

        $requirementResult = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($requirementResult)) {
            $requiredCourses[] = $row;
        }

        if (count($requiredCourses) > 0) {
            $completedCourseIDs = [];

            $stmt = mysqli_prepare(
                $conn,
                "SELECT DISTINCT cs.Course_ID
                FROM Enrollment e
                JOIN Course_Section cs ON e.CRN = cs.CRN
                WHERE e.Student_ID = ?
                AND e.Grade IS NOT NULL
                AND e.Grade NOT IN ('F', 'D-', 'D', 'D+')"
            );

            mysqli_stmt_bind_param($stmt, "i", $studentID);
            mysqli_stmt_execute($stmt);

            $completedResult = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($completedResult)) {
                $completedCourseIDs[] = (int) $row["Course_ID"];
            }

            foreach ($requiredCourses as $requiredCourse) {
                if (in_array((int) $requiredCourse["Course_ID"], $completedCourseIDs, true)) {
                    $completedMajorCourses++;
                }
            }
        }
    }

    $remainingMajorCourses = max(
        0,
        count($requiredCourses) - $completedMajorCourses
    );

    $advisors = [];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            f.Faculty_ID,
            CONCAT(u.First_Name, ' ', u.Last_Name) AS Advisor_Name,
            u.First_Name,
            u.Last_Name,
            l.Email,
            d.Dept_Name
        FROM Advisor a
        JOIN Faculty f ON a.Faculty_ID = f.Faculty_ID
        JOIN `User` u ON f.Faculty_ID = u.User_ID
        LEFT JOIN Login l ON u.User_ID = l.User_ID
        LEFT JOIN Faculty_Department fd ON f.Faculty_ID = fd.Faculty_ID
        LEFT JOIN Department d ON fd.Dept_ID = d.Dept_ID
        WHERE a.Student_ID = ?
        ORDER BY f.Faculty_ID"
    );

    mysqli_stmt_bind_param($stmt, "i", $studentID);
    mysqli_stmt_execute($stmt);

    $advisorResult = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($advisorResult)) {
        $advisors[] = $row;
    }

    $primaryAdvisor = $advisors[0] ?? null;

    $studentAddress = trim(
        ($user["Street_No"] ?? "") . " " .
        ($user["Street_Name"] ?? "")
    );

    $location = trim(
        ($user["City"] ?? "") . ", " .
        ($user["State"] ?? "") . " " .
        ($user["Zip_Code"] ?? "")
    );
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Student Dashboard - College Portal</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>

        <div class="portal">

        <aside class="sidebar">

        <div class="sidebar-logo">
            <h2>COLLEGE</h2>
            <p>Student Portal</p>
        </div>

        <div class="student-mini-profile">
            <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
            <div>
                <strong><?= htmlspecialchars($fullName) ?></strong>
                <span><?= htmlspecialchars($userType) ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <a href="#" class="nav-item active">
                <span>▣</span>
                Dashboard
            </a>

            <a href="#profile" class="nav-item">
                <span>👤</span>
                My Profile
            </a>

            <div class="nav-section">ACADEMIC</div>

            <a href="#schedule" class="nav-item">
                <span>📅</span>
                Schedule
            </a>

            <a href="#grades" class="nav-item">
                <span>📊</span>
                Grades
            </a>

            <a href="#transcript" class="nav-item">
                <span>📄</span>
                Unofficial Transcript
            </a>

            <a href="#degree-audit" class="nav-item">
                <span>✓</span>
                Degree Audit
            </a>

            <div class="nav-section">REGISTRATION</div>

            <a href="#add-course" class="nav-item">
                <span>＋</span>
                Add Course
            </a>

            <a href="#master-schedule" class="nav-item">
                <span>🔎</span>
                Master Schedule
            </a>

            <a href="#prerequisites" class="nav-item">
                <span>📚</span>
                Course Prerequisites
            </a>

            <div class="nav-section">PROGRAMS</div>

            <a href="#programs" class="nav-item">
                <span>🎓</span>
                Academic Program
            </a>

            <a href="#major-minor" class="nav-item">
                <span>★</span>
                Major / Minor
            </a>

            <div class="nav-section">ADVISING</div>

            <a href="#advisors" class="nav-item">
                <span>👥</span>
                Find an Advisor
            </a>

        </nav>

        <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
        ⇥ &nbsp; Logout
        </a>
        </div>

        </aside>

        <main class="main-content">

        <header class="top-header">

        <div>
        <p class="breadcrumb">Student Portal / Dashboard</p>
        <h1>Welcome back, <?= htmlspecialchars($firstName) ?>!</h1>
        </div>

        <div class="header-right">

        <button class="notification-button">
        🔔 <span class="notification-dot"></span>
        </button>

        <div class="header-profile">

        <div class="profile-circle small">
        <?= htmlspecialchars($initials) ?>
        </div>

        <div>
        <strong><?= htmlspecialchars($fullName) ?></strong>
        <span><?= htmlspecialchars($userType) ?></span>
        </div>

        </div>

        </div>

        </header>

        <section class="dashboard-cards">

        <div class="dashboard-card">
        <div class="card-icon">GPA</div>
        <div>
        <p>Current GPA</p>
        <h2><?= htmlspecialchars($gpa) ?></h2>
        </div>
        </div>

        <div class="dashboard-card">
        <div class="card-icon">CR</div>
        <div>
        <p>Credits Completed</p>
        <h2><?= htmlspecialchars($completedCredits) ?></h2>
        </div>
        </div>

        <div class="dashboard-card">
        <div class="card-icon">CL</div>
        <div>
        <p>Current Credits</p>
        <h2><?= htmlspecialchars($currentCredits) ?></h2>
        </div>
        </div>

        <div class="dashboard-card">
        <div class="card-icon">ST</div>
        <div>
        <p>Student Status</p>
        <h2><?= htmlspecialchars($studentStatus) ?></h2>
        </div>
        </div>

        </section>

        <section id="profile" class="portal-section">

        <div class="section-header">
        <div>
        <p class="section-label">STUDENT INFORMATION</p>
        <h2>My Profile</h2>
        </div>

        <button class="secondary-button">Edit Profile</button>
        </div>

        <div class="profile-grid">

        <div>
        <label>Full Name</label>
        <p><?= htmlspecialchars($fullName) ?></p>
        </div>

        <div>
        <label>Student ID</label>
        <p><?= htmlspecialchars($studentID) ?></p>
        </div>

        <div>
        <label>College Email</label>
        <p><?= htmlspecialchars($email) ?></p>
        </div>

        <div>
        <label>Academic Level</label>
        <p><?= htmlspecialchars($academicLevel) ?></p>
        </div>

        <div>
        <label>Major</label>
        <p><?= htmlspecialchars($majorName) ?></p>
        </div>

        <div>
        <label>Minor</label>
        <p><?= htmlspecialchars($minorName) ?></p>
        </div>

        </div>

        </section>

        <section id="schedule" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">ACADEMIC</p>
        <h2>Current Schedule</h2>
        </div>

        <select>
        <option><?= htmlspecialchars($semesterLabel) ?></option>
        </select>

        </div>

        <div class="table-container">

        <table>

        <thead>
        <tr>
        <th>Course</th>
        <th>Course Name</th>
        <th>Days</th>
        <th>Time</th>
        <th>Room</th>
        </tr>
        </thead>

        <tbody>

        <?php if (count($currentCourses) > 0): ?>

        <?php foreach ($currentCourses as $course): ?>

        <?php
        $schedule = $course["Schedule"] ?? "TBA";
        $room = $course["Bldg_Name"] . " " . $course["Room_No"];
        ?>

        <tr>

        <td>
        <strong>Course <?= htmlspecialchars($course["Course_ID"]) ?></strong>
        </td>

        <td>
        <?= htmlspecialchars($course["Course_Name"]) ?>
        </td>

        <td>
        <?= htmlspecialchars($schedule) ?>
        </td>

        <td>
        <?= htmlspecialchars($schedule) ?>
        </td>

        <td>
        <?= htmlspecialchars($room) ?>
        </td>

        </tr>

        <?php endforeach; ?>

        <?php else: ?>

        <tr>
        <td colspan="5">
        No courses are currently registered for <?= htmlspecialchars($semesterLabel) ?>.
        </td>
        </tr>

        <?php endif; ?>

        </tbody>

        </table>

        </div>

        </section>

        <section id="grades" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">ACADEMIC</p>
        <h2>Semester Grades</h2>
        </div>

        <select>
        <option><?= htmlspecialchars($semesterLabel) ?></option>
        </select>

        </div>

        <div class="table-container">

        <table>

        <thead>
        <tr>
        <th>Course</th>
        <th>Course Name</th>
        <th>Credits</th>
        <th>Grade</th>
        </tr>
        </thead>

        <tbody>

        <?php
        $semesterGrades = array_filter(
            $grades,
            function ($grade) use ($semesterName, $semesterYear) {
                return $grade["Semester_Name"] === $semesterName &&
                    (int) $grade["Year"] === (int) $semesterYear;
            }
        );
        ?>

        <?php if (count($semesterGrades) > 0): ?>

        <?php foreach ($semesterGrades as $grade): ?>

        <tr>

        <td>
        Course <?= htmlspecialchars($grade["Course_ID"]) ?>
        </td>

        <td>
        <?= htmlspecialchars($grade["Course_Name"]) ?>
        </td>

        <td>
        <?= htmlspecialchars($grade["Course_Credits"]) ?>
        </td>

        <td>
        <span class="grade">
        <?= htmlspecialchars($grade["Grade"] ?? "In Progress") ?>
        </span>
        </td>

        </tr>

        <?php endforeach; ?>

        <?php else: ?>

        <tr>
        <td colspan="4">
        No grades have been posted for <?= htmlspecialchars($semesterLabel) ?>.
        </td>
        </tr>

        <?php endif; ?>

        </tbody>

        </table>

        </div>

        </section>

        <section id="degree-audit" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">ACADEMIC TOOLS</p>
        <h2>Degree Audit</h2>
        </div>

        </div>

        <div class="audit-summary">

        <div>
        <h3><?= htmlspecialchars($completedCredits) ?> / <?= htmlspecialchars($majorCreditsRequired) ?></h3>
        <p>Credits Completed</p>
        </div>

        <div class="progress-container">

        <div class="progress-bar">
        <div
        class="progress-fill"
        style="width: <?= htmlspecialchars($degreeProgressFormatted) ?>%;"
        ></div>
        </div>

        <p><?= htmlspecialchars($degreeProgressFormatted) ?>% of degree completed</p>

        </div>

        </div>

        <div class="requirement">

        <span class="status-complete">✓</span>

        <div>
        <strong>Major Requirements</strong>

        <p>
        <?= htmlspecialchars($completedMajorCourses) ?>
        of
        <?= htmlspecialchars(count($requiredCourses)) ?>
        required courses completed
        </p>
        </div>

        </div>

        <div class="requirement">

        <span class="status-progress">●</span>

        <div>
        <strong>Degree Credits</strong>

        <p>
        <?= htmlspecialchars(max(0, $majorCreditsRequired - $completedCredits)) ?>
        credits remaining
        </p>
        </div>

        </div>

        <div class="requirement">

        <span class="status-pending">○</span>

        <div>

        <strong>Remaining Requirements</strong>

        <p>
        <?= htmlspecialchars($remainingMajorCourses) ?>
        major courses remaining
        </p>

        </div>

        </div>

        </section>

        <section id="programs" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">PROGRAMS</p>
        <h2>Academic Program</h2>
        </div>

        </div>

        <div class="program-card">

        <h3><?= htmlspecialchars($majorName) ?></h3>

        <p>
        <?php if ($majorID !== null): ?>
        Department requirements from your declared major
        <?php else: ?>
        No major currently declared
        <?php endif; ?>
        </p>

        <h4>Required Courses</h4>

        <ul>

        <?php if (count($requiredCourses) > 0): ?>

        <?php foreach ($requiredCourses as $course): ?>

        <li>
        Course <?= htmlspecialchars($course["Course_ID"]) ?>
        -
        <?= htmlspecialchars($course["Course_Name"]) ?>
        </li>

        <?php endforeach; ?>

        <?php else: ?>

        <li>No major requirements available.</li>

        <?php endif; ?>

        </ul>

        </div>

        </section>

        <section id="major-minor" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">PROGRAMS</p>
        <h2>Major / Minor</h2>
        </div>

        </div>

        <div class="program-selection">

        <div class="current-program">

        <p>Current Major</p>

        <h3><?= htmlspecialchars($majorName) ?></h3>

        </div>

        <div class="current-program">

        <p>Current Minor</p>

        <h3><?= htmlspecialchars($minorName) ?></h3>

        </div>

        </div>

        <button class="primary-button">
        Declare / Change Major
        </button>

        <button class="secondary-button">
        Declare / Change Minor
        </button>

        </section>

        <section id="add-course" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">REGISTRATION</p>
        <h2>Add a Course-Section</h2>
        </div>

        </div>

        <div class="registration-notice">

        <strong>Registration is currently open.</strong>

        <p>
        You may add courses while the registration window is open.
        </p>

        </div>

        <div class="course-search">

        <input
        type="text"
        placeholder="Search by course code or name..."
        >

        <button class="primary-button">
        Search Courses
        </button>

        </div>

        <?php
        $availableCourses = [];

        if ($semesterID !== null) {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT DISTINCT
                    c.Course_ID,
                    c.Course_Name,
                    c.Course_Credits
                FROM Course_Section cs
                JOIN Course c ON cs.Course_ID = c.Course_ID
                WHERE cs.Semester_ID = ?
                AND cs.CRN NOT IN (
                    SELECT e.CRN
                    FROM Enrollment e
                    WHERE e.Student_ID = ?
                )
                ORDER BY c.Course_ID
                LIMIT 5"
            );

            mysqli_stmt_bind_param($stmt, "ii", $semesterID, $studentID);
            mysqli_stmt_execute($stmt);

            $availableResult = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($availableResult)) {
                $availableCourses[] = $row;
            }
        }
        ?>

        <?php foreach ($availableCourses as $course): ?>

        <div class="available-course">

        <div>

        <strong>
        Course <?= htmlspecialchars($course["Course_ID"]) ?>
        </strong>

        <p>
        <?= htmlspecialchars($course["Course_Name"]) ?>
        </p>

        </div>

        <button class="primary-button">
        View Sections
        </button>

        </div>

        <?php endforeach; ?>

        </section>

        <section id="master-schedule" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">REGISTRATION</p>
        <h2>Master Schedule</h2>
        </div>

        <select>
        <option><?= htmlspecialchars($semesterLabel) ?></option>
        <option>Spring 2027</option>
        </select>

        </div>

        <div class="filter-row">

        <select>
        <option>All Departments</option>
        </select>

        <input
        type="text"
        placeholder="Course number"
        >

        <input
        type="text"
        placeholder="Faculty"
        >

        <button class="primary-button">
        Search
        </button>

        </div>

        <p class="helper-text">
        Filter by department, course number, faculty, time, building, or available seats.
        </p>

        </section>

        <section id="prerequisites" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">ACADEMIC TOOLS</p>
        <h2>Course Prerequisites</h2>
        </div>

        </div>

        <div class="course-search">

        <input
        type="text"
        placeholder="Enter course ID..."
        >

        <button class="primary-button">
        Search
        </button>

        </div>

        <div class="prerequisite-result">

        <h3>Course Prerequisites</h3>

        <p>
        Prerequisite information is stored in the database for each course.
        </p>

        <p>
        Use the course ID above to search for a specific course.
        </p>

        </div>

        </section>

        <section id="transcript" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">ACADEMIC RECORDS</p>
        <h2>Unofficial Transcript</h2>
        </div>

        <button class="secondary-button">
        Print Transcript
        </button>

        </div>

        <div class="transcript-summary">

        <p>
        <strong>Student:</strong>
        <?= htmlspecialchars($fullName) ?>
        </p>

        <p>
        <strong>Student ID:</strong>
        <?= htmlspecialchars($studentID) ?>
        </p>

        <p>
        <strong>Cumulative GPA:</strong>
        <?= htmlspecialchars($gpa) ?>
        </p>

        <p>
        <strong>Total Credits:</strong>
        <?= htmlspecialchars($completedCredits) ?>
        </p>

        </div>

        </section>

        <section id="advisors" class="portal-section">

        <div class="section-header">

        <div>
        <p class="section-label">ADVISING</p>
        <h2>Find an Advisor</h2>
        </div>

        </div>

        <div class="filter-row">

        <select>
        <option>Department</option>
        </select>

        <input
        type="text"
        placeholder="Advisor name"
        >

        <button class="primary-button">
        Search
        </button>

        </div>

        <?php if ($primaryAdvisor): ?>

        <div class="advisor-card">

        <div class="advisor-avatar">
        <?= htmlspecialchars(
            strtoupper(
                substr($primaryAdvisor["First_Name"], 0, 1) .
                substr($primaryAdvisor["Last_Name"], 0, 1)
            )
        ) ?>
        </div>

        <div>

        <h3>
        <?= htmlspecialchars($primaryAdvisor["Advisor_Name"]) ?>
        </h3>

        <p>
        <?= htmlspecialchars($primaryAdvisor["Dept_Name"] ?? "Faculty") ?>
        </p>

        <p>
        Email:
        <?= htmlspecialchars($primaryAdvisor["Email"] ?? "N/A") ?>
        </p>

        </div>

        </div>

        <?php else: ?>

        <div class="advisor-card">

        <div class="advisor-avatar">
        --
        </div>

        <div>

        <h3>No Advisor Assigned</h3>

        <p>
        You currently do not have an advisor assigned in the system.
        </p>

        </div>

        </div>

        <?php endif; ?>

        </section>

        <footer>

        <p>© 2026 College Portal</p>

        <p>Student Information System</p>

        </footer>

        </main>

        </div>

        <script>
            // Redirect to ADMIN DASHBOARD if this user is an administrator
            if ("<?= htmlspecialchars($userType) ?>" == "Administrator") {
                window.location.href = "admin-dashboard.php"
            }
        </script>

    </body>
</html>

<?php
mysqli_close($conn);
?>
