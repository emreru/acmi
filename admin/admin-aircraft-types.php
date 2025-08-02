<?php
// --- PHP MANTIĞI (DEĞİŞTİRİLMEDİ) ---
global $wpdb;
$table_name = $wpdb->prefix . 'acmi_aircraft_types';

// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_aircraft_type'])) {
    $wpdb->insert($table_name, [
        'body_type'     => sanitize_text_field($_POST['body_type']),
        'aircraft_type' => sanitize_text_field($_POST['aircraft_type']),
        'configuration' => sanitize_text_field($_POST['configuration']),
        'image_url'     => esc_url_raw($_POST['image_url']),
        'created_at'    => current_time('mysql')
    ]);
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $wpdb->delete($table_name, ['id' => intval($_GET['delete'])]);
}

// Fetch all aircraft types
$aircrafts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC");
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

<div class="wrap bg-gray-100 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl">
        
        <div class="flex items-center gap-3 mb-8">
            <i class="bi bi-airplane text-3xl text-slate-700"></i>
            <h1 class="text-3xl font-bold text-slate-800">Aircraft Types</h1>
        </div>

   <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h2 class="text-xl font-semibold text-gray-700 mb-5">Add New Aircraft Type</h2>
    <form method="post" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-6 items-end">
        
        <div class="lg:col-span-2">
            <label for="body_type" class="block text-sm font-medium text-gray-600 mb-1">Body Type</label>
            <select name="body_type" id="body_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="Narrowbody">Narrowbody</option>
                <option value="Widebody">Widebody</option>
            </select>
        </div>

        <div class="lg:col-span-3">
            <label for="aircraft_type" class="block text-sm font-medium text-gray-600 mb-1">Aircraft Type</label>
            <input type="text" name="aircraft_type" id="aircraft_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>

        <div class="lg:col-span-2">
            <label for="configuration" class="block text-sm font-medium text-gray-600 mb-1">Configuration</label>
            <input type="text" name="configuration" id="configuration" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        
        <div class="lg:col-span-4">
            <label for="image_url" class="block text-sm font-medium text-gray-600 mb-1">Aircraft Image</label>
            <div class="flex">
                <input type="text" name="image_url" id="image_url" class="flex-grow w-full rounded-l-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Image URL">
                <button type="button" onclick="uploadImage()" class="inline-flex items-center px-4 bg-gray-200 text-sm font-medium text-gray-700 border border-l-0 border-gray-300 rounded-r-md hover:bg-gray-300">
                    Browse
                </button>
            </div>
        </div>

        <div class="lg:col-span-1">
            <button type="submit" name="add_aircraft_type" class="w-full bg-indigo-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150">
                Add
            </button>
        </div>
    </form>
</div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-white uppercase bg-slate-700">
                        <tr>
                            <th scope="col" class="px-6 py-3">ID</th>
                            <th scope="col" class="px-6 py-3">Body Type</th>
                            <th scope="col" class="px-6 py-3">Aircraft Type</th>
                            <th scope="col" class="px-6 py-3">Configuration</th>
                            <th scope="col" class="px-6 py-3">Image</th>
                            <th scope="col" class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($aircrafts)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-gray-500">
                                    No aircraft types found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($aircrafts as $aircraft): ?>
                                <tr class="bg-white border-b hover:bg-gray-50 align-middle">
                                    <td class="px-6 py-4 font-medium text-gray-900"><?= $aircraft->id ?></td>
                                    <td class="px-6 py-4"><span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?= esc_html($aircraft->body_type) ?></span></td>
                                    <td class="px-6 py-4 font-semibold"><?= esc_html($aircraft->aircraft_type) ?></td>
                                    <td class="px-6 py-4"><?= esc_html($aircraft->configuration) ?></td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($aircraft->image_url)): ?>
                                            <img src="<?= esc_url($aircraft->image_url) ?>" alt="Aircraft Image" class="w-20 h-auto rounded-md shadow-sm">
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="?page=acmi-aircraft-types&delete=<?= $aircraft->id ?>" 
                                           class="font-medium text-red-600 hover:text-red-800 hover:underline" 
                                           onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
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

<script>
// --- JAVASCRIPT (DEĞİŞTİRİLMEDİ) ---
function uploadImage() {
    // Bu fonksiyon WordPress Media Uploader'ı kullandığı için
    // WordPress admin panelinde çalışması gerekir ve değişikliğe ihtiyaç duymaz.
    wp.media.editor.send.attachment = function(props, attachment) {
        document.getElementById("image_url").value = attachment.url;
    };
    wp.media.editor.open();
}
</script>