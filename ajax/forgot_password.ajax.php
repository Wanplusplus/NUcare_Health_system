<?php

require_once '../includes/db.php';
require_once '../includes/mailer.php';

$email = trim($_POST['email']);

if(empty($email)){

    echo json_encode([
        "status" => "error",
        "message" => "Email is required."
    ]);

    exit;
}

$stmt = $conn->prepare(
    "SELECT patient_id, email
     FROM patients
     WHERE email = ?"
);

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows > 0){

    $token = bin2hex(random_bytes(32));

    $expiry = date(
        "Y-m-d H:i:s",
        strtotime("+20 minutes")
    );

    $update = $conn->prepare(
        "UPDATE patients
         SET reset_token = ?,
             token_expiry = ?
         WHERE email = ?"
    );

    $update->bind_param(
        "sss",
        $token,
        $expiry,
        $email
    );

    $update->execute();

    $resetLink =
    "http://localhost/NUCARE_HEALTH_SYSTEM/auth/reset_password.php?token=$token";

    if(sendResetEmail($email, $resetLink)){

        echo json_encode([
            "status" => "success",
            "message" => "Reset email sent successfully."
        ]);

    } else {

        echo json_encode([
            "status" => "error",
            "message" => "Failed to send email."
        ]);
    }

} else {

    echo json_encode([
        "status" => "error",
        "message" => "Email not found."
    ]);
}
?>