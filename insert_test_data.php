<?php
    // Database configuration
    $host = '127.0.0.1';
    $db   = 'registration_system';$user = 'root';
    $pass = '';$charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn,$user, $pass,$options);
        echo "Connected successfully to database '$db'.\nStarting data seeding...\n\n";

        // Disable foreign key checks temporarily to allow clean inserts if needed
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Clear existing data (optional - comment out if you want to append)
        $tables = [
            'Faculty_History', 'Student_History', 'Student_Major', 'Faculty_Department', 
            'Advisor', 'Enrollment', 'Course_Section', 'Major_Requirement', 'Prerequisite', 
            'Course', 'Student_Hold', 'Major', 'Department', 'Graduate', 'Undergraduate', 
            'Student', 'Faculty', 'Admin', 'Login', 'User', 'Lecture', 'Lab', 'Office', 
            'Room', 'Building', 'Semester'
        ];
        foreach ($tables as $table) {$pdo->exec("TRUNCATE TABLE `$table`;");
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // --- HELPER ARRAYS FOR RANDOMIZATION ---
        $firstNames = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth', 'David', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas'];$cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose'];
        $states = ['NY', 'CA', 'IL', 'TX', 'AZ', 'PA', 'TX', 'CA', 'TX', 'CA'];$streets = ['Main St', 'First Ave', 'Park Rd', 'Broadway', 'Oak Lane', 'Pine St', 'Cedar Dr', 'Maple Ave'];

        // 1. INSERT BUILDINGS
        echo "Inserting Buildings...\n";
        $buildingStmt =$pdo->prepare("INSERT INTO Building (Bldg_Name, Bldg_Usage) VALUES (?, ?)");
        $buildings = [ ['Science Hall', 'STEM Classes and Labs'], ['Engineering Complex', 'Computer Science and Engineering'], ['Liberal Arts Building', 'Humanities and Social Sciences'], ['Administration Hall', 'University Offices and Admissions'] ];$bldgIds = [];
        foreach ($buildings as$bldg) {
            $buildingStmt->execute($bldg);
            $bldgIds[] =$pdo->lastInsertId();
        }

        // 2. INSERT ROOMS & SUBTYPES (Office, Lecture, Lab)
        echo "Inserting Rooms, Offices, Lectures, and Labs...\n";
        $roomStmt =$pdo->prepare("INSERT INTO Room (Bldg_ID, Room_No, Room_Type) VALUES (?, ?, ?)");
        $officeStmt =$pdo->prepare("INSERT INTO Office (Office_ID, Num_Desks) VALUES (?, ?)");
        $lectureStmt =$pdo->prepare("INSERT INTO Lecture (Lecture_ID, Avail_Seats) VALUES (?, ?)");
        $labStmt =$pdo->prepare("INSERT INTO Lab (Lab_ID, Num_Workstations) VALUES (?, ?)");

        $officeIds = [];
        $lectureIds = [];$labIds = [];

        foreach ($bldgIds as$bldgId) {
            // Create 2 offices, 2 lecture halls, 2 labs per building
            for ($i = 1; $i <= 6; $i++) {$roomNo = chr(64 + $bldgId) . '10' .$i;
                $type = ($i <= 2) ? 'Office' : (($i <= 4) ? 'Lecture' : 'Lab');
                
                $roomStmt->execute([$bldgId, $roomNo,$type]);
                $roomId =$pdo->lastInsertId();

                if ($type === 'Office') {
                    $officeStmt->execute([$roomId, rand(1, 3)]);
                    $officeIds[] =$roomId;
                } elseif ($type === 'Lecture') {
                    $lectureStmt->execute([$roomId, rand(30, 100)]);
                    $lectureIds[] =$roomId;
                } else {
                    $labStmt->execute([$roomId, rand(15, 30)]);
                    $labIds[] =$roomId;
                }
            }
        }

        // 3. INSERT USERS, LOGINS, ADMINS, FACULTY, STUDENTS
        echo "Inserting Users, Admins, Faculty, and Students...\n";
        $userStmt =$pdo->prepare("INSERT INTO User (First_Name, Middle_Name, Last_Name, City, State, Country, Street_No, Street_Name, Zip_Code, User_Type) VALUES (?, ?, ?, ?, ?, 'USA', ?, ?, ?, ?)");
        $loginStmt =$pdo->prepare("INSERT INTO Login (User_ID, Email, Password) VALUES (?, ?, ?)");
        $adminStmt =$pdo->prepare("INSERT INTO Admin (Admin_ID, Security_Level) VALUES (?, ?)");
        $facultyStmt =$pdo->prepare("INSERT INTO Faculty (Faculty_ID, Office_ID, Specialty, Faculty_Rank, Faculty_Type) VALUES (?, ?, ?, ?, ?)");
        $studentStmt =$pdo->prepare("INSERT INTO Student (Student_ID, Student_Type, Student_Status) VALUES (?, ?, ?)");
        $undergradStmt =$pdo->prepare("INSERT INTO Undergraduate (Student_ID, Student_Year) VALUES (?, ?)");
        $gradStmt =$pdo->prepare("INSERT INTO Graduate (Student_ID, Student_Year, Program_Type, Credits_Earned) VALUES (?, ?, ?, ?)");

        $facultyIds = [];$studentIds = [];

        // Create Admins (2 admins)
        for ($i = 0; $i < 2; $i++) {
            $fn =$firstNames[array_rand($firstNames)];$ln = $lastNames[array_rand($lastNames)];
            $userStmt->execute([$fn, 'A', $ln,$cities[array_rand($cities)],$states[array_rand($states)], rand(1, 999),$streets[array_rand($streets)], '10001', 'Admin']);$uid = $pdo->lastInsertId();$loginStmt->execute([$uid, strtolower("$fn.$ln$uid@luscid.edu"), password_hash('password123', PASSWORD_BCRYPT)]);
            $adminStmt->execute([$uid, rand(1, 10)]);
        }

        // Create Faculty (8 faculty members)
        $specialties = ['Algorithms', 'Database Systems', 'Artificial Intelligence', 'Organic Chemistry', 'Calculus', 'Quantum Mechanics', 'Microeconomics', 'World History'];
        $ranks = ['Instructor', 'Assistant Professor', 'Associate Professor', 'Full Professor'];$fTypes = ['Full-Time', 'Part-Time'];

        for ($i = 0; $i < 8; $i++) {$fn = $firstNames[array_rand($firstNames)];
            $ln =$lastNames[array_rand($lastNames)];$userStmt->execute([$fn, 'B',$ln, $cities[array_rand($cities)], $states[array_rand($states)], rand(1, 999), $streets[array_rand($streets)], '10002', 'Faculty']);
            $uid =$pdo->lastInsertId();
            $facultyIds[] =$uid;

            $loginStmt->execute([$uid, strtolower("$fn.$ln@luscid.edu"), password_hash('password123', PASSWORD_BCRYPT)]);
            $facultyStmt->execute([$uid,$officeIds[array_rand($officeIds)],$specialties[$i % count($specialties)], $ranks[array_rand($ranks)], $fTypes[array_rand($fTypes)]]);
        }

        // Create Students (20 students)
        $sTypes = ['Full-Time', 'Part-Time'];$statuses = ['Active Student', 'Academic Probation', 'Good Standing'];
        $ugYears = ['Freshman', 'Sophomore', 'Junior', 'Senior'];$gradPrograms = ['MS/MA', 'PhD'];

        for ($i = 0; $i < 20; $i++) {$fn = $firstNames[array_rand($firstNames)];
            $ln =$lastNames[array_rand($lastNames)];$userStmt->execute([$fn, 'C',$ln, $cities[array_rand($cities)], $states[array_rand($states)], rand(1, 999), $streets[array_rand($streets)], '10003', 'Student']);
            $uid =$pdo->lastInsertId();
            $studentIds[] =$uid;

            $loginStmt->execute([$uid, strtolower("$fn.$ln.stu$uid@luscid.edu"), password_hash('password123', PASSWORD_BCRYPT)]);
            $studentStmt->execute([$uid, $sTypes[array_rand($sTypes)], $statuses[array_rand($statuses)]]);

            if ($i < 15) {
                // Undergraduate
                $undergradStmt->execute([$uid, $ugYears[array_rand($ugYears)]]);
            } else {
                // Graduate
                $gradStmt->execute([$uid, rand(1, 3), $gradPrograms[array_rand($gradPrograms)], rand(12, 90)]);
            }
        }

        // 4. INSERT DEPARTMENTS & MAJORS
        echo "Inserting Departments and Majors...\n";
        $deptStmt =$pdo->prepare("INSERT INTO Department (Dept_Name, Chair_ID, Email, Phone_No, Office_ID, Dept_Manager) VALUES (?, ?, ?, ?, ?, ?)");
        $majorStmt =$pdo->prepare("INSERT INTO Major (Dept_ID, Major_Name, Num_Credits_Required) VALUES (?, ?, ?)");

        $departments = [
            ['Computer Science', $facultyIds[0], 'cs@luscid.edu', '555-0101', $officeIds[0], 'Alice Smith'],
            ['Mathematics', $facultyIds[1], 'math@luscid.edu', '555-0202', $officeIds[1], 'Bob Jones'],
            ['Physics', $facultyIds[2], 'physics@luscid.edu', '555-0303', $officeIds[2], 'Carol White']
        ];

        $deptIds = [];
        foreach ($departments as$dept) {
            $deptStmt->execute($dept);
            $deptIds[] =$pdo->lastInsertId();
        }

        $majors = [
            [$deptIds[0], 'B.S. Computer Science', 120],
            [$deptIds[0], 'B.S. Software Engineering', 120],
            [$deptIds[1], 'B.S. Mathematics', 120],
            [$deptIds[2], 'B.S. Physics', 128]
        ];

        $majorIds = [];
        foreach ($majors as$maj) {
            $majorStmt->execute($maj);
            $majorIds[] =$pdo->lastInsertId();
        }

        // 5. INSERT SEMESTERS & COURSES
        echo "Inserting Semesters and Courses...\n";
        $semStmt =$pdo->prepare("INSERT INTO Semester (Semester_Name, Year, Start_Date, End_Date) VALUES (?, ?, ?, ?)");
        $semStmt->execute(['Fall', 2026, '2026-08-24', '2026-12-15']);
        $semId =$pdo->lastInsertId();

        $courseStmt =$pdo->prepare("INSERT INTO Course (Dept_ID, Course_Name, Course_Description, Course_Credits, Course_Type) VALUES (?, ?, ?, ?, ?)");
        $courses = [
            [$deptIds[0], 'Introduction to Programming', 'Basics of PHP and Python', 3, 'Undergraduate'],
            [$deptIds[0], 'Data Structures & Algorithms', 'Advanced problem solving', 4, 'Undergraduate'],
            [$deptIds[1], 'Calculus I', 'Limits, derivatives, and integrals', 4, 'Undergraduate'],
            [$deptIds[2], 'General Physics I', 'Mechanics and thermodynamics', 4, 'Undergraduate']
        ];

        $courseIds = [];
        foreach ($courses as$c) {
            $courseStmt->execute($c);
            $courseIds[] =$pdo->lastInsertId();
        }

        // 6. INSERT COURSE SECTIONS
        echo "Inserting Course Sections...\n";
        $secStmt =$pdo->prepare("INSERT INTO Course_Section (Section_No, Avail_Seats, Course_ID, Instructor_ID, Lecture_ID, Semester_ID) VALUES (?, ?, ?, ?, ?, ?)");
        $crns = [];

        foreach ($courseIds as$index => $cId) {$secStmt->execute(['001', 40, $cId,$facultyIds[$index % count($facultyIds)], $lectureIds[$index % count($lectureIds)],$semId]);
            $crns[] =$pdo->lastInsertId();
        }

        // 7. INSERT ENROLLMENTS & ADVISORS
        echo "Linking Students to Courses (Enrollments) and Advisors...\n";
        $enrollStmt =$pdo->prepare("INSERT IGNORE INTO Enrollment (Student_ID, CRN, Grade) VALUES (?, ?, ?)");
        $advisorStmt =$pdo->prepare("INSERT IGNORE INTO Advisor (Student_ID, Faculty_ID) VALUES (?, ?)");
        $studentMajorStmt =$pdo->prepare("INSERT IGNORE INTO Student_Major (Student_ID, Major_ID) VALUES (?, ?)");

        $grades = ['A', 'B', 'C', 'A-', 'B+'];

        foreach ($studentIds as $idx =>$sId) {
            // Assign a major
            $studentMajorStmt->execute([$sId,$majorIds[$idx % count($majorIds)]]);
            
            // Assign an advisor
            $advisorStmt->execute([$sId,$facultyIds[$idx % count($facultyIds)]]);

            // Enroll each student in 2 random courses
            $randomCrns = (array_rand($crns, 2));
            if (!is_array($randomCrns)) {
                $randomCrns = [$randomCrns];
            }
            foreach ($randomCrns as$crnKey) {
                $enrollStmt->execute([$sId, $crns[$crnKey], $grades[array_rand($grades)]]);
            }
        }

        echo "\nDatabase seeding completed successfully! All constraints satisfied.\n";

    } catch (PDOException $e) {
        echo "Error seeding database: " . $e->getMessage() . "\n";
    }
?>