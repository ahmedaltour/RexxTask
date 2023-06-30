<?php

$jsonData = file_get_contents(
    'C:\Users\ahmed\source\repos\RexxTask\RexxTask\Code Challenge (Events).json'
);
$participations = json_decode($jsonData, true);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rexxx";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//Create the database tables
$employeeCreateQuery = "CREATE TABLE IF NOT EXISTS employee (
 employee_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(255) UNIQUE,
    employee_mail VARCHAR(255)
);
";

if ($conn->query($employeeCreateQuery) !== true) {
    die("Error creating table: " . $conn->error);
}

$eventCreateQuery = "CREATE TABLE IF NOT EXISTS event (
    event_id INT PRIMARY KEY,
    event_name VARCHAR(255),
    event_date DATE
);
";

if ($conn->query($eventCreateQuery) !== true) {
    die("Error creating table: " . $conn->error);
}

$participationCreateQuery = "CREATE TABLE IF NOT EXISTS participation (
  participation_id INT PRIMARY KEY,
  participation_fee DECIMAL(10, 2),
  employee_id INT,
  event_id INT,
  FOREIGN KEY (employee_id) REFERENCES employee(employee_id),
  FOREIGN KEY (event_id) REFERENCES event(event_id)
);
";

if ($conn->query($participationCreateQuery) !== true) {
    die("Error creating table: " . $conn->error);
}

//Parse file content
foreach ($participations as $entry) {
    $participationId = $entry["participation_id"];
    $employeeName = $entry["employee_name"];
    $employeeMail = $entry["employee_mail"];
    $eventId = $entry["event_id"];
    $eventName = $entry["event_name"];
    $participationFee = $entry["participation_fee"];
    $eventDate = $entry["event_date"];

    //If the entry isn't in the database ,it gets added
    $eventSelectQuery = "SELECT event_id FROM event WHERE event_id = '$eventId'";
    $result = mysqli_query($conn, $eventSelectQuery);

    if (mysqli_num_rows($result) == 0) {
        // Entry doesn't exist, insert it into the database
        $eventQuery =
            "INSERT INTO event (event_id, event_name, event_date)
                   VALUES ('" .
            $eventId .
            "', '" .
            $eventName .
            "', '" .
            $eventDate .
            "')";
        if ($conn->query($eventQuery)) {
            echo "Entry inserted successfully.";
        } else {
            echo "Error inserting entry: " . mysqli_error($conn);
        }
    }

    $employeeSelectQuery = "SELECT employee_id FROM employee WHERE employee_name = '$employeeName'";
    $result = mysqli_query($conn, $employeeSelectQuery);

    if (mysqli_num_rows($result) == 0) {
        // Entry doesn't exist, insert it into the database
        $employeeQuery =
            "INSERT INTO employee (employee_name, employee_mail)
                      VALUES ('" .
            $employeeName .
            "', '" .
            $employeeMail .
            "')";
        if ($conn->query($employeeQuery)) {
            echo "Entry inserted successfully.";
            $employee_id = $conn->insert_id;
        } else {
            echo "Error inserting entry: " . mysqli_error($conn);
        }
    } else {
        $employee_id = mysqli_fetch_assoc($result)["employee_id"];
    }

    // Insert into participations table
    $participationQuery =
        "INSERT INTO participation (participation_id, participation_fee, employee_id, event_id)
                      VALUES ('" .
        $participationId .
        "',
                              " .
        $participationFee .
        ",
                              " .
        $employee_id .
        ",
                              '" .
        $eventId .
        "')";
    $conn->query($participationQuery);
}

// Close the database connection
$conn->close();

//Create the filters form
echo '
<form method="POST" action="">
  <label for="employee_name">Employee Name:</label>
  <input type="text" name="employee_name" id="employee_name">

  <label for="event_name">Event Name:</label>
  <input type="text" name="event_name" id="event_name">

  <label for="date">Date:</label>
  <input type="date" name="date" id="date">

  <button type="submit">Filter</button>
</form>
';

$employeeNameField = $_POST["employee_name"] ?? "";
$eventNameField = $_POST["event_name"] ?? "";
$dateField = $_POST["date"] ?? "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//Pass the filter to the database
$query =
    "select c.employee_name,b.event_name,b.event_date,a.participation_fee from participation a left join event b on a.event_id=b.event_id left join employee c on a.employee_id=c.employee_id where 1=1";

// Apply filters to the query
if (!empty($employeeNameField)) {
    $query .= " AND employee_name LIKE '%$employeeNameField%'";
}
if (!empty($eventNameField)) {
    $query .= " AND event_name LIKE '%$eventNameField%'";
}
if (!empty($dateField)) {
    $query .= " AND event_date = '$dateField'";
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        //create the table to show the results
        echo '<table width="600">
<tr>
    <th bgcolor="silver">Employee name</th>
    <th bgcolor="silver">Event Name</th>
    <th bgcolor="silver">Date</th>
  </tr>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>
      <td>' .
                $row["employee_name"] .
                '</td>
      <td>' .
                $row["event_name"] .
                '</td>
      <td>' .
                $row["event_date"] .
                '</td>
    </tr>';

            // Accumulate the price for calculating total
            $totalPrice += $row["participation_fee"];
        }
    } else {
        echo '<tr><td colspan="4">No results found.</td></tr>';
    }

    // Output the total price
    echo '</tbody>
  <tfoot>
    <tr>
      <td colspan="3">Total Price:</td>
      <td>' .
        $totalPrice .
        '</td>
    </tr>
  </tfoot>
</table>';

    // Close the database connection
    $conn->close();
}

?>
