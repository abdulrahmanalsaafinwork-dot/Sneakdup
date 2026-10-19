<?php
include 'db_conn.php';

// The password you want to use
$new_password = "admin123";

// Generate a REAL hash using your server's algorithm
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$email = "admin@shoe.com";

// Update the database
$sql = "UPDATE users SET password = '$hashed_password' WHERE email = '$email'";

if ($conn->query($sql) === TRUE) {
    echo "Success! Password for $email has been reset to: $new_password";
} else {
    echo "Error updating record: " . $conn->error;
}
?>