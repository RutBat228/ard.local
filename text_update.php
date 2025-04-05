<?php
session_start();
include("inc/function.php");

// Устанавливаем заголовок для JSON
header('Content-Type: application/json; charset=utf-8');

AutorizeProtect();
access();

if (!isset($_POST['id']) || !isset($_POST['text'])) {
    echo json_encode(['success' => false, 'message' => 'Отсутствуют необходимые параметры']);
    exit;
}

$id = intval($_POST['id']);
$text = trim($_POST['text']);

global $connect;

// Проверяем существование монтажа и права доступа
$stmt = $connect->prepare("SELECT * FROM `montaj` WHERE `id` = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Монтаж не найден']);
    exit;
}

$montaj = $result->fetch_assoc();

// Проверяем права доступа
if ($montaj['region'] !== $usr['region']) {
    echo json_encode(['success' => false, 'message' => 'Нет прав для изменения этого монтажа']);
    exit;
}

// Обновляем описание
$stmt = $connect->prepare("UPDATE `montaj` SET `text` = ? WHERE `id` = ?");
$stmt->bind_param("si", $text, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Описание успешно обновлено']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении описания']);
}

$stmt->close();
$connect->close();
?>
