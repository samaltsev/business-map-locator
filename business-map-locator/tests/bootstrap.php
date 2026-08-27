<?php
declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'BusinessMapLocator\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }, true, true);
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

        public function get_error_message(): string
        {
            return $this->message;
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

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $id, string $key, mixed $value): bool
    {
        $GLOBALS['bml_test_meta'][$id][$key] = $value;

        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $value): string
    {
        return trim($value);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(string $value): string
    {
        return trim($value);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $value): string
    {
        return trim($value);
    }
}

if (!function_exists('get_posts')) {
    function get_posts(array $args = []): array
    {
        $matches = [];
        foreach ($GLOBALS['bml_test_posts'] ?? [] as $id => $post) {
            if (($args['post_type'] ?? '') !== '' && $post->post_type !== $args['post_type']) {
                continue;
            }
            if (isset($args['post_status']) && is_array($args['post_status']) && !in_array($post->post_status, $args['post_status'], true)) {
                continue;
            }
            foreach ($args['meta_query'] ?? [] as $query) {
                if (($GLOBALS['bml_test_meta'][$id][$query['key']] ?? '') !== ($query['value'] ?? '')) {
                    continue 2;
                }
            }
            $matches[] = ($args['fields'] ?? '') === 'ids' ? (int) $id : $post;
        }

        $limit = (int) ($args['posts_per_page'] ?? -1);

        return $limit > 0 ? array_slice($matches, 0, $limit) : $matches;
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post(array $data, bool $wpError = false): int
    {
        $ids = array_keys($GLOBALS['bml_test_posts'] ?? []);
        $id = $ids === [] ? 1 : max($ids) + 1;
        $post = new WP_Post();
        $post->ID = $id;
        $post->post_type = (string) ($data['post_type'] ?? 'post');
        $post->post_title = (string) ($data['post_title'] ?? '');
        $post->post_status = (string) ($data['post_status'] ?? 'draft');
        $GLOBALS['bml_test_posts'][$id] = $post;

        return $id;
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post(array $data, bool $wpError = false): int|WP_Error
    {
        $id = (int) ($data['ID'] ?? 0);
        $post = $GLOBALS['bml_test_posts'][$id] ?? null;
        if (!$post instanceof WP_Post) {
            return new WP_Error('missing_post', 'Post not found.');
        }
        $post->post_title = (string) ($data['post_title'] ?? $post->post_title);
        $post->post_status = (string) ($data['post_status'] ?? $post->post_status);

        return $id;
    }
}

if (!class_exists('BML_Location_Index')) {
    class BML_Location_Index
    {
        public function upsert(int $postId): bool
        {
            $GLOBALS['bml_test_indexed'][] = $postId;

            return true;
        }
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
