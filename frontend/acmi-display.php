<?php
// ACMI Display Template (Custom with Fixed Round Icon)
global $wpdb;
$table_types = $wpdb->prefix . 'acmi_aircraft_types';
$table_acmi  = $wpdb->prefix . 'acmi_availabilities';

$results = $wpdb->get_results("
    SELECT 
        t.id, t.aircraft_type, t.body_type, t.configuration, t.image_url,
        COUNT(a.id) as count
    FROM $table_types t
    LEFT JOIN $table_acmi a ON a.aircraft_type_id = t.id AND a.is_active = 1
    GROUP BY t.id
    HAVING count >= 1
");
?>
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css'>
<style>
    .acmi-card {
        border: 1px solid #dee2e6;
        background-color: #fff;
        border-radius: 12px;
        padding: 25px 20px;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
        color: #043527;
    }
    .acmi-card:hover {
        background-color: #e3c48b;
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    .acmi-icon {
        font-size: 32px;
        background-color: #f1f1f1;
        border-radius: 50%;
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        color: #043527;
    }
    .acmi-count {
        font-size: 32px;
        font-weight: 700;
        color: #9b6605;
    }
    .acmi-card:hover .acmi-count {
        color: #043527;
    }
    .acmi-title {
        font-size: 14px;
        text-transform: uppercase;
        color: #043527;
        font-weight: 600;
    }
</style>
<div class='container py-5'>
    <div class='text-center mb-5'>
        <h3><i class='bi bi-airplane'></i> Aktif ACMI Uçaklar</h3>
    </div>
    <div class='row g-4'>
        <?php foreach ($results as $r): ?>
            <div class='col-12 col-sm-6 col-md-4 col-lg-3'>
                <a href='<?php echo site_url('/acmi-detay/?acmi_details=' . $r->id); ?>' class='text-decoration-none'>
                    <div class='acmi-card'>
                        <div class='acmi-icon'><i class='bi bi-airplane'></i></div>
                        <div class='acmi-count'><?php echo str_pad($r->count, 3, '0', STR_PAD_LEFT); ?></div>
                        <div class='acmi-title'><?php echo esc_html($r->aircraft_type); ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
