<?php
require_once 'config.php';

session_start();
header('Content-Type: text/html; charset=UTF-8');

$pdo = getPDO();

function saveToCookie($name, $value) {
    setcookie($name, $value, time() + 365 * 24 * 60 * 60, '/');
}

$errors = [];

// 1. ФИО
$fio = trim($_POST['fio'] ?? '');
if (empty($fio)) {
    $errors['fio'] = true;
} elseif (strlen($fio) > 150) {
    $errors['fio'] = true;
} elseif (!preg_match('/^[A-Za-zА-Яа-яЁё\s]+$/u', $fio)) {
    $errors['fio'] = true;
} else {
    saveToCookie('fio_value', $fio);
}

// 2. Телефон
$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors['phone'] = true;
} elseif (!preg_match('/^[\+\(\)\d\s-]{10,20}$/', $phone)) {
    $errors['phone'] = true;
} else {
    saveToCookie('phone_value', $phone);
}

// 3. Email
$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $errors['email'] = true;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = true;
} else {
    saveToCookie('email_value', $email);
}

// 4. Дата рождения
$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors['birth_date'] = true;
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    $errors['birth_date'] = true;
} else {
    saveToCookie('birth_date_value', $birth_date);
}

// 5. Пол
$gender = $_POST['gender'] ?? '';
if (!in_array($gender, ['male', 'female', 'other'])) {
    $errors['gender'] = true;
} else {
    saveToCookie('gender_value', $gender);
}

// 6. Языки
$languages = $_POST['languages'] ?? [];
if (empty($languages)) {
    $errors['languages'] = true;
} else {
    saveToCookie('languages_value', json_encode($languages));
}

// 7. Биография
$biography = trim($_POST['biography'] ?? '');
saveToCookie('biography_value', $biography);

// 8. Контракт
$contract = isset($_POST['contract']) && $_POST['contract'] == 1 ? 1 : 0;
if ($contract != 1) {
    $errors['contract'] = true;
} else {
    saveToCookie('contract_value', $contract);
}

// Если есть ошибки
if (!empty($errors)) {
    setcookie('errors', json_encode(array_keys($errors)), time() + 86400, '/');
    header('Location: index.php');
    exit;
}

// Сохранение в БД
try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO applications 
        (fio, phone, email, birth_date, gender, biography, contract_agreed) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract]);
    $application_id = $pdo->lastInsertId();
    
    $stmtLang = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang_id) {
        $stmtLang->execute([$application_id, $lang_id]);
    }
    
    $pdo->commit();
    
    header('Location: index.php?success=1');
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    setcookie('errors', json_encode(['database']), time() + 86400, '/');
    header('Location: index.php');
    exit;
}
?>