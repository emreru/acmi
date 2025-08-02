<?php
global $wpdb;
$table_name = $wpdb->prefix . 'acmi_aog_contacts';
$message = '';
$message_type = '';

// Ekleme, Silme, CSV Import ve Mesaj Yönetimi PHP kodları
if (isset($_POST['add_contact']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'add_new_aog_contact')) {
    $name = sanitize_text_field($_POST['contact_name']);
    $email = sanitize_email($_POST['contact_email']);
    if (empty($name) || !is_email($email)) {
        wp_safe_redirect(admin_url('admin.php?page=acmi-aog-contacts&message=4'));
        exit;
    }
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s", $email));
    if ($existing) {
        wp_safe_redirect(admin_url('admin.php?page=acmi-aog-contacts&message=2'));
        exit;
    }
    $wpdb->insert($table_name, ['name' => $name, 'email' => $email, 'created_at' => current_time('mysql')]);
    wp_safe_redirect(admin_url('admin.php?page=acmi-aog-contacts&message=1'));
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['contact_id']) && isset($_GET['_wpnonce'])) {
    $contact_id = intval($_GET['contact_id']);
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_aog_contact_' . $contact_id)) {
        $wpdb->delete($table_name, ['id' => $contact_id]);
        wp_safe_redirect(admin_url('admin.php?page=acmi-aog-contacts&message=5'));
        exit;
    }
}
if (isset($_POST['import_csv']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'import_aog_contacts_csv')) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file_path = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file_path, "r");
        $imported_count = 0; $skipped_count = 0; $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++; if ($row == 1) { continue; }
            if (isset($data[0]) && isset($data[1])) {
                $name = sanitize_text_field($data[0]); $email = sanitize_email($data[1]);
                if (is_email($email)) {
                    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s", $email));
                    if (!$existing) { $wpdb->insert($table_name, ['name' => $name, 'email' => $email, 'created_at' => current_time('mysql')]); $imported_count++; } else { $skipped_count++; }
                } else { $skipped_count++; }
            } else { $skipped_count++; }
        }
        fclose($handle);
        wp_safe_redirect(admin_url(sprintf('admin.php?page=acmi-aog-contacts&message=6&imported=%d&skipped=%d', $imported_count, $skipped_count)));
        exit;
    }
}
if (isset($_GET['message'])) {
    $message_code = intval($_GET['message']);
    switch ($message_code) {
        case 1: $message = 'Contact successfully added.'; $message_type = 'success'; break;
        case 2: $message = 'Error: This email address already exists in the list.'; $message_type = 'error'; break;
        case 4: $message = 'Error: Please provide a valid name and email address.'; $message_type = 'error'; break;
        case 5: $message = 'Contact successfully deleted.'; $message_type = 'success'; break;
        case 6: 
            $imported = isset($_GET['imported']) ? intval($_GET['imported']) : 0;
            $skipped = isset($_GET['skipped']) ? intval($_GET['skipped']) : 0;
            $message = sprintf('%d contacts were successfully imported. %d contacts were skipped.', $imported, $skipped);
            $message_type = 'success';
            break;
    }
}

// SAYFALAMA VE LİSTELEME MANTIĞI
$per_page_options = [25, 50, 100, 200];
$per_page = isset($_GET['per_page']) && in_array($_GET['per_page'], $per_page_options) ? intval($_GET['per_page']) : 25;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;
$total_contacts = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
$contacts = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY name ASC LIMIT %d OFFSET %d", $per_page, $offset));
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="wrap bg-slate-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl">
        <div class="flex items-center gap-3 mb-6">
            <i class="bi bi-person-rolodex text-3xl text-slate-700"></i>
            <h1 class="text-3xl font-bold text-slate-800">AOG Contact List</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md shadow-sm <?= $message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?>">
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-semibold text-gray-700 mb-5">Add New Contact</h2>
                <form method="post" action="<?= esc_url(admin_url('admin.php?page=acmi-aog-contacts')) ?>" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <?php wp_nonce_field('add_new_aog_contact'); ?>
                    <div class="sm:col-span-1">
                        <label for="contact_name" class="block text-sm font-medium text-gray-600 mb-1">Full Name</label>
                        <input type="text" name="contact_name" id="contact_name" class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="sm:col-span-1">
                        <label for="contact_email" class="block text-sm font-medium text-gray-600 mb-1">Email Address</label>
                        <input type="email" name="contact_email" id="contact_email" class="w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <button type="submit" name="add_contact" class="w-full bg-indigo-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-indigo-700">Add Contact</button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-semibold text-gray-700 mb-5">Import Contacts from CSV</h2>
                <form method="post" enctype="multipart/form-data" action="<?= esc_url(admin_url('admin.php?page=acmi-aog-contacts')) ?>">
                    <?php wp_nonce_field('import_aog_contacts_csv'); ?>
                    <div>
                        <label for="csv_file" class="block text-sm font-medium text-gray-600 mb-2">CSV File</label>
                        <input type="file" name="csv_file" id="csv_file" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept=".csv" required>
                        <p class="mt-2 text-xs text-gray-500">Upload a CSV file with Name in the first column and Email in the second. The first row will be skipped as a header.</p>
                    </div>
                    <div class="mt-6">
                        <button type="submit" name="import_csv" class="w-full bg-green-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-green-700">Import Contacts</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <p class="text-sm text-gray-600">Total Contacts: <span class="font-bold"><?= $total_contacts ?></span></p>
                <form method="GET">
                    <input type="hidden" name="page" value="<?= esc_attr($_REQUEST['page']) ?>">
                    <label for="per_page" class="text-sm font-medium text-gray-600 mr-2">Show:</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm p-1">
                        <?php foreach ($per_page_options as $option): ?>
                            <option value="<?= $option ?>" <?= selected($per_page, $option, false) ?>><?= $option ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-white uppercase bg-slate-700">
                        <tr>
                            <th scope="col" class="px-6 py-4">Name</th>
                            <th scope="col" class="px-6 py-4">Email</th>
                            <th scope="col" class="px-6 py-4">Date Added</th>
                            <th scope="col" class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                            <tr><td colspan="4" class="text-center py-10 text-gray-500">No contacts found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $contact): ?>
                                <tr class="bg-white border-b hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-semibold text-gray-900"><?= esc_html($contact->name) ?></td>
                                    <td class="px-6 py-4"><?= esc_html($contact->email) ?></td>
                                    <td class="px-6 py-4"><?= date('d.m.Y', strtotime($contact->created_at)) ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <?php $delete_url = wp_nonce_url(admin_url('admin.php?page=acmi-aog-contacts&action=delete&contact_id=' . $contact->id), 'delete_aog_contact_' . $contact->id); ?>
                                        <a href="<?= esc_url($delete_url) ?>" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700" title="Delete"><i class="bi bi-trash3-fill text-lg"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t bg-gray-50">
                <?php
                    if ($total_contacts > $per_page) {
                        $total_pages = ceil($total_contacts / $per_page);
                        $links = paginate_links([
                            'base'      => add_query_arg(['paged' => '%#%', 'per_page' => $per_page]),
                            'format'    => '',
                            'current'   => $current_page,
                            'total'     => $total_pages,
                            'prev_text' => '<i class="bi bi-arrow-left"></i> Previous',
                            'next_text' => 'Next <i class="bi bi-arrow-right"></i>',
                            'type'      => 'array',
                        ]);

                        if ($links) {
                            echo '<nav class="flex items-center justify-center gap-2">';
                            foreach ($links as $link) {
                                if (strpos($link, 'current') !== false) {
                                    echo '<span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-600 text-white font-bold shadow-md">'.strip_tags($link).'</span>';
                                } elseif (strpos($link, 'dots') !== false) {
                                    echo '<span class="inline-flex items-center justify-center w-10 h-10 text-gray-500">'.strip_tags($link).'</span>';
                                } else {
                                    $link = str_replace('<a class="page-numbers"', '<a class="page-numbers inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white hover:bg-gray-100 text-gray-700 font-semibold border border-gray-200 shadow-sm transition-colors"', $link);
                                    echo $link;
                                }
                            }
                            echo '</nav>';
                        }
                    }
                ?>
            </div>
        </div>
    </div>
</div>