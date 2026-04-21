<?php
// logout.php
session_start();
session_unset();    // Free all session variables
session_destroy();  // Destroy the session

// Redirect the user back to the homepage
header("Location: index.php");
exit;
?>
