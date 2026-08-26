<?php
declare(strict_types=1);

$wpLoad = 'D:\\OSPanel\\home\\business-map.local\\public\\wp-load.php';
if (!is_file($wpLoad)) { fwrite(STDERR, "wp-load.php is unavailable\n"); exit(2); }
require_once $wpLoad;

$prefix = 'BML_CODEX_RUNTIME_';
$id = 0;
$result = ['prefix' => $prefix, 'created_id' => null, 'checks' => [], 'cleanup' => false];
try {
    $id = wp_insert_post(['post_type' => 'bml_location', 'post_status' => 'publish', 'post_title' => $prefix . uniqid(), 'post_content' => '<p>Safe runtime content.</p>', 'post_excerpt' => 'Runtime excerpt'], true);
    if (is_wp_error($id) || !$id) { throw new RuntimeException('Temporary post creation failed.'); }
    $id = (int) $id; $result['created_id'] = $id;
    foreach (['bml_address' => 'Runtime address', 'bml_postcode' => '00000', 'bml_phone' => '+1000000', 'bml_email' => 'runtime@example.test', 'bml_website' => 'https://example.test/', 'bml_hours' => 'Mon-Fri 09:00-17:00', 'bml_operational_status' => 'active', 'bml_unknown_runtime' => 'preserve-me'] as $key => $value) { update_post_meta($id, $key, $value); }
    update_post_meta($id, 'bml_lat', 53.9); update_post_meta($id, 'bml_lng', 27.56);
    (new BML_Location_Index())->upsert($id);
    update_post_meta($id, 'bml_phone', '+1000001'); // partial update simulation; omitted values must survive.
    delete_post_meta($id, 'bml_hours'); // explicit owned-field clear simulation.
    (new BML_Location_Index())->upsert($id);
    $result['checks']['partial_preserves_email'] = get_post_meta($id, 'bml_email', true) === 'runtime@example.test';
    $result['checks']['partial_preserves_unknown_meta'] = get_post_meta($id, 'bml_unknown_runtime', true) === 'preserve-me';
    $result['checks']['explicit_clear_only_hours'] = get_post_meta($id, 'bml_hours', true) === '' && get_post_meta($id, 'bml_website', true) === 'https://example.test/';
    $request = new WP_REST_Request('GET', '/business-map/v1/locations/' . $id);
    $response = rest_do_request($request);
    $result['checks']['published_detail'] = $response->get_status() === 200;
    wp_update_post(['ID' => $id, 'post_status' => 'draft']); (new BML_Location_Index())->upsert($id);
    $response = rest_do_request($request);
    $result['checks']['draft_detail_is_404'] = $response->get_status() === 404;
    if (in_array(false, $result['checks'], true)) { throw new RuntimeException('One or more smoke checks failed.'); }
} catch (Throwable $error) { $result['error'] = $error->getMessage(); }
finally { if ($id > 0) { wp_delete_post($id, true); $result['cleanup'] = get_post($id) === null; } }
echo wp_json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
exit(isset($result['error']) ? 1 : 0);
