<?php
// --- WORDPRESS'E UYUMLU HALE GETİRİLMİŞ PHP MANTIĞI ---
global $wpdb;
$table_availabilities = $wpdb->prefix . 'acmi_availabilities';
$table_aircraft_types = $wpdb->prefix . 'acmi_aircraft_types';
$acmi_item = null;
$message = '';

// 1. Düzenlenecek Kaydı Getir
if (isset($_GET['edit_id'])) {
    $item_id = intval($_GET['edit_id']);
    // DİKKAT: Sorguda 'id' yerine 'a.id' kullandık, çünkü 'aircraft_type_id' alanı da 'id' olarak gelebilir.
    $acmi_item = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, t.aircraft_type 
         FROM {$table_availabilities} a 
         LEFT JOIN {$table_aircraft_types} t ON a.aircraft_type_id = t.id 
         WHERE a.id = %d", 
        $item_id
    ));
} else {
    // ID yoksa, işlemi durdur.
    // wp_die() WordPress'in standart hata gösterme fonksiyonudur.
    wp_die('Düzenlenecek bir ACMI kaydı belirtilmedi.');
}

// 2. Form Gönderildiyse Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_acmi'])) {
    $wpdb->update($table_availabilities, [
        'start_date'        => sanitize_text_field($_POST['start_date']),
        'end_date'          => sanitize_text_field($_POST['end_date']),
        'body_type'         => sanitize_text_field($_POST['body_type']),
        'aircraft_type_id'  => intval($_POST['aircraft_type_id']),
        'region'            => sanitize_text_field($_POST['region']),
        'approvals'         => isset($_POST['approvals']) ? implode(', ', array_map('sanitize_text_field', $_POST['approvals'])) : '',
        'is_active'         => isset($_POST['is_active']) ? 1 : 0,
    ], ['id' => $item_id]);

    // Başarı mesajı göster ve sayfayı yenilemek yerine listeleme sayfasına yönlendir.
    // wp_safe_redirect() WordPress'in güvenli yönlendirme fonksiyonudur.
    $list_page_url = admin_url('admin.php?page=acmi-acmi-list');
    wp_safe_redirect($list_page_url);
    exit;
}

?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="wrap bg-slate-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-4xl">
        
        <div class="flex items-center gap-3 mb-6">
            <i class="bi bi-pencil-square text-3xl text-slate-700"></i>
            <h1 class="text-3xl font-bold text-slate-800">Edit ACMI Record #<?= esc_html($acmi_item->id); ?></h1>
        </div>
        
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
            <?php if ($acmi_item): ?>
                <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-8">
                    
                    <input type="hidden" name="update_acmi" value="1">
                    
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="<?= esc_attr($acmi_item->start_date); ?>" required>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="<?= esc_attr($acmi_item->end_date); ?>" required>
                    </div>

                    <div>
                        <label for="body_type" class="block text-sm font-medium text-gray-700 mb-1">Body Type</label>
                        <select name="body_type" id="body_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="Narrowbody" <?= selected($acmi_item->body_type, 'Narrowbody', false); ?>>Narrowbody</option>
                            <option value="Widebody" <?= selected($acmi_item->body_type, 'Widebody', false); ?>>Widebody</option>
                        </select>
                    </div>

                    <div>
                        <label for="aircraft_type" class="block text-sm font-medium text-gray-700 mb-1">Aircraft Type</label>
                        <select name="aircraft_type_id" id="aircraft_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select an aircraft</option>
                            <?php
                                $types = $wpdb->get_results("SELECT id, aircraft_type, body_type FROM {$table_aircraft_types}");
                                foreach ($types as $type) {
                                    $is_selected = selected($acmi_item->aircraft_type_id, $type->id, false);
                                    echo "<option value='{$type->id}' data-body='{$type->body_type}' {$is_selected}>{$type->aircraft_type}</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                        <select name="region" id="region" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <?php 
                                $regions = ["Europe", "Asia", "Africa", "America", "Middle East", "Oceania"];
                                foreach ($regions as $region) {
                                    echo "<option value='{$region}' ".selected($acmi_item->region, $region, false).">{$region}</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Approvals</label>
                        <div class="flex flex-wrap gap-x-4 gap-y-2">
                            <?php
                                $all_approvals = ["IOSA", "EU", "UK", "TCO", "EASA"];
                                $selected_approvals = array_map('trim', explode(',', $acmi_item->approvals));
                                foreach ($all_approvals as $approval):
                                    $checked = in_array($approval, $selected_approvals) ? 'checked' : '';
                            ?>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="approvals[]" value="<?= $approval ?>" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" <?= $checked ?>>
                                    <span class="ml-2 text-sm text-gray-600"><?= $approval ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="md:col-span-2 flex justify-between items-center pt-4">
                        <div>
                            <label for="is_active" class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" id="is_active" class="sr-only peer" <?= checked($acmi_item->is_active, 1, false); ?>>
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-indigo-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                <span id="status_label" class="ml-3 text-sm font-semibold text-gray-700">Active</span>
                            </label>
                        </div>
                        
                        <div class="flex items-center gap-x-3">
                            <a href="<?= admin_url('admin.php?page=acmi-acmi-list'); ?>" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Record
                            </button>
                        </div>
                    </div>

                </form>
            <?php else: ?>
                <p class="text-red-600">ACMI record could not be found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Body Type'a göre Aircraft Type seçeneklerini filtreleme
            const bodyTypeSelect = document.querySelector('select[name="body_type"]');
            const aircraftTypeSelect = document.querySelector('#aircraft_type');
            const aircraftOptions = Array.from(aircraftTypeSelect.options);

            function updateAircraftOptions(bodyType, isInitialLoad = false) {
                const currentAircraftId = isInitialLoad ? '<?= $acmi_item->aircraft_type_id ?? '' ?>' : '';
                
                // Mevcut seçenekleri temizle (ilk "Select an aircraft" seçeneği hariç)
                while(aircraftTypeSelect.options.length > 1) {
                    aircraftTypeSelect.remove(1);
                }
                
                // Uygun seçenekleri yeniden ekle
                aircraftOptions.forEach(opt => {
                    if (opt.value && opt.dataset.body === bodyType) {
                        aircraftTypeSelect.add(opt);
                    }
                });

                // Sayfa ilk yüklendiğinde veritabanındaki değeri seçili hale getir.
                // Diğer durumlarda seçimi sıfırla.
                aircraftTypeSelect.value = isInitialLoad ? currentAircraftId : ""; 
            }

            bodyTypeSelect.addEventListener("change", function () {
                updateAircraftOptions(this.value, false);
            });

            // Sayfa yüklendiğinde ilk filtrelemeyi yap ve doğru uçağı seçili bırak
            updateAircraftOptions(bodyTypeSelect.value, true);
        });
    </script>
</div>