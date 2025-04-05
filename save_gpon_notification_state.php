<?php
session_start();

// Получаем ID монтажа и состояние
$mon_id = isset($_POST['mon_id']) ? intval($_POST['mon_id']) : 0;
$state = isset($_POST['state']) ? $_POST['state'] : '';

if ($mon_id > 0 && $state === 'dismissed') {
    // Сохраняем состояние в сессии
    $_SESSION["gpon_notification_$mon_id"] = $state;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
} 