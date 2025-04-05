<?php
session_start();

// Получаем ID монтажа
$mon_id = isset($_POST['mon_id']) ? intval($_POST['mon_id']) : 0;

if ($mon_id > 0) {
    // Проверяем состояние в сессии
    $dismissed = isset($_SESSION["gpon_notification_$mon_id"]) && $_SESSION["gpon_notification_$mon_id"] === 'dismissed';
    echo json_encode(['dismissed' => $dismissed]);
} else {
    echo json_encode(['error' => 'Invalid parameters', 'dismissed' => false]);
} 