<?php
declare(strict_types=1);

namespace BusinessMapLocator\Support;

final class SlugGenerator
{
    private const MAP = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
        'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'і' => 'i', 'ї' => 'yi', 'є' => 'ye', 'ґ' => 'g', 'ў' => 'u',
    ];

    public static function fromTerm(string $name, string $provided = ''): string
    {
        $source = trim($provided) !== '' ? $provided : $name;
        $source = rawurldecode($source);

        if (function_exists('mb_strtolower')) {
            $source = mb_strtolower($source, 'UTF-8');
        } else {
            $source = strtolower($source);
        }

        $source = strtr($source, self::MAP);
        if (function_exists('remove_accents')) {
            $source = remove_accents($source);
        }

        $source = preg_replace('/[^a-z0-9]+/i', '-', $source) ?: '';
        $source = trim(strtolower($source), '-');

        return $source !== '' ? $source : 'term';
    }
}
