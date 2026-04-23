<?php
require_once __DIR__ . '/../db_connect.php';

function getPatientPrograms(): array
{
    global $conn;

    $programs = [];
    $query = $conn->query("SELECT ProgramID, ProgramName FROM programs ORDER BY ProgramName ASC");

    if ($query instanceof mysqli_result) {
        while ($row = $query->fetch_assoc()) {
            $programs[] = $row;
        }
        $query->free();
    }

    return $programs;
}

function insertPatientRecord(array $patientData): array
{
    global $conn;

    $firstName = trim($patientData['patientFname'] ?? '');
    $lastName = trim($patientData['patientLname'] ?? '');
    $middleName = trim($patientData['patientMname'] ?? '');
    $programId = trim($patientData['patientProgram'] ?? '');
    $sex = trim($patientData['patientSex'] ?? '');
    $birthday = trim($patientData['patientBirthday'] ?? '');
    $email = trim($patientData['patientEmail'] ?? '');
    $phone = trim($patientData['patientPhone'] ?? '');
    $address = trim($patientData['patientAddress'] ?? '');
    $religion = trim($patientData['patientReligion'] ?? '');

    if ($firstName === '' || $lastName === '' || $sex === '') {
        return ['success' => false, 'message' => 'First name, last name, and sex are required.'];
    }

    if (!in_array($sex, ['Male', 'Female'], true)) {
        return ['success' => false, 'message' => 'Please select a valid sex.'];
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if ($phone !== '' && !preg_match('/^[0-9]{1,11}$/', $phone)) {
        return ['success' => false, 'message' => 'Phone number must contain numbers only and be up to 11 digits.'];
    }

    $middleName = $middleName !== '' ? $middleName : null;
    $programId = $programId !== '' ? (int) $programId : null;
    $birthday = $birthday !== '' ? $birthday : null;
    $email = $email !== '' ? $email : null;
    $phone = $phone !== '' ? $phone : null;
    $address = $address !== '' ? $address : null;
    $religion = $religion !== '' ? $religion : null;

    $sql = "INSERT INTO patients (
        PatientFname,
        PatientLname,
        PatientMname,
        ProgramID,
        Sex,
        Birthday,
        EmailAdd,
        PhoneNum,
        Address,
        Religion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ['success' => false, 'message' => 'Failed to prepare the patient insert statement.'];
    }

    $stmt->bind_param(
        'sssissssss',
        $firstName,
        $lastName,
        $middleName,
        $programId,
        $sex,
        $birthday,
        $email,
        $phone,
        $address,
        $religion
    );

    if (!$stmt->execute()) {
        $message = 'Failed to save patient: ' . $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => $message];
    }

    $stmt->close();

    return ['success' => true, 'message' => 'Patient added successfully.'];
}
