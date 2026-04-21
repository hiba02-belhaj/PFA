<?php
include 'includes/db.php';

// Generate the correct secure hash for 'admin123'
$correct_hash = password_hash('admin123', PASSWORD_DEFAULT);

// Update the admin account in the database
$sql = "UPDATE users SET password_hash = '$correct_hash' WHERE email = 'admin@careconnect.com'";

if(mysqli_query($conn, $sql)) {
    echo "<h3>Success! The admin password has been correctly set.</h3>";
    echo "<a href='login.php'>Click here to go to the login page</a> and log in with <b>admin123</b>.";
} else {
    echo "Error updating record: " . mysqli_error($conn);
}
?>