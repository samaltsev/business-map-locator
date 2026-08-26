<?php

declare(strict_types=1);

namespace BusinessMapLocator\Admin\Ajax;

use BusinessMapLocator\Admin\Request\AdminRequest;
use BusinessMapLocator\Import\Exception\ImportJobException;
use BusinessMapLocator\Import\ImportManager;
use BusinessMapLocator\WordPress\Capabilities;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ImportAjaxController
{
    public function __construct(private ImportManager $manager, private AdminRequest $request) {}

    public function prepareImport(): void
    {
        $this->respond(fn (): array => $this->manager->prepare($_FILES['csv'] ?? [], $this->request->postBool('dry_run')));
    }

    public function processImport(): void
    {
        $this->respond(fn (): array => $this->manager->process($this->token()));
    }

    public function pauseImport(): void
    {
        $this->respond(fn (): array => $this->manager->pause($this->token()));
    }

    public function cancelImport(): void
    {
        $this->respond(fn (): array => $this->manager->cancel($this->token()));
    }

    public function resumeImport(): void
    {
        $this->respond(fn (): array => $this->manager->resume($this->token()));
    }

    public function scanDuplicates(): void
    {
        $this->respond(fn (): array => $this->manager->scanDuplicates());
    }

    public function deleteDuplicates(): void
    {
        $this->respond(fn (): array => $this->manager->deleteDuplicates());
    }

    private function respond(callable $callback): void
    {
        $this->verify();

        try {
            wp_send_json_success($callback());
        } catch (ImportJobException $exception) {
            $this->logException($exception);
            wp_send_json_error([
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
            ], $exception->httpStatus());
        } catch (Throwable $exception) {
            $this->logException($exception);
            wp_send_json_error([
                'code' => 'import_internal_error',
                'message' => __('The import could not be completed. Check the plugin log and try again.', 'business-map-locator'),
            ], 500);
        }
    }

    private function token(): string
    {
        return $this->request->postString('token');
    }

    private function logException(Throwable $exception): void
    {
        do {
            error_log(sprintf(
                '[Business Map Locator] Import error: %s: %s in %s:%d',
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
            $exception = $exception->getPrevious();
        } while ($exception instanceof Throwable);
    }

    private function verify(): void
    {
        if (!current_user_can(Capabilities::MANAGE_IMPORTS)) {
            wp_send_json_error([
                'code' => 'import_forbidden',
                'message' => __('Insufficient permissions.', 'business-map-locator'),
            ], 403);
        }

        check_ajax_referer('bml_import_ajax', 'nonce');
    }
}
