<?php
    session_start();

    // LOGIN REQUEST HANDLING //

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header("Content-Type: application/json");

        // Establish SQL Connection
        $conn = mysqli_connect(
            "localhost", // sql server hostname    
            "root",  // sql user name
            "",  // sql user pass
            "registration_system",  // sql database name
        );

        // Attempt Login with given parameters
        $email = $_POST["user-email"];
        $password = $_POST["user-password"];

        if (!isset($_POST["user-email"]) || !isset($_POST["user-password"])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Email and password are required."
            ]);
            exit;
        }

        $stmt = mysqli_prepare($conn, 
            "SELECT User_ID, Password, Is_Locked FROM Login WHERE Email = ? LIMIT 1;"
        );
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) === 0) {  // account doesn't exist
            echo json_encode([
                "success" => false,
                "message" => "Invalid email or password."
            ]);
            exit;
        }

        $account = mysqli_fetch_assoc($result);

        if ($account["Is_Locked"]) {
            echo json_encode([
                "success" => false,
                "message" => "This account is locked."
            ]);
            exit;
        }
        if (!password_verify($password, $account["Password"])) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid email or password."
            ]);
            exit;
        }

        $_SESSION["user_id"] = $account["User_ID"];
        $_SESSION["logged_in"] = true;

        echo json_encode([
            "success" => true,
            "message" => "Login successful."
        ]);
        exit;
    }
?>

<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>College Portal - Login</title>

        <link rel="stylesheet" href="style.css">

        <style>
            #login-error {
                border-radius: 5px;
                border-width: 4px;
                border-color: #ff0000;
                padding:8px;
                background-color: #f5bcc9;
                color: #484848;
            }
        </style>
    </head>

    <body>
        <div class="login-container">
            <h1>Luscid University College Portal</h1>
            <p>Welcome back. Please sign in to continue.</p>

            <div id="login-error" hidden></div>

            <form id="login-form">
                <!-- Email -->
                <label for="email">Email</label>
                <input type="text" id="email" placeholder="Enter email" required>
                <!-- Password -->
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Enter password" required>
                <!-- Login -->
                <button type="submit" for="login-form">Login</button>
            </form>
        </div>
    </body>

    <script>
        function handleResponse(xhr) {
            console.log("Server response:", xhr.responseText);

            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                window.location.href = "dashboard.php";
            } else {
                console.log(response.message);
                var errNotif = document.getElementById("login-error")
                errNotif.hidden = false;
                errNotif.textContent = response.message;
            }
        }

        document.getElementById("login-form").addEventListener(
            "submit", 
            function (event) {
                event.preventDefault();
                
                // Setup Request
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "login.php");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = () => handleResponse(xhr)
                xhr.onerror = function () {
                    console.log("Request failed.");
                };

                // Send Request
                const data =
                    "user-email=" + encodeURIComponent(document.getElementById("email").value) +
                    "&user-password=" + encodeURIComponent(document.getElementById("password").value);

                xhr.send(data);
            }
        );
    </script>
</html>
