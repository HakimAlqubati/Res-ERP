<?php

declare(strict_types=1);

namespace App\Services\AWS\Textract\Helpers;

final class ParseUtils
{
    /** تطبيع نص للمقارنة: أحرف فقط Upper (لاتيني) */
    public static function n(?string $s): string
    {
        $s = $s ?? '';
        $s = strtoupper($s);
        $s = preg_replace('/[^A-Z]/', '', $s) ?? '';
        return $s;
    }

    /** تحويل نص إلى رقم (يدعم الفاصلة كعُشرية أو كفاصل آلاف بشكل مبسّط) */
    public static function toNumber(?string $s): ?float
    {
        if ($s === null) return null;
        $clean = preg_replace('/[^\d\-\.\,]/', '', $s) ?? '';
        if (substr_count($clean, ',') === 1 && substr_count($clean, '.') === 0) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
        return is_numeric($clean) ? (float) $clean : null;
    }

    /** يستخرج توكنات مرشّحة كوحدات (أحرف فقط، حتى 6) من النص */
    public static function tokenizePotentialUoms(string $text): array
    {
        preg_match_all('/\b[[:alpha:]]{1,6}\b/u', $text, $m);
        $tokens = array_map(static fn($t) => strtoupper($t), $m[0] ?? []);
        return array_values(array_unique($tokens));
    }

    /** توسيع المرشّحات بإضافة مرادفات عامة */
    public static function expandAliases(array $tokens): array
    {
        $aliases = self::defaultAliases();
        $out = [];
        foreach ($tokens as $t) {
            $n = self::n($t);
            $out[] = strtoupper($t);
            if (isset($aliases[$n])) {
                foreach ($aliases[$n] as $alt) $out[] = strtoupper($alt);
            }
        }
        return array_values(array_unique($out));
    }

    /** مرادفات/اختصارات جاهزة (قابلة للتوسعة). المفاتيح/القيم بصيغة normalized. */
    public static function defaultAliases(): array
    {
        $map = [
            // القطع
            'PC'      => ['PIECE', 'PIECES', 'PCS', 'PCE', 'EA', 'EACH'],
            'PCS'     => ['PIECE', 'PIECES', 'PC', 'PCE', 'EA', 'EACH'],
            'PIECE'   => ['PC', 'PCS', 'PCE', 'EA', 'EACH', 'PIECES'],
            'EACH'    => ['EA', 'PC', 'PCS', 'PIECE'],

            // الكراتين
            'CTN'     => ['CARTON', 'CTNS', 'CARTONS', 'CTN.'],
            'CARTON'  => ['CTN', 'CTNS', 'CARTONS'],

            // الأوزان
            'KG'      => ['KGS', 'KILOGRAM', 'KILOGRAMS', 'KILO'],
            'KGS'     => ['KG', 'KILOGRAM', 'KILOGRAMS', 'KILO'],
            'GRAM'    => ['G', 'GR', 'GRAMS'],
            'LITER'   => ['L', 'LTR', 'LITRE', 'LITERS', 'LITRES'],

            // أحجام شائعة
            'PACK'    => ['PK', 'PKT', 'PACKS', 'PACKAGE', 'PKG'],
            'BOX'     => ['BX', 'BOXES'],
        ];

        // تطبيع المفاتيح والقيم
        $norm = [];
        foreach ($map as $k => $vals) {
            $nk = self::n($k);
            $norm[$nk] = array_map(static fn($v) => self::n($v) ?: $v, $vals);
        }
        return $norm;
    }

    /** صيغ جمع مبسطة */
    public static function pluralize(string $name): array
    {
        $n = self::n($name);
        if ($n === '') return [];
        $out = [];
        if (!str_ends_with($n, 'S')) $out[] = $name . 's';
        if (str_ends_with($n, 'Y'))  $out[] = substr($name, 0, -1) . 'ies';
        return $out;
    }

    /** Levenshtein */
    public static function lev(string $a, string $b): int
    {
        return levenshtein($a, $b);
    }

    /** نسبة تشابه 0..1 باستخدام similar_text */
    public static function similarity(string $a, string $b): float
    {
        similar_text($a, $b, $p);
        return $p / 100.0;
    }
}
