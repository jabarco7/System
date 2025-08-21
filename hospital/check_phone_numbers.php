<?php
require_once 'hms/include/config.php';

echo "<h2>Checking Phone Numbers in Database</h2>";

// Check appointments
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM appointment");
$count = mysqli_fetch_assoc($result)['count'];
echo "<p>Total appointments: $count</p>";

// Check patients with phone numbers
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM tblpatient WHERE PatientContno IS NOT NULL AND PatientContno != ''");
$count = mysqli_fetch_assoc($result)['count'];
echo "<p>Patients with phone numbers: $count</p>";

// Check sample data
$result = mysqli_query($con, "SELECT PatientName, PatientContno, PatientEmail FROM tblpatient WHERE PatientContno IS NOT NULL AND PatientContno != '' LIMIT 10");
echo "<h3>Sample patients with phone numbers:</h3>";
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>" . htmlspecialchars($row['PatientName']) . ": " . htmlspecialchars($row['PatientContno']) . " (" . htmlspecialchars($row['PatientEmail']) . ")</li>";
}
echo "</ul>";

// Check appointments with phone numbers
$result = mysqli_query($con, "
    SELECT 
        a.id,
        u.fullName AS patientName,
        p.PatientContno,
        u.email
    FROM appointment a
    JOIN users u ON u.id = a.userId
    LEFT JOIN tblpatient p ON p.PatientEmail = u.email
    LIMIT 10
");

echo "<h3>Sample appointments with phone lookup:</h3>";
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    $phone = $row['PatientContno'] ?: 'No phone';
    echo "<li>Appointment ID: " . $row['id'] . " - " . htmlspecialchars($row['patientName']) . ": " . htmlspecialchars($phone) . " (" . htmlspecialchars($row['email']) . ")</li>";
}
echo "</ul>";

mysqli_close($con);
?>
