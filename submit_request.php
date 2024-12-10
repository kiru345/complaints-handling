<?php
// Database connection
$conn = new mysqli("sql213.infinityfree.com", "	if0_37871491", "08eN84gcHxg", "if0_37871491_kickininn");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert data into the database
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $date_visited = $_POST['date_visited'];
    $reason = $_POST['reason'];
    $discount = isset($_POST['discount']) && !empty($_POST['discount']) ? $_POST['discount'] : null;

    $stmt = $conn->prepare("INSERT INTO customer_requests (name, email, phone, date_visited, reason, discount) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $phone, $date_visited, $reason, $discount);

    if ($stmt->execute()) {
        echo "Request submitted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
