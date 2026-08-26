<?php
if (!defined('ABSPATH')) { exit; }

final class BML_Provider_Registry {
    /** @var array<string,BML_Map_Provider_Interface> */
    private $providers = [];

    /** @var array<string,array<string,mixed>> */
    private $validation_errors = [];

    public function __construct() {
        $this->register(new BML_OpenStreetMap_Provider());
        $this->register(new BML_GoogleMaps_Provider());
    }

    public function register(BML_Map_Provider_Interface $provider): void {
        $validation = $this->validate_provider($provider);
        $id = $validation['id'];

        if (!$validation['valid']) {
            $this->validation_errors[$id] = $validation;
            return;
        }

        $this->providers[$id] = $provider;
    }

    public function get(string $id): BML_Map_Provider_Interface {
        if (isset($this->providers[$id])) {
            return $this->providers[$id];
        }

        return $this->providers['osm'];
    }

    public function get_active(array $settings): BML_Map_Provider_Interface {
        $detection = $this->detect_provider($settings);
        return $this->get($detection['active']);
    }

    public function detect_provider(array $settings): array {
        $requested = sanitize_key((string) ($settings['provider'] ?? 'osm'));
        $provider  = $this->get($requested);
        $requested_id = $provider->get_id();

        if (!$provider->is_configured($settings)) {
            return [
                'requested' => $requested,
                'active' => 'osm',
                'fallback' => true,
                'reason' => $requested_id === 'google' ? 'google_not_configured' : 'provider_not_configured',
            ];
        }

        return [
            'requested' => $requested,
            'active' => $requested_id,
            'fallback' => $requested !== $requested_id,
            'reason' => $requested !== $requested_id ? 'provider_not_registered' : 'configured',
        ];
    }

    public function register_assets(array $settings): void {
        foreach ($this->providers as $provider) {
            $provider->register_assets($settings);
        }
    }

    public function get_statuses(array $settings): array {
        $statuses = [];
        $active = $this->get_active($settings)->get_id();

        foreach ($this->providers as $id => $provider) {
            $health = $this->get_provider_health($provider, $settings);
            $statuses[$id] = [
                'configured' => $provider->is_configured($settings),
                'active'     => $active === $id,
                'healthy'    => !empty($health['healthy']),
                'health'     => $health,
                'valid'      => $this->validate_provider($provider)['valid'],
            ];
        }

        foreach ($this->validation_errors as $id => $validation) {
            $statuses[$id] = [
                'configured' => false,
                'active' => false,
                'healthy' => false,
                'health' => [
                    'healthy' => false,
                    'code' => 'invalid_provider',
                    'message' => $validation['message'],
                ],
                'valid' => false,
            ];
        }

        return $statuses;
    }

    public function get_health(array $settings): array {
        return [
            'detection' => $this->detect_provider($settings),
            'statuses' => $this->get_statuses($settings),
            'valid' => empty($this->validation_errors),
            'validation_errors' => $this->validation_errors,
        ];
    }

    private function validate_provider(BML_Map_Provider_Interface $provider): array {
        $id = sanitize_key($provider->get_id());

        if ($id === '') {
            return [
                'id' => 'invalid',
                'valid' => false,
                'message' => 'Map provider ID is empty.',
            ];
        }

        foreach (['is_configured', 'register_assets', 'enqueue_assets', 'get_client_config'] as $method) {
            if (!is_callable([$provider, $method])) {
                return [
                    'id' => $id,
                    'valid' => false,
                    'message' => sprintf('Map provider "%s" is missing method "%s".', $id, $method),
                ];
            }
        }

        return [
            'id' => $id,
            'valid' => true,
            'message' => 'Provider interface is valid.',
        ];
    }

    private function get_provider_health(BML_Map_Provider_Interface $provider, array $settings): array {
        if (is_callable([$provider, 'get_health'])) {
            $health = $provider->get_health($settings);
            if (is_array($health) && isset($health['healthy'], $health['code'], $health['message'])) {
                return $health;
            }
        }

        return [
            'healthy' => $provider->is_configured($settings),
            'code' => $provider->is_configured($settings) ? 'ready' : 'not_configured',
            'message' => $provider->is_configured($settings)
                ? 'Provider is configured.'
                : 'Provider is not configured.',
        ];
    }
}
