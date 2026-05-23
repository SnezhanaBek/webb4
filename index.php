<?php
require_once 'config.php';

// Автоматически создаём таблицы
initDatabaseIfNeeded();

session_start();
header('Content-Type: text/html; charset=UTF-8');

$pdo = getPDO();

// Загружаем значения из Cookies
$defaultValues = [
    'fio' => $_COOKIE['fio_value'] ?? '',
    'phone' => $_COOKIE['phone_value'] ?? '',
    'email' => $_COOKIE['email_value'] ?? '',
    'birth_date' => $_COOKIE['birth_date_value'] ?? '',
    'gender' => $_COOKIE['gender_value'] ?? '',
    'biography' => $_COOKIE['biography_value'] ?? '',
    'contract' => $_COOKIE['contract_value'] ?? '',
    'languages' => json_decode($_COOKIE['languages_value'] ?? '[]', true)
];

// Загружаем ошибки из Cookies
$errors = [];
$errorMessages = [];

if (isset($_COOKIE['errors'])) {
    $errors = json_decode($_COOKIE['errors'], true) ?: [];
    setcookie('errors', '', time() - 3600, '/');
}

$errorMessages['fio'] = 'ФИО должно содержать только буквы и пробелы, не длиннее 150 символов.';
$errorMessages['phone'] = 'Телефон должен быть в формате +7 (999) 123-45-67.';
$errorMessages['email'] = 'Email должен быть в формате name@domain.ru.';
$errorMessages['birth_date'] = 'Дата рождения должна быть в формате ГГГГ-ММ-ДД.';
$errorMessages['gender'] = 'Выберите пол.';
$errorMessages['languages'] = 'Выберите хотя бы один язык.';
$errorMessages['contract'] = 'Подтвердите согласие с контрактом.';

$success = isset($_GET['success']);
$languagesList = $pdo->query("SELECT * FROM programming_languages ORDER BY id")->fetchAll();

// Проверяем, есть ли сохранённые данные в Cookies
$hasCookieData = !empty($_COOKIE['fio_value']) || !empty($_COOKIE['phone_value']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Анкета — Задание 4</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
        .error-field { border: 2px solid red !important; background: #fff0f0; }
        .radio-group { display: flex; gap: 20px; align-items: center; }
        .radio-group label { display: inline-flex; align-items: center; gap: 6px; font-weight: normal; }
        .radio-group input { width: auto; }
        select[multiple] { height: 140px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        button { background: #3498db; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background: #2980b9; }
        .success-message { background: #e0ffe8; color: #2a6e3b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2a6e3b; }
        .cookie-message { background: #e8f4fd; color: #2c3e50; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3498db; }
        .error-message { background: #fee; color: #c00; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #c00; }
        .note { text-align: center; color: gray; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Регистрационная анкета — Задание 4</h1>
    
    <?php if ($success): ?>
        <div class="success-message">✅ Данные успешно сохранены в базе данных!</div>
    <?php endif; ?>
    
    <?php if ($hasCookieData && !$success): ?>
        <div class="cookie-message">
            🍪 Ваши данные загружены из Cookies (сохранены на год). 
            При следующем визите форма будет заполнена автоматически.
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <strong>⚠️ Ошибки:</strong>
            <ul>
                <?php foreach ($errors as $field): ?>
                    <li>• <?php echo htmlspecialchars($errorMessages[$field]); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="note">Все поля, отмеченные *, обязательны</div>

    <form action="save.php" method="POST">
        <div class="form-group">
            <label>1. ФИО *</label>
            <input type="text" name="fio" value="<?php echo htmlspecialchars($defaultValues['fio']); ?>" class="<?php echo in_array('fio', $errors) ? 'error-field' : ''; ?>">
        </div>

        <div class="form-group">
            <label>2. Телефон *</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($defaultValues['phone']); ?>" class="<?php echo in_array('phone', $errors) ? 'error-field' : ''; ?>">
        </div>

        <div class="form-group">
            <label>3. E-mail *</label>
            <input type="text" name="email" value="<?php echo htmlspecialchars($defaultValues['email']); ?>" class="<?php echo in_array('email', $errors) ? 'error-field' : ''; ?>">
        </div>

        <div class="form-group">
            <label>4. Дата рождения *</label>
            <input type="date" name="birth_date" value="<?php echo htmlspecialchars($defaultValues['birth_date']); ?>" class="<?php echo in_array('birth_date', $errors) ? 'error-field' : ''; ?>">
        </div>

        <div class="form-group">
            <label>5. Пол *</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="male" <?php echo $defaultValues['gender'] == 'male' ? 'checked' : ''; ?>> Мужской</label>
                <label><input type="radio" name="gender" value="female" <?php echo $defaultValues['gender'] == 'female' ? 'checked' : ''; ?>> Женский</label>
                <label><input type="radio" name="gender" value="other" <?php echo $defaultValues['gender'] == 'other' ? 'checked' : ''; ?>> Другой</label>
            </div>
        </div>

        <div class="form-group">
            <label>6. Языки программирования *</label>
            <select name="languages[]" multiple class="<?php echo in_array('languages', $errors) ? 'error-field' : ''; ?>">
                <?php foreach ($languagesList as $lang): ?>
                    <option value="<?php echo $lang['id']; ?>" <?php echo in_array($lang['id'], $defaultValues['languages']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lang['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>7. Биография</label>
            <textarea name="biography" rows="5"><?php echo htmlspecialchars($defaultValues['biography']); ?></textarea>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" name="contract" value="1" <?php echo $defaultValues['contract'] == '1' ? 'checked' : ''; ?>>
            <label>Я ознакомлен(а) с контрактом *</label>
        </div>

        <button type="submit">Сохранить</button>
    </form>
</div>
</body>
</html>