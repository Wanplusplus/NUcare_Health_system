<?php
declare(strict_types=1);

function patientsInfoEmpty(): array
{
 return [
 'id' => null,
 'UserID' => null,
 'contact_no' => '',
 'gender' => '',
 'birth_date' => '',
 'age' => '',
 'nationality' => '',
 'status' => '',
 'religion' => '',
 'address' => '',
 'guardian_name' => '',
 'relationship' => '',
 'mobile_no' => '',
 'telephone' => '',
 'emergency_address' => '',
 ];
}

function patientsInfoLoad(PDO $pdo, int $userID): array
{
 $stmt = $pdo->prepare('SELECT * FROM patients_info WHERE UserID = :uid LIMIT 1');
 $stmt->execute([':uid' => $userID]);
 $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

 return array_merge(patientsInfoEmpty(), $row, ['UserID' => $userID]);
}

function patientsInfoPayload(array $source): array
{
 $payload = [];
 foreach (array_keys(patientsInfoEmpty()) as $key) {
 if ($key === 'id' || $key === 'UserID') {
 continue;
 }
 $payload[$key] = trim((string)($source[$key] ?? ''));
 }

 $payload['age'] = (int)($payload['age'] !== '' ? $payload['age'] : 0);
 return $payload;
}

function patientsInfoValidate(array $payload): array
{
 $errors = [];

 if ($payload['contact_no'] === '') $errors[] = 'Contact number is required.';
 if ($payload['gender'] === '') $errors[] = 'Gender is required.';
 if ($payload['birth_date'] === '') $errors[] = 'Birth date is required.';
 if ($payload['age'] <= 0) $errors[] = 'Age must be greater than zero.';

 if ($payload['birth_date'] !== '') {
 $date = DateTime::createFromFormat('Y-m-d', $payload['birth_date']);
 if (!$date || $date->format('Y-m-d') !== $payload['birth_date']) {
 $errors[] = 'Birth date must be a valid date.';
 }
 }

 return $errors;
}

function patientsInfoSave(PDO $pdo, int $userID, array $payload): void
{
 $sql = "
 INSERT INTO patients_info
 (UserID, contact_no, gender, birth_date, age, nationality, status, religion, address,
 guardian_name, relationship, mobile_no, telephone, emergency_address)
 VALUES
 (:UserID, :contact_no, :gender, :birth_date, :age, :nationality, :status, :religion, :address,
 :guardian_name, :relationship, :mobile_no, :telephone, :emergency_address)
 ON DUPLICATE KEY UPDATE
 contact_no = VALUES(contact_no),
 gender = VALUES(gender),
 birth_date = VALUES(birth_date),
 age = VALUES(age),
 nationality = VALUES(nationality),
 status = VALUES(status),
 religion = VALUES(religion),
 address = VALUES(address),
 guardian_name = VALUES(guardian_name),
 relationship = VALUES(relationship),
 mobile_no = VALUES(mobile_no),
 telephone = VALUES(telephone),
 emergency_address = VALUES(emergency_address)
 ";

 $params = [':UserID' => $userID];
 foreach ($payload as $key => $value) {
 $params[':' . $key] = $value === '' && !in_array($key, ['contact_no', 'gender', 'birth_date', 'age'], true)
 ? null
 : $value;
 }

 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
}

function familyHistoryLoad(PDO $pdo, int $userID): array
{
 $stmt = $pdo->prepare("
 SELECT FamilyHistoryID, condition_name, relationship, notes
 FROM patient_family_history
 WHERE UserID = :uid
 ORDER BY condition_name ASC, FamilyHistoryID ASC
 ");
 $stmt->execute([':uid' => $userID]);
 return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function familyHistoryReplace(PDO $pdo, int $userID, array $items): void
{
 $pdo->prepare('DELETE FROM patient_family_history WHERE UserID = :uid')->execute([':uid' => $userID]);

 $stmt = $pdo->prepare("
 INSERT INTO patient_family_history (UserID, condition_name, relationship, notes)
 VALUES (:uid, :condition_name, :relationship, :notes)
 ");

 foreach ($items as $item) {
 $condition = trim((string)($item['condition_name'] ?? ''));
 $relationship = trim((string)($item['relationship'] ?? ''));
 $notes = trim((string)($item['notes'] ?? ''));

 if ($condition === '' && $relationship === '' && $notes === '') {
 continue;
 }
 if ($condition === '' || $relationship === '') {
 throw new InvalidArgumentException('Each family history row needs a condition and relationship.');
 }

 $stmt->execute([
 ':uid' => $userID,
 ':condition_name' => $condition,
 ':relationship' => $relationship,
 ':notes' => $notes !== '' ? $notes : null,
 ]);
 }
}


