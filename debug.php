 <html>
    <head>
        <title>Debug (Account List)</title>
        <!-- <link rel="stylesheet" href="./styles.css"> -->
    </head>
    
    <body>
        <table>
            <tr>
                <th>User ID</th>
                <th>Email</th>
                <th>Placeholder Password</th>
                <th>Login Attempts</th>
                <th>Is Locked?</th>
                <th>User Type</th>
            </tr>

            <?php
                $server = "localhost";
                $username = "root";
                $password = "";

                // Connect to SQL
                $conn = mysqli_connect($server, $username, $password);
                if (!$conn) {
                    die("ERROR: Couldn't connect to SQL: " . mysqli_error($conn));
                }
                
                // Select Database
                $dbSelected = mysqli_select_db($conn, "registration_system");
                if (!$dbSelected) die("Couldn't use database - " . mysqli_error($conn) . "<br>");

                // Select & Display all Login info
                $logins = mysqli_query($conn, "SELECT * FROM Login JOIN User ON Login.User_ID = User.User_ID;" );
                if (mysqli_num_rows($logins) < 1) {
                    echo "No data found!";
                    die();
                }

                while ($row = mysqli_fetch_assoc($logins)) {
                    $loginID = $row["User_ID"];
                    $loginEmail = $row["Email"];
                    $loginPass = $row["Password"];
                    $loginAttempts = $row["Login_Attempts"];
                    $loginLocked = $row["Is_Locked"];
                    $userType = $row["User_Type"];
                    echo "
                        <tr>
                            <td>$loginID</td>
                            <td>$loginEmail</td>
                            <td>$loginPass</td>
                            <td>$loginAttempts</td>
                            <td>$loginLocked</td>
                            <td>$userType</td>
                        </tr>
                    ";
                }

                echo "This page is for testing/debug purposes only.";
                
                mysqli_close($conn);
            ?>
        </table>
    </body>
</html>


