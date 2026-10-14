<?php
    // Establish SQL Connection
    $conn = mysqli_connect(
        "localhost", // sql server hostname    
        "root",  // sql user name
        "",  // sql user pass
        "registration_system",  // sql database name
    );
    
    // Run init_database.sql

    $sql = file_get_contents('init_database.sql');
    $sql_result = mysqli_multi_query($conn, $sql);

    echo mysqli_error($conn);
    if (!$sql_result) {
        echo "Failed to Initialize Database";
        die(); 
    }

    echo "Initialized registration database.";
    
    mysqli_close($conn);
?>