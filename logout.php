
<?php

// Start the existing session.
session_start();

// Remove all session variables.
session_unset();

// Destroy the current session.
session_destroy();

// Redirect to login after logout.
header('Location: login.php');
exit;
