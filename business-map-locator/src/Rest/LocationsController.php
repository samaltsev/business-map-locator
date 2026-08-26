<?php
declare(strict_types=1);

namespace BusinessMapLocator\Rest;

use BusinessMapLocator\Application\Location\SearchLocationsHandler;
use BusinessMapLocator\Application\Location\SearchLocationsQuery;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Post;
use BusinessMapLocator\Infrastructure\Database\LocationRepository;

final readonly class LocationsController
{
    public function __construct(
        private SearchLocationsHandler $searchHandler,
        private LocationResponseFactory $responseFactory,
        private LocationDetailResponseFactory $detailResponseFactory,
        private LocationRepository $repository
    ) {
    }

    public function registerRoutes(): void
    {
        register_rest_route('business-map/v1', '/locations', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'index'],
            'permission_callback' => '__return_true',
            'args' => $this->args(),
        ]);
        register_rest_route('business-map/v1', '/locations/markers', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'markers'],
            'permission_callback' => '__return_true',
            'args' => [
                'north' => ['required' => true],
                'south' => ['required' => true],
                'east' => ['required' => true],
                'west' => ['required' => true],
                'search' => ['type' => 'string', 'default' => '', 'sanitize_callback' => static fn (mixed $value): string => sanitize_text_field((string) $value)],
                'category' => ['type' => 'string', 'default' => '', 'sanitize_callback' => static fn (mixed $value): string => sanitize_title((string) $value)],
                'city' => ['type' => 'string', 'default' => '', 'sanitize_callback' => static fn (mixed $value): string => sanitize_title((string) $value)],
                'lat' => ['type' => 'number', 'minimum' => -90, 'maximum' => 90],
                'lng' => ['type' => 'number', 'minimum' => -180, 'maximum' => 180],
                'radius' => ['type' => 'number', 'minimum' => 1, 'maximum' => 500],
                'unit' => ['type' => 'string', 'default' => 'km', 'enum' => ['km', 'mi']],
                'orderby' => ['type' => 'string', 'default' => 'title', 'enum' => ['title', 'distance']],
                'limit' => ['type' => 'integer', 'default' => 1000, 'minimum' => 1, 'maximum' => 1000],
            ],
        ]);
        register_rest_route('business-map/v1', '/locations/bounds', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'bounds'],
            'permission_callback' => '__return_true',
            'args' => [
                'search' => ['type' => 'string', 'default' => '', 'sanitize_callback' => static fn (mixed $value): string => sanitize_text_field((string) $value)],
                'category' => ['type' => 'string', 'default' => '', 'sanitize_callback' => static fn (mixed $value): string => sanitize_title((string) $value)],
                'city' => ['type' => 'string', 'default' => '', 'sanitize_callback' => static fn (mixed $value): string => sanitize_title((string) $value)],
            ],
        ]);
        register_rest_route('business-map/v1', '/locations/(?P<id>[1-9][0-9]*)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'show'],
            'permission_callback' => '__return_true',
            'args' => ['id' => ['required' => true, 'validate_callback' => static fn (mixed $value): bool => is_numeric($value) && (int) $value > 0]],
        ]);
    }

    public function markers(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $values = [];
        foreach (['north', 'south', 'east', 'west'] as $key) {
            $value = self::finiteFloat($request->get_param($key));
            if ($value === null) {
                return new WP_Error('bml_invalid_bounds', __('Invalid map bounds.', 'business-map-locator'), ['status' => 400]);
            }
            $values[$key] = $value;
        }

        if ($values['north'] < -90 || $values['north'] > 90 || $values['south'] < -90 || $values['south'] > 90 || $values['south'] > $values['north']) {
            return new WP_Error('bml_invalid_bounds', __('Invalid map bounds.', 'business-map-locator'), ['status' => 400]);
        }

        $fullWorld = ($values['east'] - $values['west']) >= 359.999999;
        if (!$fullWorld) {
            $values['east'] = self::normalizeLongitude($values['east']);
            $values['west'] = self::normalizeLongitude($values['west']);
        }

        try {
            $category = sanitize_title(self::optionalString($request->get_param('category'), 'category'));
            $city = sanitize_title(self::optionalString($request->get_param('city'), 'city'));
            $search = sanitize_text_field(self::optionalString($request->get_param('search'), 'search'));
            $nearQuery = SearchLocationsQuery::fromArray([
                'lat' => $request->get_param('lat'),
                'lng' => $request->get_param('lng'),
                'radius' => $request->get_param('radius'),
                'unit' => $request->get_param('unit'),
                'orderby' => $request->get_param('orderby'),
            ]);
            if (($nearQuery->origin && !$nearQuery->radius) || ($nearQuery->radius && !$nearQuery->origin) || ($nearQuery->orderby === 'distance' && !$nearQuery->origin)) {
                throw new InvalidArgumentException('Near me markers require latitude, longitude and radius.');
            }
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('bml_invalid_location_query', $exception->getMessage(), ['status' => 400]);
        }

        $limit = max(1, min(1000, absint($request->get_param('limit') ?: 1000)));

        return rest_ensure_response($this->repository->markers($values['north'], $values['south'], $values['east'], $values['west'], $category, $city, $search, $limit, $fullWorld, $nearQuery->origin, $nearQuery->radius));
    }

    public function bounds(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $category = sanitize_title(self::optionalString($request->get_param('category'), 'category'));
            $city = sanitize_title(self::optionalString($request->get_param('city'), 'city'));
            $search = sanitize_text_field(self::optionalString($request->get_param('search'), 'search'));
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('bml_invalid_location_query', $exception->getMessage(), ['status' => 400]);
        }

        return rest_ensure_response($this->repository->publicBounds($category, $city, $search));
    }

    private static function optionalString(mixed $value, string $parameter): string
    {
        if ($value === null) {
            return '';
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf('Invalid %s value.', $parameter));
        }

        return (string) $value;
    }

    private static function finiteFloat(mixed $value): ?float
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false || !is_finite((float) $value)) {
            return null;
        }

        return (float) $value;
    }

    private static function normalizeLongitude(float $longitude): float
    {
        $normalized = fmod(fmod($longitude + 180.0, 360.0) + 360.0, 360.0) - 180.0;

        return $normalized === -0.0 ? 0.0 : $normalized;
    }

    public function index(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $query = SearchLocationsQuery::fromArray($request->get_params());
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('bml_invalid_location_query', $exception->getMessage(), ['status' => 400]);
        }

        try {
            $result = $this->searchHandler->handle($query);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error('bml_invalid_location_query', $exception->getMessage(), ['status' => 400]);
        }

        return $this->responseFactory->create($result, $query);
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = absint($request->get_param('id'));
        $post = $id > 0 ? get_post($id) : null;
        if (!$post instanceof WP_Post || $post->post_type !== 'bml_location' || $post->post_status !== 'publish' || !$this->hasValidCoordinates($id)) {
            return new WP_Error('bml_location_not_found', __('Location not found.', 'business-map-locator'), ['status' => 404]);
        }
        return rest_ensure_response($this->detailResponseFactory->create($post));
    }

    private function hasValidCoordinates(int $id): bool
    {
        $lat = filter_var(get_post_meta($id, 'bml_lat', true), FILTER_VALIDATE_FLOAT);
        $lng = filter_var(get_post_meta($id, 'bml_lng', true), FILTER_VALIDATE_FLOAT);
        return $lat !== false && $lng !== false && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function args(): array
    {
        return [
            'search' => [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => static fn (mixed $value): string => sanitize_text_field((string) $value),
            ],
            'category' => [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => static fn (mixed $value): string => sanitize_title((string) $value),
            ],
            'city' => [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => static fn (mixed $value): string => sanitize_title((string) $value),
            ],
            'page' => [
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'sanitize_callback' => static fn (mixed $value): int => absint($value),
                'validate_callback' => static fn (mixed $value): bool => is_numeric($value) && (int) $value >= 1,
            ],
            'per_page' => [
                'type' => 'integer',
                'default' => 200,
                'minimum' => 1,
                'maximum' => 500,
                'sanitize_callback' => static fn (mixed $value): int => absint($value),
                'validate_callback' => static fn (mixed $value): bool => is_numeric($value) && (int) $value >= 1 && (int) $value <= 500,
            ],
            'orderby' => [
                'type' => 'string',
                'default' => 'title',
                'enum' => ['title', 'date', 'modified', 'menu_order', 'distance'],
                'sanitize_callback' => static fn (mixed $value): string => sanitize_key((string) $value),
            ],
            'order' => [
                'type' => 'string',
                'default' => 'ASC',
                'enum' => ['ASC', 'DESC', 'asc', 'desc'],
                'sanitize_callback' => static fn (mixed $value): string => strtoupper(sanitize_text_field((string) $value)),
            ],
            'north' => ['type' => 'number'],
            'south' => ['type' => 'number'],
            'east' => ['type' => 'number'],
            'west' => ['type' => 'number'],
            'bbox' => [
                'type' => 'string',
                'description' => 'Bounding box as west,south,east,north.',
                'sanitize_callback' => static fn (mixed $value): string => sanitize_text_field((string) $value),
            ],
            'bounds' => [
                'type' => 'string',
                'description' => 'Bounding box alias as west,south,east,north.',
                'sanitize_callback' => static fn (mixed $value): string => sanitize_text_field((string) $value),
            ],
            'lat' => [
                'type' => 'number',
                'minimum' => -90,
                'maximum' => 90,
                'sanitize_callback' => static fn (mixed $value): float => (float) $value,
            ],
            'lng' => [
                'type' => 'number',
                'minimum' => -180,
                'maximum' => 180,
                'sanitize_callback' => static fn (mixed $value): float => (float) $value,
            ],
            'radius' => [
                'type' => 'number',
                'minimum' => 1,
                'maximum' => 500,
                'sanitize_callback' => static fn (mixed $value): float => (float) $value,
            ],
            'unit' => [
                'type' => 'string',
                'default' => 'km',
                'enum' => ['km', 'mi'],
                'sanitize_callback' => static fn (mixed $value): string => sanitize_key((string) $value),
            ],
        ];
    }
}
