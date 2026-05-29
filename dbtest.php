<?php
$conn = mysqli_connect("localhost", "root", "", "");
if ($conn) {
    echo "<h2>Database Connection: SUCCESS</h2>";
    echo "Connected to MySQL successfully.";
} else {
    echo "Failed: " . mysqli_connect_error();
}
?>