USE registration_system;

CREATE TABLE IF NOT EXISTS Minor(
   Minor_Id INT PRIMARY KEY AUTO_INCREMENT , 
   DEPT_ID INT NOT NULL, 
   Minor_Name VARCHAR(100) NOT NULL, 
   FOREIGN KEY  (DEPT_ID) 
       REFERENCES Department(Dept_ID)
  );

 CREATE TABLE IF NOT EXISTS Student_Minor (
     Student_ID INT NOT NULL,
     Minor_ID INT NOT NULL,

    PRIMARY KEY (Student_ID, Minor_ID),

    FOREIGN KEY (Student_ID)
        REFERENCES Student(Student_ID),

    FOREIGN KEY (Minor_ID)
        REFERENCES Minor(Minor_ID)

);
