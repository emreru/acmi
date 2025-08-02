<?php
global $wpdb;

$acmi_table     = $wpdb->prefix . 'acmi_availabilities';
$aircraft_table = $wpdb->prefix . 'acmi_aircraft_types';

// Total counts
$total_active  = $wpdb->get_var("SELECT COUNT(*) FROM $acmi_table WHERE is_active = 1");
$total_passive = $wpdb->get_var("SELECT COUNT(*) FROM $acmi_table WHERE is_active = 0");

// Aircraft Types (total distinct)
$aircraft_types = $wpdb->get_results("SELECT DISTINCT aircraft_type FROM $aircraft_table ORDER BY aircraft_type ASC");

// Active ACMI aircraft type counts
$active_aircraft_counts = $wpdb->get_results("
    SELECT t.aircraft_type, COUNT(*) as total
    FROM $acmi_table a
    LEFT JOIN $aircraft_table t ON a.aircraft_type_id = t.id
    WHERE a.is_active = 1
    GROUP BY t.aircraft_type
    ORDER BY t.aircraft_type ASC
");

// Inactive ACMI aircraft type counts
$inactive_aircraft_counts = $wpdb->get_results("
    SELECT t.aircraft_type, COUNT(*) as total
    FROM $acmi_table a
    LEFT JOIN $aircraft_table t ON a.aircraft_type_id = t.id
    WHERE a.is_active = 0
    GROUP BY t.aircraft_type
    ORDER BY t.aircraft_type ASC
");

$regions = $wpdb->get_results("SELECT DISTINCT region FROM $acmi_table ORDER BY region ASC");

$last_acmi_active = $wpdb->get_results("
    SELECT a.*, t.aircraft_type 
    FROM $acmi_table a
    LEFT JOIN $aircraft_table t ON a.aircraft_type_id = t.id
    WHERE a.is_active = 1
    ORDER BY a.created_at DESC
    LIMIT 5
");

$last_acmi_inactive = $wpdb->get_results("
    SELECT a.*, t.aircraft_type 
    FROM $acmi_table a
    LEFT JOIN $aircraft_table t ON a.aircraft_type_id = t.id
    WHERE a.is_active = 0
    ORDER BY a.created_at DESC
    LIMIT 5
");
?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8 text-gray-800 flex items-center gap-2"><i class='bi bi-speedometer2 text-3xl'></i> ACMI Dashboard</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="bg-gradient-to-r from-green-400 to-green-500 text-white p-5 rounded-xl shadow-md">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <i class="bi bi-check2-circle text-4xl"></i>
                    <div>
                        <p class="text-sm">Active ACMI</p>
                        <p class="text-2xl font-bold"><?= $total_active ?></p>
                    </div>
                </div>
                <ul class="text-sm mt-2 space-y-1 text-white">
                    <?php foreach ($active_aircraft_counts as $a): ?>
                        <li>• <?= esc_html($a->aircraft_type) ?> (<?= $a->total ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="bg-gradient-to-r from-red-400 to-red-500 text-white p-5 rounded-xl shadow-md">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <i class="bi bi-x-octagon text-4xl"></i>
                    <div>
                        <p class="text-sm">Inactive ACMI</p>
                        <p class="text-2xl font-bold"><?= $total_passive ?></p>
                    </div>
                </div>
                <ul class="text-sm mt-2 space-y-1 text-white">
                    <?php foreach ($inactive_aircraft_counts as $i): ?>
                        <li>• <?= esc_html($i->aircraft_type) ?> (<?= $i->total ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-white p-5 rounded-xl shadow-md">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <i class="bi bi-airplane-engines text-4xl"></i>
                    <div>
                        <p class="text-sm">Aircraft Types</p>
                        <p class="text-2xl font-bold"><?= count($aircraft_types) ?></p>
                    </div>
                </div>
                <ul class="text-sm mt-2 space-y-1 text-white">
                    <?php foreach ($aircraft_types as $t): ?>
                        <li>• <?= esc_html($t->aircraft_type) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="bg-gradient-to-r from-blue-500 to-blue-700 text-white p-5 rounded-xl shadow-md">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <i class="bi bi-globe-americas text-4xl"></i>
                    <div>
                        <p class="text-sm">Region Count</p>
                        <p class="text-2xl font-bold"><?= count($regions) ?></p>
                    </div>
                </div>
                <ul class="text-sm mt-2 space-y-1 text-white">
                    <?php foreach ($regions as $r): ?>
                        <li>• <?= esc_html($r->region) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <h2 class="text-2xl font-semibold text-gray-700 mb-4 flex items-center gap-2"><i class='bi bi-clock-history text-xl'></i> Last 5 Active ACMI</h2>
    <div class="space-y-4 mb-10">
        <?php foreach ($last_acmi_active as $acmi): ?>
        <div class="bg-white shadow-md rounded-lg p-5 flex justify-between items-center hover:shadow-lg transition-all">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= esc_html($acmi->aircraft_type) ?></h3>
                <p class="text-sm text-gray-500">
                    <?= date("d.m.Y", strtotime($acmi->start_date)) ?> → <?= date("d.m.Y", strtotime($acmi->end_date)) ?>
                </p>
                <p class="text-sm text-gray-400">Region: <?= esc_html($acmi->region) ?></p>
            </div>
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-700">Active</span>
        </div>
        <?php endforeach; ?>
    </div>

    <h2 class="text-2xl font-semibold text-gray-700 mb-4 flex items-center gap-2"><i class='bi bi-clock-history text-xl'></i> Last 5 Inactive ACMI</h2>
    <div class="space-y-4">
        <?php foreach ($last_acmi_inactive as $acmi): ?>
        <div class="bg-white shadow-md rounded-lg p-5 flex justify-between items-center hover:shadow-lg transition-all">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= esc_html($acmi->aircraft_type) ?></h3>
                <p class="text-sm text-gray-500">
                    <?= date("d.m.Y", strtotime($acmi->start_date)) ?> → <?= date("d.m.Y", strtotime($acmi->end_date)) ?>
                </p>
                <p class="text-sm text-gray-400">Region: <?= esc_html($acmi->region) ?></p>
            </div>
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-700">Inactive</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
