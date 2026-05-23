<?php
require_once 'config.php';

// Проверяем и создаём таблицы, если их нет
ensureTablesExist();

session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: text/html; charset=UTF-8');

$pdo = getPDO();

// Функция для сохранения данных в Cookies на год
function saveToCookie($name, $value) {
    setcookie($name, $value, time() + 365 * 24 * 60 * 60, '/');
}

// Загружаем сохранённые значения из Cookies
$defaultValues = [];
$defaultValues['fio'] = $_COOKIE['fio_value'] ?? '';
$defaultValues['phone'] = $_COOKIE['phone_value'] ?? '';
$defaultValues['email'] = $_COOKIE['email_value'] ?? '';
$defaultValues['birth_date'] = $_COOKIE['birth_date_value'] ?? '';
$defaultValues['gender'] = $_COOKIE['gender_value'] ?? '';
$defaultValues['biography'] = $_COOKIE['biography_value'] ?? '';
$defaultValues['contract'] = $_COOKIE['contract_value'] ?? '';
$defaultLanguages = json_decode($_COOKIE['languages_value'] ?? '[]', true);

// Загружаем ошибки из Cookies
$errors = [];
$errorMessages = [];

if (isset($_COOKIE['errors'])) {
    $errors = json_decode($_COOKIE['errors'], true) ?: [];
    // Удаляем Cookies с ошибками после чтения
    setcookie('errors', '', time() - 3600, '/');
}

// Сообщения об ошибках с указанием допустимых символов
$errorMessages['fio'] = 'ФИО должно содержать только буквы (русские или английские) и пробелы, не длиннее 150 символов.';
$errorMessages['phone'] = 'Телефон должен быть в формате +7 (999) 123-45-67 или 89123456789. Допустимы цифры, +, -, (, ), пробелы.';
$errorMessages['email'] = 'Email должен быть в формате name@domain.ru (только латиница, цифры, точки, дефисы, @).';
$errorMessages['birth_date'] = 'Дата рождения должна быть в формате ГГГГ-ММ-ДД (например, 1990-01-31).';
$errorMessages['gender'] = 'Выберите пол (Мужской, Женский или Другой).';
$errorMessages['languages'] = 'Выберите хотя бы один язык программирования из списка.';
$errorMessages['contract'] = 'Необходимо подтвердить согласие с контрактом.';

$success = isset($_GET['success']);
$languagesList = $pdo->query("SELECT * FROM programming_languages ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Анкета — Задание 4 (с валидацией и Cookies)</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #2c3e50; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; transition: 0.2s; }
        .error-field { border: 2px solid red !important; background: #fff0f0; }
        .radio-group { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .radio-group label { display: inline-flex; align-items: center; gap: 6px; font-weight: normal; }
        .radio-group input { width: auto; }
        select[multiple] { height: 140px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        button { background: #3498db; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background: #2980b9; }
        .success-message { background: #e0ffe8; color: #2a6e3b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2a6e3b; }
        .error-message { background: #fee; color: #c00; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #c00; }
        .error-list { margin: 0; padding-left: 20px; }
        .field-error-text { color: red; font-size: 12px; margin-top: 5px; }
        .note { text-align: center; color: gray; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Регистрационная анкета — Задание 4</h1>
    
    <?php if ($success): ?>
        <div class="success-message">✅ Данные успешно сохранены! Форма запомнит ваши данные на год.</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <strong>⚠️ Пожалуйста, исправьте следующие ошибки:</strong>
            <ul class="error-list">
                <?php foreach ($errors as $field): ?>
                    <?php if (isset($errorMessages[$field])): ?>
                        <li>• <?php echo htmlspecialchars($errorMessages[$field], ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="note">(Все поля, отмеченные *, обязательны для заполнения)</div>

    <form action="save.php" method="POST">
        <!-- 1. ФИО -->
        <div class="form-group">
            <label>1. ФИО *</label>
            <input type="text" name="fio" 
                   value="<?php echo htmlspecialchars($defaultValues['fio'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="<?php echo in_array('fio', $errors) ? 'error-field' : ''; ?>"
                   placeholder="Иванов Иван Иванович">
        </div>

        <!-- 2. Телефон -->
        <div class="form-group">
            <label>2. Телефон *</label>
            <input type="text" name="phone" 
                   value="<?php echo htmlspecialchars($defaultValues['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="<?php echo in_array('phone', $errors) ? 'error-field' : ''; ?>"
                   placeholder="+7 (999) 123-45-67">
        </div>

        <!-- 3. Email -->
        <div class="form-group">
            <label>3. E-mail *</label>
            <input type="text" name="email" 
                   value="<?php echo htmlspecialchars($defaultValues['email'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="<?php echo in_array('email', $errors) ? 'error-field' : ''; ?>"
                   placeholder="example@mail.ru">
        </div>

        <!-- 4. Дата рождения -->
        <div class="form-group">
            <label>4. Дата рождения *</label>
            <input type="date" name="birth_date" 
                   value="<?php echo htmlspecialchars($defaultValues['birth_date'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="<?php echo in_array('birth_date', $errors) ? 'error-field' : ''; ?>">
        </div>

        <!-- 5. Пол -->
        <div class="form-group">
            <label>5. Пол *</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="male" <?php echo ($defaultValues['gender'] == 'male') ? 'checked' : ''; ?>> Мужской</label>
                <label><input type="radio" name="gender" value="female" <?php echo ($defaultValues['gender'] == 'female') ? 'checked' : ''; ?>> Женский</label>
                <label><input type="radio" name="gender" value="other" <?php echo ($defaultValues['gender'] == 'other') ? 'checked' : ''; ?>> Другой</label>
            </div>
            <?php if (in_array('gender', $errors)): ?>
                <div class="field-error-text"><?php echo htmlspecialchars($errorMessages['gender'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <!-- 6. Любимые языки -->
        <div class="form-group">
            <label>6. Любимые языки программирования *</label>
            <select name="languages[]" multiple class="<?php echo in_array('languages', $errors) ? 'error-field' : ''; ?>">
                <?php foreach ($languagesList as $lang): ?>
                    <option value="<?php echo $lang['id']; ?>" <?php echo in_array($lang['id'], $defaultLanguages) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (in_array('languages', $errors)): ?>
                <div class="field-error-text"><?php echo htmlspecialchars($errorMessages['languages'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <!-- 7. Биография -->
        <div class="form-group">
            <label>7. Биография</label>
            <textarea name="biography" rows="5" placeholder="Расскажите о себе..."><?php echo htmlspecialchars($defaultValues['biography'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <!-- 8. Контракт -->
        <div class="form-group checkbox-group">
            <input type="checkbox" name="contract" value="1" <?php echo ($defaultValues['contract'] == '1') ? 'checked' : ''; ?>>
            <label>Я ознакомлен(а) с контрактом *</label>
            <?php if (in_array('contract', $errors)): ?>
                <div class="field-error-text"><?php echo htmlspecialchars($errorMessages['contract'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <button type="submit">Сохранить</button>
    </form>
</div>
</body>
</html>