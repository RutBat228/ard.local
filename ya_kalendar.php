<?php
session_start();
ob_start();
include("inc/function.php");
echo '<!doctype html><html lang="ru">';
include("inc/style.php");

// Отладочный лог для PHP
error_log("PHP: Начало обработки ya_kalendar.php");

// Проверка функций авторизации
AutorizeProtect();
error_log("PHP: После AutorizeProtect()");
access();
error_log("PHP: После access()");

global $connect;
global $usr;

$current_year  = date('Y');
$current_month = date('m');

if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}$/', $_GET['date'])) {
    error_log("PHP: Проверка параметра date: " . $_GET['date']);
    $date_parts = explode('-', $_GET['date']);
    $display_year = (int)$date_parts[0];
    $month_num = $date_parts[1];
    $month = date_view($_GET['date']);
    $date_blyat = $_GET['date'];
    error_log("PHP: Параметр date корректен: " . $date_blyat . ", display_year: " . $display_year);
} else {
    if (isset($_GET['date'])) {
        error_log("PHP: Параметр date некорректен, перенаправление на текущий месяц");
        if (!isset($_GET['redirected'])) {
            header("Location: ya_kalendar.php?date=" . date("Y-m") . "&redirected=1");
            exit();
        }
    }
    $display_year = isset($_GET['older']) ? $current_year - 1 : $current_year;
    $month = month_view(date('m'));
    $date_blyat = $display_year . '-' . date('m');
    error_log("PHP: Параметр date не указан или некорректен, используется: display_year=" . $display_year . ", date_blyat=" . $date_blyat);
}

// Получаем всех пользователей из региона текущего пользователя
$stmt = $connect->prepare("SELECT id, fio, inicial FROM `user` WHERE `region` = ? ORDER BY `fio`");
$stmt->bind_param('s', $usr['region']);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[$row['id']] = [
        'fio' => $row['fio'],
        'inicial' => $row['inicial'] ?? ''
    ];
}

// Получаем дежурства для всех пользователей за выбранный месяц
$duties = [];
$stmt = $connect->prepare("SELECT user_id, duty_date FROM duty_days WHERE duty_date LIKE ?");
$month_pattern = $date_blyat . '-%';
$stmt->bind_param('s', $month_pattern);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $date = $row['duty_date'];
    $user_id = $row['user_id'];
    if (!isset($duties[$date])) {
        $duties[$date] = [];
    }
    if (isset($users[$user_id])) {
        $duties[$date][] = $users[$user_id];
    }
}

// Получаем праздничные дни
$holidays = [];
$stmt = $connect->prepare("SELECT holiday_date FROM holidays WHERE holiday_date LIKE ?");
$stmt->bind_param('s', $month_pattern);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $holidays[] = $row['holiday_date'];
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
    <title>Общий календарь дежурств - <?=$month?> <?=$display_year?></title>
</head>

<body style="background: #ffffff url(img/background.webp) repeat;">
<div class="container-sm">
    <nav class="navbar navbar-expand-lg navbar-dark" style="border-radius: 0!important; padding-bottom: 0; background: #ffffff url(img/background.webp) repeat;">
        <div class="container" style="display: initial;">
            <div class="row">
                <div class="col-12">
                    <a class="navbar-brand" href="/index.php">
                        <img id="animated-example" class="mt-2 pidaras animated fadeOut" src="img/logo.webp?12w" alt="ArdMoney" height="90px">
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main role="main" style="padding-bottom: 60px">
        <div style="min-height: calc(100vh - 9rem); padding: 0 0; background: #fff;" class="jumbotron">
            <div class="col-md-12 col-sm-12 mx-auto">
                <h2 class="text-center mb-4">Общий календарь дежурств</h2>
                
                <div class="month-nav">
                    <?php
                    $prevMonth = date('Y-m', strtotime("$date_blyat -1 month"));
                    $nextMonth = date('Y-m', strtotime("$date_blyat +1 month"));
                    ?>
                    <button onclick="window.location.href='?date=<?=$prevMonth?>'"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="calendar-wrapper">
                        <div class="month-year" onclick="toggleCalendar(event)">
                            <?=$month?> <?=$display_year?>
                        </div>
                        <div class="calendar-container" id="calendarContainer" style="display: none;">
                            <div class="month-grid">
                                <?php
                                $months = [
                                    '01' => 'Январь', '02' => 'Февраль', '03' => 'Март',
                                    '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь',
                                    '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь',
                                    '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
                                ];
                                foreach ($months as $mnum => $mname): ?>
                                    <button class="month-btn" data-month="<?=$mnum?>"><?=$mname?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($nextMonth <= date('Y-m')): ?>
                        <button onclick="window.location.href='?date=<?=$nextMonth?>'"><i class="fa-solid fa-arrow-right"></i></button>
                    <?php else: ?>
                        <button disabled><i class="fa-solid fa-arrow-right"></i></button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered calendar-table">
                        <thead>
                            <tr>
                                <th style="width: 14.28%">Пн</th>
                                <th style="width: 14.28%">Вт</th>
                                <th style="width: 14.28%">Ср</th>
                                <th style="width: 14.28%">Чт</th>
                                <th style="width: 14.28%">Пт</th>
                                <th style="width: 14.28%">Сб</th>
                                <th style="width: 14.28%">Вс</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Определяем первый день месяца
                            $firstDay = new DateTime("$display_year-$month_num-01");
                            $firstDayIndex = $firstDay->format('N') - 1; // 0 = Понедельник, 6 = Воскресенье
                            
                            // Определяем количество дней в месяце
                            $lastDay = $firstDay->format('t');
                            
                            // Создаем календарь
                            $day = 1;
                            $week = 0;
                            
                            // Максимум 6 недель в месяце
                            for ($week = 0; $week < 6; $week++) {
                                echo "<tr>";
                                
                                // Заполняем дни недели
                                for ($i = 0; $i < 7; $i++) {
                                    if (($week == 0 && $i < $firstDayIndex) || $day > $lastDay) {
                                        // Пустая ячейка
                                        echo "<td class='empty-day'></td>";
                                    } else {
                                        // Формируем дату в формате YYYY-MM-DD
                                        $date = sprintf("%s-%s-%02d", $display_year, $month_num, $day);
                                        
                                        // Проверяем, является ли день выходным
                                        $isWeekend = ($i >= 5);
                                        
                                        // Проверяем, является ли день праздничным
                                        $isHoliday = in_array($date, $holidays);
                                        
                                        // Получаем список дежурных на этот день
                                        $dutyUsers = isset($duties[$date]) ? $duties[$date] : [];
                                        
                                        // Формируем классы для ячейки
                                        $classes = ['calendar-day'];
                                        if ($isWeekend) $classes[] = 'weekend';
                                        if ($isHoliday) $classes[] = 'holiday';
                                        if (empty($dutyUsers)) $classes[] = 'no-duty';
                                        
                                        echo "<td class='" . implode(' ', $classes) . "'>";
                                        echo "<div class='day-number'>$day</div>";
                                        
                                        if (!empty($dutyUsers)) {
                                            echo "<div class='duty-users'>";
                                            
                                            // Проверяем, является ли текущий пользователь из региона 10 (Москольцо)
                                            if ($usr['region'] == '10 - Москольцо') {
                                                // Для региона 10 отображаем все inicial в одной строке
                                                $inicials = [];
                                                foreach ($dutyUsers as $user) {
                                                    if (!empty($user['inicial'])) {
                                                        $inicials[] = $user['inicial'];
                                                    }
                                                }
                                                
                                                if (!empty($inicials)) {
                                                    echo "<span class='duty-user'>" . implode(', ', $inicials) . "</span>";
                                                } else {
                                                    // Если inicial пустые, отображаем фамилии как обычно
                                                    foreach ($dutyUsers as $user) {
                                                        echo "<span class='duty-user' title='" . $users[$user]['fio'] . "'>" . $users[$user]['fio'] . "</span>";
                                                    }
                                                }
                                            } else {
                                                // Для других регионов отображаем фамилии как обычно
                                                foreach ($dutyUsers as $user) {
                                                    echo "<span class='duty-user' title='" . $users[$user]['fio'] . "'>" . $users[$user]['fio'] . "</span>";
                                                }
                                            }
                                            
                                            echo "</div>";
                                        }
                                        
                                        echo "</td>";
                                        $day++;
                                    }
                                }
                                
                                echo "</tr>";
                                
                                // Если мы прошли все дни месяца, выходим из цикла
                                if ($day > $lastDay) {
                                    break;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="legend mt-3">
                    <div class="legend-item">
                        <span class="legend-color weekend"></span>
                        <span class="legend-text">Выходной</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color holiday"></span>
                        <span class="legend-text">Праздничный день</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color no-duty"></span>
                        <span class="legend-text">Нет дежурных</span>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="user.php" class="btn btn-outline-primary">
                        <i class="fa-solid fa-arrow-left me-2"></i> Вернуться на страницу пользователя
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .month-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        background: #30352d;
        color: white;
        padding: 5px 10px;
        border-radius: 0;
    }
    .month-nav button {
        background: #495057;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 15px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    .month-nav button:hover {
        background: #6c757d;
        transform: scale(1.05);
    }
    .month-nav button:disabled {
        background: #6c757d;
        opacity: 0.5;
        cursor: not-allowed;
    }
    .month-year {
        font-size: 1rem;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 5px;
        transition: background 0.3s ease;
    }
    .month-year:hover {
        background: #495057;
    }
    .calendar-wrapper {
        position: relative;
    }
    .calendar-container {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 10px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        z-index: 1000;
        width: 250px;
    }
    .month-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
    }
    .month-btn {
        padding: 5px;
        background: #e9ecef;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }
    .month-btn:hover {
        background: #d1d5db;
    }
    .month-btn:disabled {
        background: #6c757d;
        opacity: 0.5;
        cursor: not-allowed;
    }
    .calendar-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .calendar-table th {
        text-align: center;
        background-color: #f8f9fa;
        padding: 5px;
        font-size: 0.8rem;
    }
    .calendar-table td {
        height: 120px;
        vertical-align: top;
        padding: 3px;
        position: relative;
        font-size: 0.8rem;
    }
    .empty-day {
        background-color: #f8f9fa;
    }
    .day-number {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .weekend {
        background-color: #f8f9fa;
    }
    .holiday {
        background-color: #fff3cd;
    }
    .no-duty {
        background-color: #f8f9fa;
    }
    .duty-users {
        display: flex;
        flex-direction: column;
        gap: 2px;
        max-height: 100px;
        overflow-y: auto;
    }
    .duty-user {
        background-color: #28a745;
        color: white;
        padding: 2px 3px;
        border-radius: 3px;
        font-size: 0.75rem;
        white-space: normal;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        margin-bottom: 2px;
        line-height: 1.2;
    }
    .duty-user:hover {
        background-color: #218838;
    }
    .legend {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .legend-color {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 3px;
    }
    .legend-color.weekend {
        background-color: #f8f9fa;
    }
    .legend-color.holiday {
        background-color: #fff3cd;
    }
    .legend-color.no-duty {
        background-color: #f8f9fa;
    }
    
    .abbreviations {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }
    
    .abbreviations-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
        margin-top: 10px;
    }
    
    .abbreviation-item {
        background-color: #fff;
        padding: 5px 10px;
        border-radius: 3px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .abbreviation-short {
        font-weight: bold;
        color: #28a745;
    }
    
    .abbreviation-full {
        color: #495057;
    }
    
    /* Адаптивные стили для мобильных устройств */
    @media (max-width: 768px) {
        .calendar-table td {
            height: 100px;
            font-size: 0.75rem;
            padding: 2px;
        }
        
        .duty-user {
            font-size: 0.65rem;
            padding: 1px 2px;
            line-height: 1.1;
        }
        
        .day-number {
            font-size: 0.8rem;
            margin-bottom: 2px;
        }
        
        .calendar-table th {
            font-size: 0.75rem;
            padding: 3px;
        }
        
        .month-year {
            font-size: 0.9rem;
        }
        
        .container-sm {
            padding-left: 5px;
            padding-right: 5px;
        }
        
        .table-responsive {
            margin-left: -5px;
            margin-right: -5px;
        }
        
        .duty-users {
            max-height: 80px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Страница загружена");

    const calendarContainer = document.getElementById('calendarContainer');
    const monthButtons = document.querySelectorAll('.month-btn');

    const fixedYear = <?php echo $display_year; ?>;
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1;

    window.toggleCalendar = function(event) {
        if (event) event.stopPropagation();
        console.log("Тоггл календаря");
        calendarContainer.style.display = (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') ? 'block' : 'none';
    };

    document.addEventListener('click', function(event) {
        const calendarWrapper = document.querySelector('.calendar-wrapper');
        if (!calendarWrapper.contains(event.target)) {
            console.log("Клик вне календаря");
            calendarContainer.style.display = 'none';
        }
    });

    monthButtons.forEach(button => {
        const monthNum = parseInt(button.getAttribute('data-month'));
        if (fixedYear > currentYear || (fixedYear === currentYear && monthNum > currentMonth)) {
            button.disabled = true;
        }
    });

    monthButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation();
            console.log("Выбор месяца");
            const selectedMonth = this.getAttribute('data-month');
            if (selectedMonth) {
                console.log(`Перенаправление на ?date=${fixedYear}-${selectedMonth}`);
                window.location.href = `?date=${fixedYear}-${selectedMonth}`;
            }
            calendarContainer.style.display = 'none';
        });
    });
});
</script>

<?php include 'inc/foot.php'; ?>
</body>
</html> 