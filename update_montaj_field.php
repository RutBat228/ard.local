<?php
include('inc/db.php');

header('Content-Type: application/json');

$monId = isset($_POST['monId']) ? (int)$_POST['monId'] : null;
$field = isset($_POST['field']) ? $_POST['field'] : null;
$value = isset($_POST['value']) ? (int)$_POST['value'] : null;

$response = ['success' => false, 'message' => ''];

// Список допустимых полей с учетом клиентского значения
$validFields = ['dogovor', 'status', 'status_baza', 'stat_baza']; // Добавили 'stat_baza'

// Приводим 'stat_baza' к 'status_baza' для работы с базой
if ($field === 'stat_baza') {
    $field = 'status_baza';
}

if ($field === 'stat') {
    $field = 'status';
}
if (!is_null($monId) && !is_null($field) && !is_null($value) && in_array($field, $validFields)) {
    $stmt = $connect->prepare("UPDATE montaj SET `$field` = ? WHERE id = ?");
    $stmt->bind_param("ii", $value, $monId);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        
        // Устанавливаем сообщения в зависимости от поля и значения
        if ($field === 'dogovor') {
            $response['message'] = $value ? 'Статус договора успешно обновлен' : 'Заявка удалена из базы';
        } else if ($field === 'status_baza') {
            $response['message'] = $value ? 'Монтаж в обработке базой' : 'Заявка удалена из базы';
        } else if ($field === 'status') {
            $response['message'] = $value ? 'Статус подтверждения успешно обновлен' : 'Статус подтверждения сброшен';
        } else {
            $response['message'] = "Поле '$field' успешно обновлено";
        }
    } else {
        $response['message'] = "Ошибка при обновлении: " . $connect->error;
    }
    $stmt->close();
} else {
    $response['message'] = "Недостаточно данных или неверное поле: monId=$monId, field=$field, value=$value";
}

echo json_encode($response);
?>