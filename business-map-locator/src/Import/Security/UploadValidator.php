<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Security;

use BusinessMapLocator\Import\Config\ImportLimits;
use RuntimeException;

final class UploadValidator
{
    public function validate(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('CSV upload failed.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('CSV upload failed.');
        }

        $reportedSize = (int) ($file['size'] ?? 0);
        $actualSize = filesize($tmpName);
        $size = $actualSize !== false ? (int) $actualSize : $reportedSize;
        if ($size <= 0 || $size > ImportLimits::maxFileSize()) {
            throw new RuntimeException('CSV file exceeds the allowed size limit.');
        }

        $name = sanitize_file_name((string) ($file['name'] ?? ''));
        $type = wp_check_filetype($name, ['csv' => 'text/csv']);
        if (($type['ext'] ?? '') !== 'csv') {
            throw new RuntimeException('Only CSV files are allowed.');
        }

        $allowedMimes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/octet-stream',
        ];

        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detectedMime = (string) $finfo->file($tmpName);
            if ($detectedMime !== '' && !in_array($detectedMime, $allowedMimes, true)) {
                throw new RuntimeException('The uploaded file is not a valid CSV file.');
            }
        }
    }
}
