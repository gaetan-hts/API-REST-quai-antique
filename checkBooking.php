<?php

// Set response headers to allow cross-origin requests and specify content type
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

try {
    // Connect to the database
    require_once 'dbConnect.php';

    // Retrieve POST data and decode it as JSON
    $data = json_decode(file_get_contents("php://input"));

    echo json_encode($data);

    // Sanitize input variables to prevent SQL injection
    $name = htmlspecialchars($data->name, ENT_QUOTES, 'UTF-8');
    $surname = htmlspecialchars($data->surname, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($data->email, ENT_QUOTES, 'UTF-8');
    $people = htmlspecialchars($data->people, ENT_QUOTES, 'UTF-8');
    $allergies = htmlspecialchars($data->allergies, ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($data->date, ENT_QUOTES, 'UTF-8');
    $time = htmlspecialchars($data->time, ENT_QUOTES, 'UTF-8');
    


    // Get the maximum number of people allowed per reservation from the database
    $stmt = $pdo->prepare('SELECT maxnumber FROM maxpeople WHERE id = 1');
    $stmt->execute();
    $maxPeople = intval($stmt->fetchColumn());

    // Check if the date exists in the database
    $stmt = $pdo->prepare('SELECT date FROM calendar WHERE date = :date');
    $stmt->bindParam(':date', $date, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // If the date doesn't exist and is not a Monday or Wednesday, insert it into the database
    if (!$result) {
          $lunchPeople = 0;
          $dinnerPeople = 0;
          $sql = "INSERT INTO calendar (date, lunchPeople, dinnerPeople, maxPeople) VALUES ('$date', '$lunchPeople', '$dinnerPeople', '$maxPeople')";
          $pdo->exec($sql);
    }
    // If the time is between 12:00 and 14:00, update the number of people for lunch
    if ($time >= '12:00' && $time <= '14:00') {
        $stmt = $pdo->prepare('UPDATE calendar SET lunchPeople = lunchPeople + :people WHERE date = :date');
        $stmt->bindParam(':people', $people, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
         // Retrieve updated number of lunch and dinner reservations and maximum number of people allowed
        $stmt = $pdo->prepare('SELECT lunchPeople, dinnerPeople, maxPeople FROM calendar WHERE date = :date');
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // Construct the response array
        $response = array(
            'lunchPeople' => ($result['lunchPeople'] <= $result['maxPeople']),
            'dinnerPeople' => ($result['dinnerPeople'] <= $result['maxPeople']),
            'date' => $date
        );
        echo json_encode($response);
    // If the time is between 19:00 and 21:00, update the number of people for dinner
    } elseif ($time >= '19:00' && $time <= '21:00') {
        $stmt = $pdo->prepare('UPDATE calendar SET dinnerPeople = dinnerPeople + :people WHERE date = :date');
        $stmt->bindParam(':people', $people, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        // Retrieve updated number of lunch and dinner reservations and maximum number of people allowed
        $stmt = $pdo->prepare('SELECT dinnerPeople, lunchPeople, maxPeople FROM calendar WHERE date = :date');
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // Construct the response array
        $response = array(
            'lunchPeople' => ($result['lunchPeople'] <= $result['maxPeople']),
            'dinnerPeople' => ($result['dinnerPeople'] <= $result['maxPeople'])
        );
        echo json_encode($response);
        
    } else {
        echo json_encode(array("booked ?" => false));
    }
} catch (PDOException $e) {
    // If there is an error connecting to the database, return an error message
    echo 'Error: ' . $e->getMessage();
}
// Close the database connection
$pdo = null;

?>