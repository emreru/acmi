<?php
global $wpdb;

// 1. SİLME İŞLEMİ MANTIĞI
// ===================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['contact_id'])) {
    $contact_id_to_delete = intval($_GET['contact_id']);
    // Nonce (güvenlik kodu) kontrolü
    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_contact_' . $contact_id_to_delete)) {
        // Yetki kontrolü (sadece adminler silebilir)
        if (current_user_can('manage_options')) {
            // Flamingo kaydını (bir post olduğu için) kalıcı olarak sil
            wp_delete_post($contact_id_to_delete, true);
            // Kullanıcıya bildirim göstermek için sayfayı yönlendir
            wp_safe_redirect(admin_url('admin.php?page=acmi-contacts&deleted=true'));
            exit;
        }
    }
}

// 2. FİLTRE VE SAYFALAMA VERİLERİNİ ALMA
// ==========================================
$search_query    = isset($_GET['search_query']) ? sanitize_text_field($_GET['search_query']) : '';
$aircraft_filter = isset($_GET['aircraft_filter']) ? sanitize_text_field($_GET['aircraft_filter']) : '';
$paged           = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// 3. WP_QUERY SORGUSUNU DİNAMİK OLARAK OLUŞTURMA
// ==================================================
$args = [
    'post_type'      => 'flamingo_inbound',
    'posts_per_page' => 20,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => ['relation' => 'AND'],
];

if (!empty($search_query)) {
    $args['meta_query'][] = ['relation' => 'OR', ['key' => '_field_your-name', 'value' => $search_query, 'compare' => 'LIKE'], ['key' => '_field_your-email', 'value' => $search_query, 'compare' => 'LIKE']];
}
if (!empty($aircraft_filter)) {
    $args['meta_query'][] = ['key' => '_field_aircraft', 'value' => $aircraft_filter, 'compare' => '='];
}

$inbound_messages = new WP_Query($args);

// 4. YARDIMCI FONKSİYON VE VERİLER
// ======================================
function get_safe_flamingo_meta($post_id, $key) {
    $meta = get_post_meta($post_id, $key, true);
    return (is_array($meta) && !empty($meta)) ? $meta[0] : $meta;
}

$aircraft_types_table = $wpdb->prefix . 'acmi_aircraft_types';
$all_aircraft_types = $wpdb->get_results("SELECT DISTINCT aircraft_type FROM $aircraft_types_table ORDER BY aircraft_type ASC");
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="wrap bg-slate-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-full">
        
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-3">
                <i class="bi bi-people-fill text-3xl text-slate-700"></i>
                <h1 class="text-3xl font-bold text-slate-800">Contact Form Leads</h1>
            </div>
            <div class="flex items-center gap-x-2 p-1 bg-gray-200 rounded-lg">
                <button id="view-grid-btn" class="view-toggle-btn p-2 rounded-md transition-colors" title="Grid View"><i class="bi bi-grid-3x3-gap-fill text-xl"></i></button>
                <button id="view-list-btn" class="view-toggle-btn p-2 rounded-md transition-colors" title="List View"><i class="bi bi-list text-xl"></i></button>
            </div>
        </div>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'true'): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-sm mb-6" role="alert">
                <p>Contact submission successfully deleted.</p>
            </div>
        <?php endif; ?>

        <div class="bg-white p-4 rounded-xl shadow-lg mb-8">
            <form method="GET" action="">
                <input type="hidden" name="page" value="<?= esc_attr($_REQUEST['page']) ?>">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label for="search_query" class="block text-sm font-medium text-gray-700">Search by Name or Email</label>
                        <input type="text" name="search_query" id="search_query" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="<?= esc_attr($search_query) ?>" placeholder="e.g. John Doe or john@example.com">
                    </div>
                    <div>
                        <label for="aircraft_filter" class="block text-sm font-medium text-gray-700">Filter by Aircraft</label>
                        <select name="aircraft_filter" id="aircraft_filter" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Aircrafts</option>
                            <?php foreach ($all_aircraft_types as $type): ?>
                                <option value="<?= esc_attr($type->aircraft_type) ?>" <?= selected($aircraft_filter, $type->aircraft_type, false) ?>>
                                    <?= esc_html($type->aircraft_type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Filter</button>
                        <a href="<?= admin_url('admin.php?page=acmi-contacts') ?>" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($inbound_messages->have_posts()): ?>
            <div id="contact-cards-container" class="gap-6">
                <?php while ($inbound_messages->have_posts()): $inbound_messages->the_post(); ?>
                    <?php
                        $id            = get_the_ID();
                        $name          = get_safe_flamingo_meta($id, '_field_your-name');
                        $email         = get_safe_flamingo_meta($id, '_field_your-email');
                        $phone         = get_safe_flamingo_meta($id, '_field_your-phone');
                        $enquirer      = get_safe_flamingo_meta($id, '_field_who-are-you');
                        $aircraft      = get_safe_flamingo_meta($id, '_field_aircraft');
                        $contract      = get_safe_flamingo_meta($id, '_field_contract-type');
                        $configuration = get_safe_flamingo_meta($id, '_field_configuration');
                        $units         = get_safe_flamingo_meta($id, '_field_units');
                        $date_from     = get_safe_flamingo_meta($id, '_field_date-from');
                        $date_to       = get_safe_flamingo_meta($id, '_field_date-to');
                        $routes        = get_safe_flamingo_meta($id, '_field_routes');
                        $aircraft_base = get_safe_flamingo_meta($id, '_field_aircraft-base');
                        $total_bh      = get_safe_flamingo_meta($id, '_field_total-bh');
                        $cycle_ratio   = get_safe_flamingo_meta($id, '_field_cycle-ratio');
                        $notes         = get_safe_flamingo_meta($id, '_field_notes');
                        $submission_date = get_the_date('d.m.Y H:i', $id);
                    ?>
                    <div class="contact-card bg-white p-6 rounded-xl shadow-lg flex flex-col">
                        <div class="border-b pb-4 mb-4 flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-slate-800"><?= esc_html($name) ?></h3>
                                <p class="text-sm text-gray-500"><?= esc_html($enquirer) ?></p>
                            </div>
                            <?php
                                $delete_url = wp_nonce_url(admin_url('admin.php?page=acmi-contacts&action=delete&contact_id=' . $id), 'delete_contact_' . $id);
                            ?>
                            <a href="<?= esc_url($delete_url) ?>" onclick="return confirm('Are you sure you want to permanently delete this contact submission?')" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete Submission"><i class="bi bi-trash3-fill"></i></a>
                        </div>
                        <div class="space-y-3 text-sm flex-grow">
                            <p class="flex items-center gap-3"><i class="bi bi-envelope-fill text-gray-400 w-4 text-center"></i><a href="mailto:<?= esc_attr($email) ?>" class="text-indigo-600 hover:underline"><?= esc_html($email) ?></a></p>
                            <p class="flex items-center gap-3"><i class="bi bi-telephone-fill text-gray-400 w-4 text-center"></i><span><?= esc_html($phone) ?></span></p>
                            <p class="flex items-center gap-3"><i class="bi bi-airplane-fill text-gray-400 w-4 text-center"></i><span class="font-semibold">Aircraft:</span> <?= esc_html($aircraft) ?></p>
                            <p class="flex items-center gap-3"><i class="bi bi-file-earmark-text-fill text-gray-400 w-4 text-center"></i><span class="font-semibold">Contract:</span> <?= esc_html($contract) ?></p>
                        </div>
                        <div class="mt-4">
                            <button type="button" class="toggle-details text-sm font-medium text-indigo-600 hover:text-indigo-800 focus:outline-none" data-target="#details-<?= $id ?>"><span class="toggle-text">Read More</span> <i class="bi bi-chevron-down toggle-icon transition-transform"></i></button>
                        </div>
                        <div id="details-<?= $id ?>" class="collapsible-details hidden mt-4 pt-4 border-t border-gray-200 text-sm">
                            <h4 class="font-bold text-md text-slate-700 mb-3">Rental Details</h4>
                            <div class="space-y-2 pl-2">
                                <p><strong class="font-semibold text-gray-600">Configuration:</strong> <?= esc_html($configuration) ?></p>
                                <p><strong class="font-semibold text-gray-600">Number of Units:</strong> <?= esc_html($units) ?></p>
                                <p><strong class="font-semibold text-gray-600">Period:</strong> <?= esc_html($date_from) ?> to <?= esc_html($date_to) ?></p>
                                <p><strong class="font-semibold text-gray-600">Routes:</strong> <?= esc_html($routes) ?></p>
                                <p><strong class="font-semibold text-gray-600">Aircraft Base:</strong> <?= esc_html($aircraft_base) ?></p>
                                <p><strong class="font-semibold text-gray-600">Total BH/month:</strong> <?= esc_html($total_bh) ?></p>
                                <p><strong class="font-semibold text-gray-600">Cycle Ratio:</strong> <?= esc_html($cycle_ratio) ?></p>
                                <?php if (!empty($notes)): ?>
                                    <div class="pt-1"><p class="font-semibold text-gray-600">Notes:</p><blockquote class="text-gray-500 pl-2 border-l-2 ml-1 mt-1"><?= nl2br(esc_html($notes)) ?></blockquote></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 mt-4 pt-4 border-t text-center">Submitted on: <?= $submission_date ?></div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="mt-10 py-4">
                <?php echo paginate_links(['base' => remove_query_arg('paged', add_query_arg('paged', '%#%')), 'format' => '?paged=%#%', 'current' => max(1, $paged), 'total' => $inbound_messages->max_num_pages, 'prev_text' => __('&larr; Previous'), 'next_text' => __('Next &rarr;'), 'type' => 'list']); ?>
            </div>
            
            <?php wp_reset_postdata(); ?>

        <?php else: ?>
            <div class="bg-white p-10 rounded-xl shadow-lg text-center">
                <p class="text-gray-600 font-semibold text-lg">No Results Found</p>
                <p class="text-gray-500 mt-2">Your search or filter criteria did not match any records. Try different keywords or clear the filters.</p>
                <a href="<?= admin_url('admin.php?page=acmi-contacts') ?>" class="mt-4 inline-block py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // "Read More" Toggle
    const toggles = document.querySelectorAll('.toggle-details');
    toggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.dataset.target;
            const detailsSection = document.querySelector(targetId);
            const toggleText = this.querySelector('.toggle-text');
            const toggleIcon = this.querySelector('.toggle-icon');
            detailsSection.classList.toggle('hidden');
            if (detailsSection.classList.contains('hidden')) {
                toggleText.textContent = 'Read More';
                toggleIcon.classList.remove('bi-chevron-up');
                toggleIcon.classList.add('bi-chevron-down');
            } else {
                toggleText.textContent = 'Read Less';
                toggleIcon.classList.remove('bi-chevron-down');
                toggleIcon.classList.add('bi-chevron-up');
            }
        });
    });

    // View Switcher Logic
    const gridBtn = document.getElementById('view-grid-btn');
    const listBtn = document.getElementById('view-list-btn');
    const container = document.getElementById('contact-cards-container');

    const setActiveButton = (activeBtn) => {
        gridBtn.classList.remove('bg-white', 'text-indigo-600', 'shadow-md');
        listBtn.classList.remove('bg-white', 'text-indigo-600', 'shadow-md');
        gridBtn.classList.add('text-gray-500');
        listBtn.classList.add('text-gray-500');
        activeBtn.classList.add('bg-white', 'text-indigo-600', 'shadow-md');
        activeBtn.classList.remove('text-gray-500');
    };

    const setGridView = () => {
        container.className = 'gap-6 view-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4';
        setActiveButton(gridBtn);
        localStorage.setItem('contactView', 'grid');
    };

    const setListView = () => {
        container.className = 'gap-6 view-list flex flex-col';
        setActiveButton(listBtn);
        localStorage.setItem('contactView', 'list');
    };

    gridBtn.addEventListener('click', setGridView);
    listBtn.addEventListener('click', setListView);

    const savedView = localStorage.getItem('contactView');
    if (savedView === 'list') { setListView(); } 
    else { setGridView(); }
});
</script>

<style>
    .pagination ul { display: flex; gap: 0.5rem; justify-content: center; list-style-type: none; padding: 0; }
    .pagination li a, .pagination li span { display: block; padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; color: #374151; text-decoration: none; transition: all 0.2s ease-in-out; }
    .pagination li a:hover { background-color: #f3f4f6; border-color: #6b7280; }
    .pagination li span.current { background-color: #4f46e5; color: #fff; border-color: #4f46e5; font-weight: bold; }
    .view-list .contact-card { flex-direction: row; align-items: center; padding: 1rem; }
    .view-list .contact-card > div:first-child { border-bottom: 0; border-right: 1px solid #e5e7eb; margin-bottom: 0; padding-bottom: 0; padding-right: 1rem; flex-basis: 25%; }
    .view-list .contact-card .flex-grow { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem 1.5rem; padding-left: 1rem; flex-basis: 60%; }
    .view-list .contact-card .flex-grow p { margin: 0; }
    .view-list .toggle-details, .view-list .collapsible-details { display: none; }
    .view-list .mt-4.pt-4.border-t { border-top: 0; margin-top: 0; padding-top: 0; padding-left: 1rem; text-align: right; flex-basis: 15%; }
</style>