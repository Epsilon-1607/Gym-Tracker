<?php
session_start();

$data_file = 'tracker_data.json';

$default_data = [
    'menu' => [
        'Day 1' => ['name' => 'Push Day 1', 'desc' => 'Focus: Chest, Shoulders, Triceps'],
        'Day 2' => ['name' => 'Pull Day 1', 'desc' => 'Focus: Back, Biceps, Rear Delts'],
        'Day 3' => ['name' => 'Leg Day', 'desc' => 'Focus: Quads, Hamstrings, Calves'],
        'Day 4' => ['name' => 'Rest 1', 'desc' => 'Active recovery or complete rest'],
        'Day 5' => ['name' => 'Push Day 2', 'desc' => 'Focus: Chest, Shoulders, Triceps'],
        'Day 6' => ['name' => 'Pull Day 2', 'desc' => 'Focus: Back thickness, Bicep isolation'],
        'Day 7' => ['name' => 'Rest 2', 'desc' => 'Complete rest. Prepare for next week.']
    ],
    'violations' => []
];

if (!file_exists($data_file)) {
    file_put_contents($data_file, json_encode($default_data, JSON_PRETTY_PRINT));
    $app_data = $default_data;
} else {
    $app_data = json_decode(file_get_contents($data_file), true);
    if (!isset($app_data['menu']['Day 1'])) {
        file_put_contents($data_file, json_encode($default_data, JSON_PRETTY_PRINT));
        $app_data = $default_data;
    }
}

if (!isset($app_data['stats'])) {
    $app_data['stats'] = [
        'current_week' => 1,
        'perfect_days' => 0,
        'total_sins' => 0,
        'redeemed_sins' => 0,
        'total_days_logged' => 0
    ];
}

if (!isset($_SESSION['current_day_index'])) {
    $_SESSION['current_day_index'] = 0;
}

$current_index = $_SESSION['current_day_index'];
$day_key = 'Day ' . ($current_index + 1);
$current_day_data = $app_data['menu'][$day_key];
$is_rest_day = strpos(strtolower($current_day_data['name']), 'rest') !== false;
$show_msg = false;

$unresolved_sins = array_filter($app_data['violations'], fn($v) => $v['status'] === 'Unresolved');
$unresolved_count = count($unresolved_sins);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_menu'])) {
        for ($i = 1; $i <= 7; $i++) {
            $key = 'Day ' . $i;
            $app_data['menu'][$key]['name'] = htmlspecialchars($_POST['name_'.$i]);
            $app_data['menu'][$key]['desc'] = htmlspecialchars($_POST['desc_'.$i]);
        }
        file_put_contents($data_file, json_encode($app_data, JSON_PRETTY_PRINT));
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['complete_day'])) {
        $sins_today = 0; 
        if (!$is_rest_day && !isset($_POST['task_workout'])) {
            $app_data['violations'][] = ['id' => uniqid(), 'desc' => "Missed Workout ({$current_day_data['name']})", 'status' => 'Unresolved', 'date' => date('Y-m-d H:i')];
            $sins_today++;
        }
        if (!isset($_POST['task_read'])) {
            $app_data['violations'][] = ['id' => uniqid(), 'desc' => 'Missed Reading Session', 'status' => 'Unresolved', 'date' => date('Y-m-d H:i')];
            $sins_today++;
        }
        if (!isset($_POST['task_habit'])) {
            $app_data['violations'][] = ['id' => uniqid(), 'desc' => 'Missed Daily Habit', 'status' => 'Unresolved', 'date' => date('Y-m-d H:i')];
            $sins_today++;
        }
        if (!isset($_POST['task_getup'])) {
            $app_data['violations'][] = ['id' => uniqid(), 'desc' => 'Missed Early Wakeup', 'status' => 'Unresolved', 'date' => date('Y-m-d H:i')];
            $sins_today++;
        }

        $app_data['stats']['total_days_logged']++;
        if ($sins_today === 0) {
            $app_data['stats']['perfect_days']++;
        } else {
            $app_data['stats']['total_sins'] += $sins_today;
        }

        if ($current_index == 6) {
            $app_data['stats']['current_week']++;
        }

        file_put_contents($data_file, json_encode($app_data, JSON_PRETTY_PRINT));
        $_SESSION['current_day_index'] = ($current_index + 1) % 7;
        $_SESSION['show_msg'] = true; 
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['redeem_sins'])) {
        $punishment_type = $_POST['punishment_type'];
        $redeemed_count = 0;
        foreach ($app_data['violations'] as &$v) {
            if ($v['status'] === 'Unresolved') {
                $v['status'] = 'Resolved';
                $v['resolved_by'] = $punishment_type;
                $v['resolve_date'] = date('Y-m-d H:i');
                $redeemed_count++;
            }
        }
        $app_data['stats']['redeemed_sins'] += $redeemed_count;
        file_put_contents($data_file, json_encode($app_data, JSON_PRETTY_PRINT));
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_SESSION['show_msg']) && $_SESSION['show_msg'] === true) {
    $show_msg = true;
    $_SESSION['show_msg'] = false; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Minimalist Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Inter"', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { background-color: #000; color: #fff; overflow-x: hidden; }
        input[type=checkbox] {
            appearance: none; width: 22px; height: 22px; border: 2px solid #555;
            background-color: transparent; cursor: pointer; position: relative; border-radius: 4px;
        }
        input[type=checkbox]:checked { background-color: #fff; border-color: #fff; }
        input[type=checkbox]:checked::after {
            content: '✓'; position: absolute; color: #000;
            font-size: 16px; left: 4px; top: -2px; font-weight: bold;
        }
        .slide { transform-origin: left center; }
        .flip-in-right { animation: flipInRight 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; }
        .flip-in-left { animation: flipInLeft 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; transform-origin: right center; }
        @keyframes flipInRight { 0% { transform: rotateY(90deg); opacity: 0; } 100% { transform: rotateY(0deg); opacity: 1; } }
        @keyframes flipInLeft { 0% { transform: rotateY(-90deg); opacity: 0; } 100% { transform: rotateY(0deg); opacity: 1; } }
        .main-container { border: 1px solid #333; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #555; border-radius: 2px; }
    </style>
</head>
<body class="min-h-screen py-6 px-3 flex flex-col items-center font-sans antialiased">

    <header class="text-center mb-4 w-full max-w-lg relative">
        <h1 class="text-2xl font-extrabold uppercase tracking-widest text-white">Arc Tracker</h1>
        <button onclick="document.getElementById('editMenuModal').classList.remove('hidden')" class="absolute top-0 right-2 text-[10px] border border-gray-500 text-gray-300 px-3 py-1 hover:bg-white hover:text-black transition rounded">
            EDIT
        </button>
    </header>

    <div id="editMenuModal" class="hidden absolute z-50 top-10 left-1/2 transform -translate-x-1/2 w-[95%] max-w-lg bg-[#111] p-5 border border-gray-700 shadow-2xl text-sm rounded">
        <h3 class="text-lg font-bold text-white mb-3 text-center uppercase tracking-widest">Schedule Setup</h3>
        <form method="POST" class="h-[60vh] overflow-y-auto pr-2 space-y-3">
            <?php for($i=1; $i<=7; $i++): $key = 'Day '.$i; ?>
                <div class="bg-black p-3 border border-gray-800 rounded">
                    <label class="text-gray-300 font-bold mb-1 block text-xs"><?php echo $key; ?> Name:</label>
                    <input type="text" name="name_<?php echo $i; ?>" value="<?php echo htmlspecialchars($app_data['menu'][$key]['name']); ?>" class="w-full bg-transparent border-b border-gray-600 text-white p-1 mb-2 focus:outline-none focus:border-white">
                    <label class="text-gray-500 text-[10px] block mb-1">Description:</label>
                    <textarea name="desc_<?php echo $i; ?>" rows="2" class="w-full bg-transparent border border-gray-700 text-gray-400 p-1 text-xs focus:outline-none focus:border-white"><?php echo htmlspecialchars($app_data['menu'][$key]['desc']); ?></textarea>
                </div>
            <?php endfor; ?>
            <div class="flex justify-end gap-2 mt-4 pb-4">
                <button type="button" onclick="document.getElementById('editMenuModal').classList.add('hidden')" class="px-4 py-2 border border-gray-600 text-gray-400 hover:bg-gray-800 transition">Cancel</button>
                <button type="submit" name="save_menu" class="px-4 py-2 bg-white text-black font-bold hover:bg-gray-200 transition">Save</button>
            </div>
        </form>
    </div>

    <div class="w-full max-w-lg relative min-h-[75vh] bg-[#0a0a0a] main-container rounded">
        
        <div id="page-0" class="slide hidden p-5 h-full flex-col">
            <?php if ($show_msg): ?>
            <div class="bg-[#1a1a1a] border border-gray-500 text-white p-3 mb-4 text-center text-sm rounded">
                Day completed successfully. Progress saved.
            </div>
            <?php endif; ?>

            <div class="text-center border-b border-gray-800 pb-3 mb-4 shrink-0">
                <h2 class="text-xs text-gray-500 tracking-[0.3em] uppercase mb-1">Today's Protocol</h2>
                <div class="text-2xl font-extrabold text-white uppercase"><?php echo $day_key; ?>: <span class="text-gray-400"><?php echo $current_day_data['name']; ?></span></div>
            </div>

            <div class="text-sm text-gray-400 mb-4 border-l-2 border-white pl-3 bg-[#111] p-3 shrink-0">
                <?php echo $current_day_data['desc']; ?>
            </div>

            <form method="POST" onsubmit="sessionStorage.setItem('activePage', 0)" class="flex-1 flex flex-col overflow-hidden">
                <div class="bg-[#111] border border-gray-800 p-4 space-y-4 flex-1 overflow-y-auto rounded">
                    <p class="text-[10px] text-gray-400 uppercase font-bold text-center border-b border-gray-800 pb-2 mb-2">Mandatory Tasks</p>

                    <label class="flex items-start">
                        <input type="checkbox" name="task_getup" class="mt-1 mr-3 shrink-0"> 
                        <div>
                            <span class="block text-gray-200 text-sm">Early Wakeup</span>
                            <span class="text-[10px] text-gray-500 block mt-1">Get up before the sun.</span>
                        </div>
                    </label>
                    <hr class="border-gray-800/50">
                    
                    <?php if (!$is_rest_day): ?>
                    <label class="flex items-start">
                        <input type="checkbox" name="task_workout" class="mt-1 mr-3 shrink-0"> 
                        <div>
                            <span class="block text-gray-200 text-sm">Complete Workout</span>
                            <span class="text-[10px] text-gray-500 block mt-1">Follow the daily schedule.</span>
                        </div>
                    </label>
                    <hr class="border-gray-800/50">
                    <?php endif; ?>

                    <label class="flex items-start">
                        <input type="checkbox" name="task_read" class="mt-1 mr-3 shrink-0"> 
                        <div>
                            <span class="block text-gray-200 text-sm">Reading Session</span>
                            <span class="text-[10px] text-gray-500 block mt-1">10 pages or 15 minutes of non-fiction.</span>
                        </div>
                    </label>
                    <hr class="border-gray-800/50">

                    <label class="flex items-start">
                        <input type="checkbox" name="task_habit" class="mt-1 mr-3 shrink-0"> 
                        <div>
                            <span class="block text-gray-200 text-sm">Daily Habit</span>
                            <span class="text-[10px] text-gray-500 block mt-1">Hydration, grooming, or personal task.</span>
                        </div>
                    </label>
                    <hr class="border-gray-800/50">

                    <label class="flex items-start opacity-50">
                        <input type="checkbox" name="task_optional" class="mt-1 mr-3 shrink-0"> 
                        <div>
                            <span class="block text-gray-400 text-sm">Optional Task</span>
                            <span class="text-[10px] text-gray-600 block mt-1">Will not be logged as a violation if missed.</span>
                        </div>
                    </label>
                </div>

                <button type="submit" name="complete_day" class="w-full bg-[#111] border border-gray-600 text-white py-3 hover:bg-white hover:text-black transition uppercase tracking-widest text-sm font-bold mt-4 shrink-0 rounded">
                    Complete Day
                </button>
            </form>

            <div class="mt-4 flex justify-end border-t border-gray-800 pt-3 shrink-0">
                <button type="button" onclick="changePage(1, 'right')" class="text-xs text-gray-400 hover:text-white transition flex items-center uppercase tracking-wider">
                    Violation Log <span class="text-lg ml-2">➔</span>
                </button>
            </div>
        </div>

        <div id="page-1" class="slide hidden p-5 h-full flex-col bg-[#050505]">
            <div class="text-center border-b border-gray-700 pb-3 mb-4 shrink-0">
                <h2 class="text-xs text-gray-500 tracking-[0.3em] uppercase mb-1">Pending Punishments</h2>
                <div class="text-2xl font-extrabold text-white uppercase">Violation Log</div>
                <div class="mt-1 text-[11px] text-gray-400">Unresolved: <span class="text-black bg-white font-bold px-2 py-0.5 rounded ml-1"><?php echo $unresolved_count; ?></span></div>
            </div>

            <div class="flex-1 overflow-y-auto pr-2 flex flex-col">
                <?php if ($is_rest_day && $unresolved_count > 0): ?>
                    <div class="bg-[#111] border border-white p-4 mb-5 relative shrink-0 rounded">
                        <h3 class="text-sm font-bold text-white mb-1 text-center uppercase tracking-widest">Rest Day Protocol</h3>
                        <form method="POST" onsubmit="sessionStorage.setItem('activePage', 1)" class="space-y-3 text-xs mt-3">
                            <select name="punishment_type" class="w-full bg-black border border-gray-700 text-white p-2 focus:outline-none focus:border-white rounded" required>
                                <option value="" disabled selected>-- Select Resolution --</option>
                                <option value="Physical: 100 Reps">Physical: 100 Reps (Push-ups/Squats)</option>
                                <option value="Diet: Caloric Deficit">Diet: Strict Caloric Deficit</option>
                                <option value="Cardio: 30 Min">Cardio: 30 Min HIIT</option>
                            </select>
                            <button type="submit" name="redeem_sins" class="w-full bg-white text-black font-bold py-2 uppercase tracking-widest hover:bg-gray-300 transition rounded">
                                Execute & Clear Log
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($unresolved_count === 0): ?>
                    <div class="flex-1 flex items-center justify-center flex-col opacity-30 text-center min-h-[150px]">
                        <p class="text-sm uppercase tracking-widest">Log is empty</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3 pb-4">
                        <?php foreach (array_reverse($app_data['violations']) as $v): ?>
                            <?php if ($v['status'] === 'Unresolved'): ?>
                                <div class="relative pl-3 py-1">
                                    <div class="absolute left-0 top-0 bottom-0 w-[2px] bg-white opacity-50"></div>
                                    <p class="text-gray-200 text-sm leading-snug"><?php echo $v['desc']; ?></p>
                                    <p class="text-[10px] text-gray-500 mt-1"><?php echo $v['date']; ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 flex justify-between border-t border-gray-800 pt-3 shrink-0">
                <button type="button" onclick="changePage(0, 'left')" class="text-xs text-gray-400 hover:text-white transition flex items-center uppercase tracking-wider">
                    <span class="text-lg mr-2">⬅</span> Schedule
                </button>
                <button type="button" onclick="changePage(2, 'right')" class="text-xs text-gray-400 hover:text-white transition flex items-center uppercase tracking-wider">
                    Stats <span class="text-lg ml-2">➔</span>
                </button>
            </div>
        </div>

        <div id="page-2" class="slide hidden p-5 h-full flex-col bg-[#0a0a0a]">
            <div class="text-center border-b border-gray-800 pb-3 mb-6 shrink-0">
                <h2 class="text-xs text-gray-500 tracking-[0.3em] uppercase mb-1">Overview</h2>
                <div class="text-xl font-extrabold text-white uppercase">Statistics</div>
            </div>

            <div class="flex-1 flex flex-col justify-center space-y-4">
                <div class="bg-[#111] p-4 border border-gray-800 rounded text-center">
                    <p class="text-gray-500 text-[10px] font-bold uppercase tracking-wider mb-1">Current Week</p>
                    <p class="text-3xl font-extrabold text-white">Week <?php echo $app_data['stats']['current_week']; ?></p>
                </div>
                
                <div class="bg-[#111] p-4 border border-white rounded text-center">
                    <p class="text-white text-[10px] font-bold uppercase tracking-wider mb-1">Flawless Days</p>
                    <p class="text-4xl font-extrabold text-white my-1"><?php echo $app_data['stats']['perfect_days']; ?></p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest">Zero Violations</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-[#111] p-3 border border-gray-700 rounded text-center">
                        <p class="text-gray-500 text-[10px] font-bold uppercase mb-1">Total Violations</p>
                        <p class="text-2xl font-bold text-white"><?php echo $app_data['stats']['total_sins']; ?></p>
                    </div>
                    <div class="bg-[#111] p-3 border border-gray-800 rounded text-center">
                        <p class="text-gray-500 text-[10px] font-bold uppercase mb-1">Redeemed</p>
                        <p class="text-2xl font-bold text-gray-400"><?php echo $app_data['stats']['redeemed_sins']; ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-start border-t border-gray-800 pt-3 shrink-0">
                <button type="button" onclick="changePage(1, 'left')" class="text-xs text-gray-400 hover:text-white transition flex items-center uppercase tracking-wider">
                    <span class="text-lg mr-2">⬅</span> Violation Log
                </button>
            </div>
        </div>

    </div>

    <script>
        function changePage(targetIndex, direction) {
            const pages = document.querySelectorAll('.slide');
            pages.forEach(p => {
                p.classList.add('hidden');
                p.classList.remove('flex', 'flip-in-right', 'flip-in-left');
            });
            const targetPage = document.getElementById('page-' + targetIndex);
            targetPage.classList.remove('hidden');
            targetPage.classList.add('flex');
            if (direction === 'right') {
                targetPage.classList.add('flip-in-right');
            } else if (direction === 'left') {
                targetPage.classList.add('flip-in-left');
            }
            sessionStorage.setItem('activePage', targetIndex);
        }
        document.addEventListener('DOMContentLoaded', () => {
            let savedPage = sessionStorage.getItem('activePage');
            let startIndex = savedPage !== null ? parseInt(savedPage) : 0;
            document.querySelectorAll('.slide').forEach(p => {
                p.classList.add('hidden');
                p.classList.remove('flex');
            });
            let startPage = document.getElementById('page-' + startIndex);
            startPage.classList.remove('hidden');
            startPage.classList.add('flex');
        });
    </script>
</body>
</html>