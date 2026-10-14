-- DATABASE --

DROP DATABASE IF EXISTS registration_system;

CREATE DATABASE registration_system;
USE registration_system;


-- LOCATION --

CREATE TABLE Building (
	Bldg_ID INT PRIMARY KEY AUTO_INCREMENT,
    Bldg_Name VARCHAR(60) NOT NULL UNIQUE,
    Bldg_Usage VARCHAR(255)
);

CREATE TABLE Room ( -- Related to Building (many-to-one; many rooms can be in one building)
	Room_ID INT PRIMARY KEY AUTO_INCREMENT,
	Bldg_ID INT NOT NULL,  -- foreign

    Room_No VARCHAR(5) NOT NULL,  -- Name/Number of the room (e.g. A105)
    Room_Type VARCHAR(15),  
    
	FOREIGN KEY (Bldg_ID) REFERENCES Building(Bldg_ID),
    UNIQUE (Bldg_ID, Room_No)
);

CREATE TABLE Lecture ( -- Related to Room (many-to-one, this is a type of room)
	Lecture_ID INT,  -- foreign, primary
    
	Avail_Seats SMALLINT,
	
    PRIMARY KEY (Lecture_ID),
    FOREIGN KEY (Lecture_ID) REFERENCES Room(Room_ID)
);

CREATE TABLE Lab ( -- Related to Room (many-to-one, this is a type of room)
	Lab_ID INT,  -- foreign, primary
    
	Num_Workstations SMALLINT,
	
    PRIMARY KEY (Lab_ID),
    FOREIGN KEY (Lab_ID) REFERENCES Room(Room_ID)
);

CREATE TABLE Office ( -- Related to Room (many-to-one, this is a type of room)
	Office_ID INT,  -- foreign, primary
    
	Num_Desks SMALLINT,
	
    PRIMARY KEY (Office_ID),
    FOREIGN KEY (Office_ID) REFERENCES Room(Room_ID)
);


-- BASE USER --

CREATE TABLE User (
    User_ID INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Name
    First_Name VARCHAR(50) NOT NULL,
    Middle_Name VARCHAR(50),
    Last_Name VARCHAR(50) NOT NULL,
    
    -- Address
    City VARCHAR(50),
    State VARCHAR(50),
    Country VARCHAR(50),
    Street_No VARCHAR(10),
    Street_Name VARCHAR(100),
    Zip_Code VARCHAR(20),
    
    User_Type VARCHAR(30) NOT NULL
);

CREATE TABLE Login (
    User_ID INT PRIMARY KEY,
    
    Email VARCHAR(127) NOT NULL UNIQUE,
    Password VARCHAR(127) NOT NULL,  -- hash should be stored
    
    Login_Attempts TINYINT NOT NULL DEFAULT 0,
    Is_Locked BOOLEAN NOT NULL DEFAULT FALSE,

    FOREIGN KEY (User_ID) REFERENCES User(User_ID),
    CONSTRAINT chk_email CHECK (Email REGEXP '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$')
);


-- ADMIN --

CREATE TABLE Admin (
	Admin_ID INT,  -- foreign, primary
    
    Security_Level TINYINT CHECK (Security_Level > 0),  -- higher value = more permissions, goes up to 127
    
    PRIMARY KEY (Admin_ID),
    FOREIGN KEY (Admin_ID) REFERENCES User(User_ID)
);


-- FACULTY --

CREATE TABLE Faculty (
    Faculty_ID INT, -- foreign, primary
    Office_ID INT NOT NULL,  -- foreign
    
    Specialty VARCHAR(40),  -- title of the faculty's area of expertise
    Faculty_Rank ENUM('Instructor', 'Assistant Professor', 'Associate Professor', 'Full Professor')  NOT NULL,
    Faculty_Type ENUM('Full-Time', 'Part-Time') NOT NULL,
    
    PRIMARY KEY (Faculty_ID),
    FOREIGN KEY (Faculty_ID) REFERENCES User(User_ID),
    FOREIGN KEY (Office_ID) REFERENCES Office(Office_ID)
);


-- STUDENT --

CREATE TABLE Student (
    Student_ID INT,  -- foreign, primary
    
    Student_Type ENUM('Full-Time', 'Part-Time') NOT NULL,
    Student_Status VARCHAR(30) NOT NULL,  -- e.g. "Active Student", "Inactive Student", "Academic Probation", etc.

	PRIMARY KEY (Student_ID),
    FOREIGN KEY (Student_ID) REFERENCES User(User_ID)
);

CREATE TABLE Undergraduate (
	Student_ID INT,  -- foreign, primary
    
	Student_Year ENUM('Freshman', 'Sophomore', 'Junior', 'Senior') NOT NULL,
    
	PRIMARY KEY (Student_ID),
	FOREIGN KEY (Student_ID) REFERENCES Student(Student_ID)
);

CREATE TABLE Graduate (
	Student_ID INT,  -- foreign, primary
    
    Student_Year TINYINT NOT NULL DEFAULT 1,
    Program_Type VARCHAR(15) NOT NULL,  -- e.g. 'MS/MA', 'PhD'
    
	Credits_Earned SMALLINT NOT NULL DEFAULT 0,
    
    PRIMARY KEY (Student_ID),
    FOREIGN KEY (Student_ID) REFERENCES Student(Student_ID)
);


-- DEPARTMENT --

CREATE TABLE Department (
    Dept_ID INT PRIMARY KEY AUTO_INCREMENT,
    Dept_Name VARCHAR(100) NOT NULL,
    
    Chair_ID INT NOT NULL,  -- foreign
    
    Email VARCHAR(127),
    Phone_No VARCHAR(20),
	Office_ID INT NOT NULL,  -- foreign
    
    Dept_Manager VARCHAR(100),
	
    FOREIGN KEY (Chair_ID) REFERENCES Faculty(Faculty_ID),
    FOREIGN KEY (Office_ID) REFERENCES Office(Office_ID)
);

CREATE TABLE Major (  -- Related to Department (many-to-one)
    Major_ID INT PRIMARY KEY AUTO_INCREMENT,
	Dept_ID INT NOT NULL,  -- foreign
    
    Major_Name VARCHAR(100) NOT NULL,
    Num_Credits_Required SMALLINT,
    
    FOREIGN KEY (Dept_ID) REFERENCES Department(Dept_ID)
);


-- MASTER SCHEDULE --

CREATE TABLE Time_Slot (
	Time_Slot_ID INT PRIMARY KEY AUTO_INCREMENT
);

CREATE TABLE Period (
	Period_ID INT PRIMARY KEY AUTO_INCREMENT,
    Start_Time TIME,  -- 24-hour time format (00:00:00 -> 23:59:59)
    End_Time TIME  -- 24-hour time format (00:00:00 -> 23:59:59)
);

CREATE TABLE Day (
	Day_ID TINYINT PRIMARY KEY AUTO_INCREMENT,
    Week_Day VARCHAR(10) NOT NULL
);

CREATE TABLE Time_Slot_Period ( -- Relationship between Period & Time_Slot (many-to-many)
	Period_ID INT,
    Time_Slot_ID INT,
    
    PRIMARY KEY (Period_ID, Time_Slot_ID),
    FOREIGN KEY (Period_ID) REFERENCES Period(Period_ID),
    FOREIGN KEY (Time_Slot_ID) REFERENCES Time_Slot(Time_Slot_ID)
);

CREATE TABLE Time_Slot_Day ( -- Relationship between Day & Time_Slot
	Day_ID TINYINT,
    Time_Slot_ID INT,
    
    PRIMARY KEY (Day_ID, Time_Slot_ID),
    FOREIGN KEY (Day_ID) REFERENCES Day(Day_ID),
    FOREIGN KEY (Time_Slot_ID) REFERENCES Time_Slot(Time_Slot_ID)
);

INSERT INTO Day(WEEK_DAY) VALUES 
	('Monday'), ('Tuesday'), ('Wednesday'), ('Thursday'), ('Friday'), ('Saturday');


CREATE TABLE Semester (
	Semester_ID SMALLINT PRIMARY KEY AUTO_INCREMENT,
    
	Semester_Name ENUM('Spring', 'Summer', 'Fall', 'Winter') NOT NULL,
	Year SMALLINT NOT NULL,
	Start_Date DATE NOT NULL,
	End_Date DATE NOT NULL,

	UNIQUE (Semester_Name, Year)
);


CREATE TABLE Hold_Type (  -- Contains all valid/possible types of holds
	Hold_Type_ID SMALLINT PRIMARY KEY AUTO_INCREMENT,
    Hold_Type VARCHAR(50) NOT NULL UNIQUE,  -- e.g. "Probation", "Financial", "Immunization", "Advising", etc.
	Hold_Description VARCHAR(255)
);

INSERT INTO Hold_Type(Hold_Type, Hold_Description) VALUES 
	('Probation', "Academic Probation"), 
    ('Financial', "Billing or Financial Aid issue"), 
    ('Immunization', "Immunization needs to be received"), 
    ('Advising', "Student needs advising");

CREATE TABLE Student_Hold (  -- Relationship between Student & Hold_Type (many-to-many; contains all instances of holds in student accounts)
	Student_Hold_ID INT PRIMARY KEY AUTO_INCREMENT,
	Student_ID INT NOT NULL,  -- foreign
    Hold_ID SMALLINT NOT NULL,  -- foreign
    
    Date_Given DATE,
    
    FOREIGN KEY (Student_ID) REFERENCES Student(Student_ID),
    FOREIGN KEY (Hold_ID) REFERENCES Hold_Type(Hold_Type_ID)
);


CREATE TABLE Course (
	Course_ID INT PRIMARY KEY AUTO_INCREMENT,
    Dept_ID INT NOT NULL,  -- foreign key

    Course_Name VARCHAR(60),
    Course_Description VARCHAR(255),
    
    Course_Credits TINYINT,
    Course_Type ENUM('Undergraduate', 'Graduate'),

    FOREIGN KEY (Dept_ID) REFERENCES Department(Dept_ID)
);


CREATE TABLE Prerequisite (  -- Relationship between courses (many-to-many)
	Course_ID INT,  -- foreign, primary
    Prereq_ID INT,  -- foreign, primary
    
    Minimum_Grade VARCHAR(15) NOT NULL,
    
    PRIMARY KEY (Course_ID, Prereq_ID),
    FOREIGN KEY (Course_ID) REFERENCES Course(Course_ID),
    FOREIGN KEY (Prereq_ID) REFERENCES Course(Course_ID)
);

CREATE TABLE Major_Requirement (  -- Relationship between Major & Course (many-to-many)
	Major_ID INT,
    Course_ID INT,
    
    PRIMARY KEY (Course_ID, Major_ID),
    FOREIGN KEY (Major_ID) REFERENCES Major(Major_ID),
    FOREIGN KEY (Course_ID) REFERENCES Course(Course_ID)
    
    -- each major can have many required courses, and each course can have many majors that require it.
);


CREATE TABLE Course_Section ( -- Related to Course (many-to-one)
	CRN INT PRIMARY KEY AUTO_INCREMENT,
    
	Section_No VARCHAR(5),
    Avail_Seats SMALLINT,  -- independent of Lecture(Avail_Seats).
    
    Course_ID INT NOT NULL,  -- foreign
    Instructor_ID INT NOT NULL,  -- foreign
    Lecture_ID INT NOT NULL,  -- foreign
    Semester_ID SMALLINT NOT NULL,  -- foreign
    
    FOREIGN KEY (Course_ID) REFERENCES Course(Course_ID),
    FOREIGN KEY (Instructor_ID) REFERENCES Faculty(Faculty_ID),
    FOREIGN KEY (Lecture_ID) REFERENCES Room(Room_ID),
    FOREIGN KEY (Semester_ID) REFERENCES Semester(Semester_ID)
);


CREATE TABLE Enrollment (  -- Relationship between Student & Course Section
	Student_ID INT,  -- foreign, primary
    CRN INT,  -- foreign, primary
    
	Grade VARCHAR(15),  -- can be letter or number
    
    PRIMARY KEY (Student_ID, CRN),
    FOREIGN KEY (Student_ID) REFERENCES Student(Student_ID),
    FOREIGN KEY (CRN) REFERENCES Course_Section(CRN)
);


-- RELATIONSHIPS --

CREATE TABLE Advisor (  -- Relationship between Student & Faculty
	Student_ID INT,  -- foreign, primary
    Faculty_ID INT,  -- foreign, primary
    
    PRIMARY KEY (Student_ID, Faculty_ID),
    FOREIGN KEY (Student_ID) REFERENCES Student(Student_ID),
    FOREIGN KEY (Faculty_ID) REFERENCES Faculty(Faculty_ID)
);

CREATE TABLE Faculty_Department (  -- Relationship between Faculty & Department
	Faculty_ID INT,  -- foreign, primary
    Dept_ID INT,  -- foreign, primary
    
    Percent_Time DECIMAL(5, 2) CHECK (Percent_Time BETWEEN 0.00 AND 100.00),
    Appointed_Date DATE,
    
    PRIMARY KEY (Faculty_ID, Dept_ID),
    FOREIGN KEY (Faculty_ID) REFERENCES Faculty(Faculty_ID),
    FOREIGN KEY (Dept_ID) REFERENCES Department(Dept_ID)
);

CREATE TABLE Student_Major (  -- Relationship between Student & Major (many-to-many; one student can have multiple majors)
	Student_ID INT,  -- foreign, primary
    Major_ID INT,  -- foreign, primary
	
    PRIMARY KEY (Student_ID, Major_ID),
    FOREIGN KEY (Student_ID) REFERENCES Student(Student_ID),
    FOREIGN KEY (Major_ID) REFERENCES Major(Major_ID)
);

CREATE TABLE Student_History (  -- Relationship between Student & Course_Section (many-to-many)
	Student_ID INT,  -- foreign, primary
    CRN INT,  -- foreign, primary
    
    PRIMARY KEY (Student_ID, CRN),
    FOREIGN KEY (Student_ID) REFERENCES Student(Student_ID),
    FOREIGN KEY (CRN) REFERENCES Course_Section(CRN)
);

CREATE TABLE Faculty_History (  -- Relationship between Faculty & Course_Section (many-to-many)
	Faculty_ID INT,  -- foreign, primary
    CRN INT,  -- foreign, primary
    
    PRIMARY KEY (Faculty_ID, CRN),
    FOREIGN KEY (Faculty_ID) REFERENCES Faculty(Faculty_ID),
    FOREIGN KEY (CRN) REFERENCES Course_Section(CRN)
);
