<?php
declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;
        public string $post_type = '';
        public string $post_status = '';
        public string $post_title = '';
        public string $post_excerpt = '';
        public string $post_content = '';
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @param array<string, mixed> $params */
        public function __construct(private array $params = [])
        {
        }

        public function get_param(string $key): mixed
        {
            return $this->params[$key] ?? null;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public function __construct(private mixed $data, private int $status = 200)
        {
        }

        public function get_data(): mixed
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        /** @param array<string, mixed> $data */
        public function __construct(private string $code = '', private string $message = '', private array $data = [])
        {
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        /** @return array<string, mixed> */
        public function get_error_data(): array
        {
            return $this->data;
        }
    }
}

if (!function_exists('absint')) {
    function absint(mixed $value): int
    {
        return abs((int) $value);
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        $key = strtolower($key);

        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

if (!function_exists('get_post')) {
    function get_post(int $id): ?WP_Post
    {
        return $GLOBALS['bml_test_posts'][$id] ?? null;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $id, string $key, bool $single = false): mixed
    {
        return $GLOBALS['bml_test_meta'][$id][$key] ?? '';
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response(mixed $data): WP_REST_Response
    {
        return new WP_REST_Response($data);
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink(int $id): string
    {
        return 'https://example.test/location/' . $id;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value): mixed
    {
        return $value;
    }
}

if (!function_exists('get_the_post_thumbnail_url')) {
    function get_the_post_thumbnail_url(int $id, string $size = 'thumbnail'): string|false
    {
        return false;
    }
}

if (!function_exists('wp_get_post_terms')) {
    function wp_get_post_terms(int $id, string $taxonomy): array
    {
        return [];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool
    {
        return $thing instanceof WP_Error;
    }
}
