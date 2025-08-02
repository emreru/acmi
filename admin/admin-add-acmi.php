<?php
// --- PHP FORM İŞLEME MANTIĞI (DEĞİŞTİRİLMEDİ) ---
if (isset($_POST['add_acmi'])) {
    global $wpdb;
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    $body_type = sanitize_text_field($_POST['body_type']);
    $aircraft_type_id = intval($_POST['aircraft_type_id']);
    $region = sanitize_text_field($_POST['region']);
    $approvals = isset($_POST['approvals']) ? implode(', ', array_map('sanitize_text_field', $_POST['approvals'])) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $table = $wpdb->prefix . 'acmi_availabilities';
    $wpdb->insert($table, [
        'start_date' => $start_date,
        'end_date' => $end_date,
        'body_type' => $body_type,
        'aircraft_type_id' => $aircraft_type_id,
        'region' => $region,
        'approvals' => $approvals,
        'is_active' => $is_active
    ]);

    // Başarı mesajını göstermek için bir işaretçi ayarla
    $show_success_message = true;
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="wrap bg-slate-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-4xl">
        
        <div class="flex items-center gap-3 mb-6">
            <i class="bi bi-plus-circle text-3xl text-slate-700"></i>
            <h1 class="text-3xl font-bold text-slate-800">Add ACMI Availability</h1>
        </div>

        <?php if (isset($show_success_message) && $show_success_message): ?>
            <div class='border-l-4 border-green-600 bg-green-50 text-green-800 p-4 rounded-md shadow-sm mb-6' role="alert">
                <p class="font-semibold">Success!</p>
                <p>ACMI availability has been successfully added.</p>
            </div>
        <?php endif; ?>
        
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
            <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-8">
                
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="body_type" class="block text-sm font-medium text-gray-700 mb-1">Body Type</label>
                    <select name="body_type" id="body_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="Narrowbody">Narrowbody</option>
                        <option value="Widebody">Widebody</option>
                    </select>
                </div>

                <div>
                    <label for="aircraft_type" class="block text-sm font-medium text-gray-700 mb-1">Aircraft Type</label>
                    <select name="aircraft_type_id" id="aircraft_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Select an aircraft</option>
                        <?php
                            global $wpdb;
                            $types = $wpdb->get_results("SELECT id, aircraft_type, body_type FROM {$wpdb->prefix}acmi_aircraft_types");
                            foreach ($types as $type) {
                                echo "<option value='{$type->id}' data-body='{$type->body_type}'>{$type->aircraft_type}</option>";
                            }
                        ?>
                    </select>
                </div>

                <div>
                    <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <select name="region" id="region" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="Europe">Europe</option>
                        <option value="Asia">Asia</option>
                        <option value="Africa">Africa</option>
                        <option value="America">America</option>
                        <option value="Middle East">Middle East</option>
                        <option value="Oceania">Oceania</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Approvals</label>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        <?php foreach (["IOSA", "EU", "UK", "TCO", "EASA"] as $approval): ?>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="approvals[]" value="<?= $approval ?>" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600"><?= $approval ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="md:col-span-2 grid grid-cols-2 gap-6 items-center">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Active Status</label>
                        <label for="is_active" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" id="is_active" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-indigo-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            <span id="status_label" class="ml-3 text-sm font-semibold text-green-600">Active</span>
                        </label>
                    </div>

                    <div class="text-right">
                         <button type="submit" name="add_acmi" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Add ACMI
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Body Type'a göre Aircraft Type seçeneklerini filtreleme
            const bodyTypeSelect = document.querySelector('select[name="body_type"]');
            const aircraftTypeSelect = document.querySelector('#aircraft_type');
            const aircraftOptions = Array.from(aircraftTypeSelect.options);

            function updateAircraftOptions(bodyType) {
                // Mevcut seçenekleri temizle (ilk seçenek hariç)
                while(aircraftTypeSelect.options.length > 1) {
                    aircraftTypeSelect.remove(1);
                }
                
                // Uygun seçenekleri ekle
                aircraftOptions.forEach(opt => {
                    if (opt.value && opt.dataset.body === bodyType) {
                        aircraftTypeSelect.add(opt);
                    }
                });
                
                aircraftTypeSelect.value = ""; // Seçimi sıfırla
            }

            bodyTypeSelect.addEventListener("change", function () {
                updateAircraftOptions(this.value);
            });

            // Sayfa yüklendiğinde ilk filtrelemeyi yap
            updateAircraftOptions(bodyTypeSelect.value);

            // Switch (toggle) durumuna göre etiket rengini ve metnini değiştirme
            const switchInput = document.getElementById("is_active");
            const statusLabel = document.getElementById("status_label");

            switchInput.addEventListener("change", function () {
                if (this.checked) {
                    statusLabel.textContent = "Active";
                    statusLabel.style.color = "#16a34a"; // Tailwind green-600
                } else {
                    statusLabel.textContent = "Passive";
                    statusLabel.style.color = "#dc2626"; // Tailwind red-600
                }
            });
        });
    </script>
</div>