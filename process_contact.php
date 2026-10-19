<?php
session_start();
include 'db_conn.php';

// Check if the form was actually submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Retrieve and sanitize inputs to prevent SQL injection
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $subject = $conn->real_escape_string(trim($_POST['subject']));
    $message = $conn->real_escape_string(trim($_POST['message']));

    // 2. Validate that fields aren't empty (basic fallback)
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo "<script>
                alert('Please fill in all fields.');
                window.history.back();
              </script>";
        exit();
    }

    // 3. Insert the data into the contact_messages table
    $sql = "INSERT INTO contact_messages (name, email, subject, message) 
            VALUES ('$name', '$email', '$subject', '$message')";

    if ($conn->query($sql) === TRUE) {
        // Success! Alert the user and send them back to the shop
        echo "<script>
                alert('Thank you, $name! Your message has been sent successfully. We will get back to you soon.');
                window.location.href = 'shop.php';
              </script>";
    } else {
        // Database error
        echo "<script>
                alert('Oops! Something went wrong while saving your message. Please try again later.');
                window.history.back();
              </script>";
    }

} else {
    // If someone tries to access this file directly without submitting the form, kick them back to the contact page
    header("Location: contact.php");
    exit();
}
?>