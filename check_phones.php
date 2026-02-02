<?php
include('manger/db.php');

echo "Available phone numbers in the database:<br><br>";

$result = $conn->query("SELECT student_id, student_name, phone FROM students LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Student ID</th><th>Name</th><th>Phone</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['student_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
        echo "<td>" . $row['phone'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No students found in the database.";
}

$conn->close();
?>
