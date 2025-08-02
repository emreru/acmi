<?php
global $wpdb;
$table_queue = $wpdb->prefix . 'acmi_email_queue';
$table_aog_contacts = $wpdb->prefix . 'acmi_aog_contacts';
$message = '';
$message_type = '';

// Kontak sayılarını hesapla
$flamingo_count_obj = wp_count_posts('flamingo_inbound');
$flamingo_count = $flamingo_count_obj->publish;
$aog_list_count = $wpdb->get_var("SELECT COUNT(id) FROM $table_aog_contacts");

// Form gönderimini işle
if (isset($_POST['send_aog_alert']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'send_aog_alert_nonce')) {
    $recipients = [];
    if (!empty($_POST['recipients']['flamingo'])) {
        $flamingo_emails = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_from_email'");
        if($flamingo_emails){ foreach($flamingo_emails as $email){ if(is_email($email)) $recipients[] = $email; } }
    }
    if (!empty($_POST['recipients']['aog_list'])) {
        $aog_contacts = $wpdb->get_results("SELECT email FROM $table_aog_contacts");
        foreach ($aog_contacts as $contact) { if (is_email($contact->email)) $recipients[] = $contact->email; }
    }
    $recipients = array_unique($recipients);
    $total_recipients = count($recipients);
    if ($total_recipients > 0) {
        $subject = sanitize_text_field($_POST['aog_subject']);
        $body = wp_kses_post($_POST['aog_body']);
        foreach ($recipients as $email) {
            $wpdb->insert($table_queue, ['recipient_email' => $email, 'subject' => $subject, 'message' => $body, 'status' => 'pending', 'created_at' => current_time('mysql')]);
        }
        $message = sprintf('AOG Alert for %d recipients has been successfully queued for sending.', $total_recipients);
        $message_type = 'success';
    } else {
        $message = 'No recipients selected. Please select at least one recipient list.';
        $message_type = 'error';
    }
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="wrap bg-slate-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl">
        <div class="flex items-center gap-3 mb-6">
            <i class="bi bi-broadcast-pin text-3xl text-slate-700"></i>
            <h1 class="text-3xl font-bold text-slate-800">Send AOG Alert</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md shadow-sm <?= $message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?>">
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <form id="aog-form" method="post">
            <?php wp_nonce_field('send_aog_alert_nonce'); ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-5">1. Alert Details</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="aog_aircraft" class="block text-sm font-medium text-gray-600 mb-1">AOG Aircraft</label>
                            <input type="text" id="aog_aircraft" class="live-preview-input w-full rounded-md border-gray-300 shadow-sm" data-target="#preview-aircraft" placeholder="e.g., B737-800">
                        </div>
                        <div>
                            <label for="aog_location" class="block text-sm font-medium text-gray-600 mb-1">Location (ICAO)</label>
                            <input type="text" id="aog_location" class="live-preview-input w-full rounded-md border-gray-300 shadow-sm" data-target="#preview-location" placeholder="e.g., LTAI">
                        </div>
                        <div>
                            <label for="aog_requirement" class="block text-sm font-medium text-gray-600 mb-1">Requirement</label>
                            <textarea id="aog_requirement" class="live-preview-input w-full rounded-md border-gray-300 shadow-sm" rows="4" data-target="#preview-requirement" placeholder="e.g., Need ACMI lease for 48 hours for passenger transport."></textarea>
                        </div>
                        <div>
                            <label for="aog_contact" class="block text-sm font-medium text-gray-600 mb-1">Contact Info</label>
                            <input type="text" id="aog_contact" class="live-preview-input w-full rounded-md border-gray-300 shadow-sm" data-target="#preview-contact" placeholder="e.g., John Doe - +90 555 123 4567">
                        </div>
                    </div>

                    <h2 class="text-xl font-semibold text-gray-700 mt-8 mb-5">2. Select Recipients</h2>
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="recipients[flamingo]" value="1" class="h-5 w-5 rounded border-gray-300 text-indigo-600">
                            <span class="ml-3 text-sm font-medium text-gray-700">
                                Contact Form Leads (from Flamingo)
                                <span class="font-normal text-gray-500">(Contacts: <?= esc_html($flamingo_count) ?>)</span>
                            </span>
                        </label>
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="recipients[aog_list]" value="1" class="h-5 w-5 rounded border-gray-300 text-indigo-600">
                             <span class="ml-3 text-sm font-medium text-gray-700">
                                AOG Contact List (External)
                                <span class="font-normal text-gray-500">(Contacts: <?= esc_html($aog_list_count) ?>)</span>
                            </span>
                        </label>
                    </div>

                    <input type="hidden" name="aog_subject" id="aog_subject">
                    <input type="hidden" name="aog_body" id="aog_body">

                    <div class="mt-8 border-t pt-6">
                        <button type="submit" name="send_aog_alert" class="w-full bg-red-600 text-white font-semibold py-3 px-4 rounded-md hover:bg-red-700 text-lg">
                            <i class="bi bi-send-fill"></i> Send Alert to Queue
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg">
                    <div class="p-4 bg-gray-100 rounded-t-xl border-b">
                        <h2 class="text-xl font-semibold text-gray-700">Live Email Preview</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-1">Subject:</p>
                        <div id="preview-subject" class="p-3 bg-gray-50 rounded-md font-semibold text-gray-800 mb-4">AOG ALERT: Aircraft Required</div>
                        
                        <p class="text-sm text-gray-500 mb-1">Body:</p>
                        <div id="preview-body" class="p-4 border rounded-md text-sm text-gray-700" style="font-family: Arial, sans-serif; line-height: 1.6;">
                            <h3 style="font-size: 18px; color: #b91c1c; margin-top: 0;">URGENT AOG REQUIREMENT</h3>
                            <p><strong>Aircraft:</strong> <span id="preview-aircraft"></span></p>
                            <p><strong>Location:</strong> <span id="preview-location"></span></p>
                            <p><strong>Requirement Details:</strong></p>
                            <p style="padding-left: 15px; border-left: 2px solid #e5e7eb;"><span id="preview-requirement"></span></p>
                            <p><strong>Please Contact:</strong> <span id="preview-contact"></span></p>
                            <hr style="border-top: 1px solid #f3f4f5; margin: 20px 0;">
                            <p style="font-size: 12px; color: #6b7280;">This is an automated AOG alert from [_site_title].</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('aog-form');
    // Sadece formun içinde olan inputları seçtiğimizden emin olalım
    if(form) {
        const inputs = form.querySelectorAll('.live-preview-input');
        
        function updatePreview() {
            // Form verilerini al
            const aircraft = document.getElementById('aog_aircraft').value || '[Aircraft Type]';
            const location = document.getElementById('aog_location').value || '[Location]';
            const requirement = document.getElementById('aog_requirement').value.replace(/\n/g, '<br>') || '[Requirement Details]';
            const contact = document.getElementById('aog_contact').value || '[Contact Info]';

            // Önizleme alanlarını güncelle
            document.getElementById('preview-aircraft').textContent = aircraft;
            document.getElementById('preview-location').textContent = location;
            document.getElementById('preview-requirement').innerHTML = requirement;
            document.getElementById('preview-contact').textContent = contact;

            // Gizli Subject ve Body alanlarını güncelle
            const subjectText = `AOG ALERT: ${aircraft} required at ${location}`;
            const bodyHtml = document.getElementById('preview-body').innerHTML;
            
            document.getElementById('preview-subject').textContent = subjectText;
            document.getElementById('aog_subject').value = subjectText;
            document.getElementById('aog_body').value = bodyHtml;
        }

        // Her bir input alanına event listener ekle
        inputs.forEach(input => {
            input.addEventListener('input', updatePreview);
        });

        // Sayfa yüklendiğinde ilk önizlemeyi oluştur
        updatePreview();
    }
});
</script>