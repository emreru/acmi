<?php
// --- PHP MANTIĞI (DEĞİŞTİRİLMEDİ) ---
global $wpdb;
$table_acmi     = $wpdb->prefix . 'acmi_availabilities';
$table_aircraft = $wpdb->prefix . 'acmi_aircraft_types';

$current_date = current_time('Y-m-d');

// Silme işlemi
if (isset($_GET['delete_acmi'])) {
    $wpdb->delete($table_acmi, ['id' => intval($_GET['delete_acmi'])]);
    echo '<div class="notice notice-success is-dismissible"><p>ACMI kaydı başarıyla silindi.</p></div>';
}

// Aktif/pasif toggle
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $current = $wpdb->get_var("SELECT is_active FROM $table_acmi WHERE id = $id");
    $wpdb->update($table_acmi, ['is_active' => !$current], ['id' => $id]);
    echo '<div class="notice notice-info is-dismissible"><p>Durum başarıyla güncellendi.</p></div>';
}

// Süresi dolmuş kayıtları otomatik pasif yap
$expired_records = $wpdb->get_results("
    SELECT id 
    FROM $table_acmi 
    WHERE is_active = 1 AND end_date < '$current_date'
");

if (!empty($expired_records)) {
    foreach ($expired_records as $record) {
        $wpdb->update($table_acmi, ['is_active' => 0], ['id' => $record->id]);
    }
}

// ACMI + uçak tipi bilgilerini çek
$rows = $wpdb->get_results("
    SELECT a.*, t.aircraft_type, t.configuration 
    FROM $table_acmi a
    LEFT JOIN $table_aircraft t ON a.aircraft_type_id = t.id
    ORDER BY a.created_at DESC
");
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="wrap bg-slate-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl">
        
        <div class="flex items-center gap-3 mb-6">
            <i class="bi bi-list-ul text-3xl text-slate-700"></i>
            <h1 class="text-3xl font-bold text-slate-800">ACMI Availability List</h1>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-white uppercase bg-slate-700 tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-4">ID</th>
                            <th scope="col" class="px-6 py-4">Aircraft</th>
                            <th scope="col" class="px-6 py-4">Body</th>
                            <th scope="col" class="px-6 py-4">Config</th>
                            <th scope="col" class="px-6 py-4">Available Dates</th>
                            <th scope="col" class="px-6 py-4">Region</th>
                            <th scope="col" class="px-6 py-4">Approvals</th>
                            <th scope="col" class="px-6 py-4 text-center">Status</th>
                            <th scope="col" class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-10 text-gray-500">
                                    No ACMI records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): 
                                $is_expired = strtotime($r->end_date) < strtotime($current_date);
                                if ($r->is_active && !$is_expired) {
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text  = 'Active';
                                } elseif ($is_expired) {
                                    $status_class = 'bg-gray-200 text-gray-800';
                                    $status_text  = 'Expired';
                                } else {
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text  = 'Passive';
                                }
                            ?>
                                <tr class="bg-white border-b hover:bg-gray-50/50 transition-colors duration-150">
                                    <td class="px-6 py-4 font-mono text-gray-800">#<?= esc_html($r->id) ?></td>
                                    <td class="px-6 py-4 font-semibold text-gray-900"><?= esc_html($r->aircraft_type) ?></td>
                                    <td class="px-6 py-4"><?= esc_html($r->body_type) ?></td>
                                    <td class="px-6 py-4"><?= esc_html($r->configuration) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?= date('d.m.Y', strtotime($r->start_date)) ?> - <?= date('d.m.Y', strtotime($r->end_date)) ?>
                                    </td>
                                    <td class="px-6 py-4"><?= esc_html($r->region) ?></td>
                                    <td class="px-6 py-4 text-xs"><?= esc_html($r->approvals) ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-x-5">
                                            <a href="?page=acmi-acmi-list&toggle_status=<?= esc_attr($r->id) ?>" title="Toggle Status" class="text-gray-500 hover:text-indigo-600 transition-colors">
                                                <i class="bi bi-arrow-repeat text-lg"></i>
                                            </a>
                                            <a href="<?= admin_url('admin.php?page=acmi-edit-acmi&edit_id=' . $r->id) ?>" title="Edit" class="text-gray-500 hover:text-blue-600 transition-colors">
                                                 <i class="bi bi-pencil-square text-lg"></i>
                                            </a>
                                            <a href="?page=acmi-acmi-list&delete_acmi=<?= esc_attr($r->id) ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')" class="text-gray-500 hover:text-red-600 transition-colors">
                                                <i class="bi bi-trash3-fill text-lg"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>