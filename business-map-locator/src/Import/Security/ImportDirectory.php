<?php
declare(strict_types=1);

namespace BusinessMapLocator\Import\Security;

use RuntimeException;

final class ImportDirectory
{
    public function path(): string
    {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            throw new RuntimeException((string) $uploads['error']);
        }

        $directory = trailingslashit((string) $uploads['basedir']) . 'bml-imports';
        $this->protect($directory);
        return $directory;
    }

    private function protect(string $directory): void
    {
        if (!wp_mkdir_p($directory)) {
            throw new RuntimeException('Unable to create import directory.');
        }

        $files = [
            'index.php' => "<?php\n// Silence is golden.\n",
            '.htaccess' => "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n",
            'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n    <system.webServer>\n        <directoryBrowse enabled=\"false\" />\n        <security>\n            <authorization>\n                <remove users=\"*\" roles=\"\" verbs=\"\" />\n                <add accessType=\"Deny\" users=\"*\" />\n            </authorization>\n        </security>\n    </system.webServer>\n</configuration>\n",
        ];

        foreach ($files as $filename => $contents) {
            $target = trailingslashit($directory) . $filename;
            if (!file_exists($target) && file_put_contents($target, $contents, LOCK_EX) === false) {
                throw new RuntimeException('Unable to protect the import directory.');
            }
        }
    }
}
