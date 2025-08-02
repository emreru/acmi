<?php
// ACMI Details Page
global $wpdb;

$acmi_id = isset($_GET['acmi_details']) ? intval($_GET['acmi_details']) : 0;
if (!$acmi_id) return;

$table_types = $wpdb->prefix . 'acmi_aircraft_types';
$table_availabilities = $wpdb->prefix . 'acmi_availabilities';

$today = date('Y-m-d');

$results = $wpdb->get_results(
    $wpdb->prepare("
        SELECT 
            a.start_date,
            a.end_date,
            a.region,
            a.approvals,
            t.aircraft_type,
            t.body_type,
            t.configuration
        FROM $table_availabilities a
        JOIN $table_types t ON a.aircraft_type_id = t.id
        WHERE a.aircraft_type_id = %d AND a.is_active = 1
        ORDER BY a.start_date ASC
    ", $acmi_id)
);

if (!$results) return;

$aircraft_title = esc_html($results[0]->aircraft_type);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .acmi-table th {
        background-color: #1f2e2e;
        color: #fff;
    }
    .acmi-table td {
        vertical-align: middle;
    }
</style>

<div class="container my-5">
    <h3 class="text-center mb-4"><i class="bi bi-airplane"></i> <?php echo $aircraft_title; ?> Details</h3>

    <div class="table-responsive">
        <table class="table table-striped acmi-table">
            <thead>
                <tr>
                    <th>Date of Availability</th>
                    <th>Base Region</th>
                    <th>Aircraft Type</th>
                    <th>Configuration</th>
                    <th>Approvals</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): 
                    $start = (strtotime($row->start_date) < strtotime($today)) ? date('d M Y') : date('d M Y', strtotime($row->start_date));
                    $end = date('d M Y', strtotime($row->end_date));
                    ?>
                    <tr>
                        <td><?php echo $start . ' – ' . $end; ?></td>
                        <td><?php echo esc_html($row->region); ?></td>
                        <td><?php echo esc_html($row->aircraft_type); ?></td>
                        <td><?php echo esc_html($row->configuration); ?></td>
                        <td><?php echo esc_html($row->approvals); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
