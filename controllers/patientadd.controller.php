<?php
session_start();
require_once __DIR__ . '/../models/patientadd.model.php';

function getDefaultPatientFormData(): array
{
    return [
        'patientFname' => '',
        'patientLname' => '',
        'patientMname' => '',
        'patientProgram' => '',
        'patientSex' => '',
        'patientBirthday' => '',
        'patientEmail' => '',
        'patientPhone' => '',
        'patientAddress' => '',
        'patientReligion' => '',
    ];
}

function getPatientAddViewData(): array
{
    $patientData = $_SESSION['patient_form_data'] ?? getDefaultPatientFormData();
    $successMessage = $_SESSION['patient_success_message'] ?? '';
    $errorMessage = $_SESSION['patient_error_message'] ?? '';
    $activePanel = $_SESSION['active_panel'] ?? ($_GET['panel'] ?? 'dashboardPanel');

    unset(
        $_SESSION['patient_form_data'],
        $_SESSION['patient_success_message'],
        $_SESSION['patient_error_message'],
        $_SESSION['active_panel']
    );

    $panelStatusText = 'Ready';
    $panelStatusClass = 'status-ready';

    if ($successMessage !== '') {
        $panelStatusText = 'Saved';
        $panelStatusClass = 'status-success';
    } elseif ($errorMessage !== '') {
        $panelStatusText = 'Error';
        $panelStatusClass = 'status-error';
    }

    return [
        'patientData' => $patientData,
        'programOptions' => getPatientPrograms(),
        'successMessage' => $successMessage,
        'errorMessage' => $errorMessage,
        'activePanel' => $activePanel,
        'panelStatusText' => $panelStatusText,
        'panelStatusClass' => $panelStatusClass,
    ];
}

function handlePatientAddRequest(array $postData): array
{
    $patientData = getDefaultPatientFormData();

    foreach ($patientData as $field => $value) {
        $patientData[$field] = trim($postData[$field] ?? '');
    }

    $result = insertPatientRecord($patientData);

    if ($result['success']) {
        $_SESSION['patient_success_message'] = $result['message'];
        $_SESSION['patient_form_data'] = getDefaultPatientFormData();
    } else {
        $_SESSION['patient_error_message'] = $result['message'];
        $_SESSION['patient_form_data'] = $patientData;
    }

    $_SESSION['active_panel'] = 'patientsPanel';

    return $result;
}
