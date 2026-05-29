<?php
require 'db.php';
echo "<h2>Courses table columns:</h2><pre>";
$cols = $conn->query("DESCRIBE courses")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";
echo "</pre>";
echo "<h2>Sample courses data:</h2><pre>";
$rows = $conn->query("SELECT * FROM courses LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
echo "</pre>";
