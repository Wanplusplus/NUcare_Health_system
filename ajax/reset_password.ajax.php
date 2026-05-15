<?php

require_once '../includes/db.php';

$token = $_POST['token'];
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

if($password !== $confirm){

    echo json_encode([
        "status" => "error",
        "message" => "Passwords do not match."
    ]);

    exit;
}

$stmt = $conn->prepare(
    "SELECT patient_id
     FROM patients
     WHERE reset_token = ?
     AND token_expiry > NOW()"
);

$stmt->bind_param("s", $token);

$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows > 0){

    $hashedPassword =
    password_hash($password, PASSWORD_DEFAULT);

    $update = $conn->prepare(
        "UPDATE patients
         SET password = ?,
             reset_token = NULL,
             token_expiry = NULL
         WHERE reset_token = ?"
    );

    $update->bind_param(
        "ss",
        $hashedPassword,
        $token
    );

    $update->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Password updated successfully."
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => "Invalid or expired token."
    ]);
}
?>