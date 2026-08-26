<?php
declare(strict_types=1);

namespace BusinessMapLocator\Admin\Shared;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminActionResponder
{
    public function redirect(string $page, string $notice, array $extra = []): never
    {
        $url = add_query_arg(
            array_merge(
                [
                    'page' => $page,
                    'bml_notice' => $notice,
                ],
                $extra
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }

    public function error(string $message, int $statusCode = 400): never
    {
        wp_die(esc_html($message), '', ['response' => $statusCode]);
    }
}
