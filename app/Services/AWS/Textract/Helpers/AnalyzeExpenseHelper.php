<?php

declare(strict_types=1);

namespace App\Services\AWS\Textract\Helpers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPrice;

class AnalyzeExpenseHelper
{
    public function __construct() {} // لا كاش هنا

    /* ===================== Summary ===================== */

    public function extractSummary(array $doc, ?array $wanted = null): array
    {
        $wanted ??= config('services.textract.summary_fields', [
            'VENDOR_NAME',
            'RECEIVER_NAME',
            'INVOICE_RECEIPT_ID',
            'INVOICE_RECEIPT_DATE',
            'DUE_DATE',
            'SUBTOTAL',
            'TOTAL',
            'CURRENCY',
        ]);

        $map = [];
        foreach (($doc['SummaryFields'] ?? []) as $sf) {
            $type  = $sf['Type']['Text'] ?? ($sf['Type']['NormalizedValue']['Value'] ?? null);
            $value = $sf['ValueDetection']['Text'] ?? null;

            if ($type && $value && in_array($type, $wanted, true)) {
                $map[$type] = $value;
            }
        }
        return $map;
    }

    /* ===================== Line Items ===================== */
    public function extractItems(array $doc): array
    {
        $rows  = [];
        // id, code, name, label, regex, n_code, n_name, aliases
        $units = $this->loadUnitsDictionary();

        foreach (($doc['LineItemGroups'] ?? []) as $group) {
            foreach (($group['LineItems'] ?? []) as $li) {

                // نضيف الحقول الجديدة من البداية
                $row = [
                    'is_existing'            => false, // بوليان في بداية الـ object
                    'product'                => null,
                    'quantity'               => null,
                    'unit_price'             => null,
                    'price'                  => null,
                    'unit_name'              => null,

                    'unit_id'                => null,
                    'package_size'           => null,

                    // الحقول الجديدة الخاصة بالمطابقة مع المنتجات الموجودة
                    'existing_product_id'    => null,
                    'existing_product_code'  => null,
                    'existing_product_name'  => null,
                ];

                $textBucket = [];

                foreach (($li['LineItemExpenseFields'] ?? []) as $f) {
                    $type = $f['Type']['Text'] ?? null;
                    $val  = $f['ValueDetection']['Text'] ?? null;
                    if (!$type || $val === null) continue;

                    switch ($type) {
                        case 'ITEM':
                            $row['product'] = $val;
                            $textBucket[]   = $val;
                            break;

                        case 'QUANTITY':
                            $row['quantity'] = ParseUtils::toNumber($val);
                            $textBucket[]    = $val;
                            break;

                        case 'UNIT_PRICE':
                            $row['unit_price'] = ParseUtils::toNumber($val);
                            break;

                        case 'PRICE':
                            $row['price'] = ParseUtils::toNumber($val);
                            break;

                        case 'UNIT':
                        case 'UOM':
                        case 'MEASURE':
                        case 'UNIT_OF_MEASURE':
                            $textBucket[]     = $val;
                            // إن لم تُحسم الوحدة بعد، جرّب استنتاجها من النص العام
                            if ($row['unit_name'] === null && !empty($textBucket)) {
                                $candidate = $this->detectUnitByContext(implode(' ', $textBucket), $units);
                                if ($candidate !== null) {
                                    $row['unit_name'] = $candidate['label'];
                                    $row['unit_id']   = $candidate['id']; // ⬅️ جديد
                                }
                            } else {
                                // حتى لو حصلنا على unit_name سابقًا، حاول جلب id من النص العام لتوحيد المرجع
                                if ($row['unit_id'] === null && !empty($textBucket)) {
                                    $candidate = $this->detectUnitByContext(implode(' ', $textBucket), $units);
                                    if ($candidate !== null) {
                                        $row['unit_id'] = $candidate['id']; // ⬅️ جديد
                                        // لا نغيّر unit_name إن كان مضبوطًا
                                    }
                                }
                            }
                          
                            break;

                        case 'EXPENSE_ROW':
                        case 'OTHER':
                            $textBucket[] = $val;

                            // التقط الاسم العربي مباشرة عند غياب ITEM
                            if ($row['product'] === null && preg_match('/\p{Arabic}/u', $val)) {
                                $row['product'] = $val; // بدون تطبيع
                            }

                            // إن لم نجد منتجًا صريحًا، حاول اختيار أفضل اسم من النصوص
                            if ($row['product'] === null && $textBucket) {
                                $row['product'] = $this->pickBestProduct($textBucket);
                            }
                            break;

                        default:
                            break;
                    }
                }

                // إن لم تُحسم الوحدة بعد، جرّب استنتاجها من النص العام
                if ($row['unit_name'] === null && !empty($textBucket)) {
                    $candidate = $this->detectUnitByContext(implode(' ', $textBucket), $units);
                    if ($candidate !== null) {
                        $row['unit_name'] = $candidate['label'];
                        $row['unit_id'] = $candidate['id'];
                    }
                }
 
                // ✨ إضافة: البحث عن منتج مقارب جدًا في قاعدة البيانات
                // if (!empty($row['product'])) {
                // يمكنك تعديل (0.80) لرفع/خفض حساسية التطابق، و(25) لعدد المرشحين من الداتابيس
                $match = Product::bestNameMatch((string) $row['product']); // returns Product|null

                if ($match instanceof \App\Models\Product) {
                    $row['is_existing']           = true;
                    $row['existing_product_id']   = $match->id;
                    $row['existing_product_code'] = $match->code;
                    $row['existing_product_name'] = $match->name;
                }
                if ($row['is_existing'] && $row['existing_product_id'] && $row['unit_id']) {
                    $row['package_size'] = UnitPrice::where('product_id', $row['existing_product_id'])
                        ->where('unit_id', $row['unit_id'])
                        ->value('package_size');
                }
                // }

                // لا نضيف صفوفًا فارغة
                if ($row['product'] !== null || $row['price'] !== null) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }


    /* ===================== Unit Resolver (exact + fuzzy) ===================== */

    /** يحوّل قيمة خام للوحدة (مثل "PC") إلى label من جدول الوحدات باستخدام مرادفات وتشابه. */
    private function resolveUnitName(?string $raw, string $context, array $units): ?string
    {
        $raw = ParseUtils::n($raw);
        if ($raw === '') {
            return $this->detectUnitByContext($context, $units)['label'] ?? null;
        }

        // 1) محاولات مباشرة: مرادفات + توكنات محتملة
        $tokenCandidates = ParseUtils::expandAliases([$raw]);
        $tokenCandidates = array_unique(array_merge(
            $tokenCandidates,
            ParseUtils::tokenizePotentialUoms($raw)
        ));

        // 2) طابق مباشرة عبر خصائص الوحدة
        foreach ($tokenCandidates as $tok) {
            foreach ($units as $u) {
                if ($this->matchesAny($tok, $u)) {
                    return $u['label'];
                }
            }
        }

        // 3) Fuzzy على الاسم/الكود
        $best = $this->fuzzyMatchTokenAgainstUnits($raw, $units);
        if ($best !== null) return $best['label'];

        // 4) جرب من النص المحيط
        $ctxGuess = $this->detectUnitByContext($context, $units);
        return $ctxGuess['label'] ?? null;
    }

    /** استنتاج الوحدة من وصف البند العام. */
    private function detectUnitByContext(string $text, array $units): ?array
    {
        $tokens = ParseUtils::tokenizePotentialUoms($text);
        $tokens = ParseUtils::expandAliases($tokens);

        // Exact
        foreach ($tokens as $tok) {
            foreach ($units as $u) {
                if ($this->matchesAny($tok, $u)) {
                    return ['id' => $u['id'], 'label' => $u['label']];
                }
            }
        }

        // Fuzzy
        foreach ($tokens as $tok) {
            $best = $this->fuzzyMatchTokenAgainstUnits($tok, $units);
            if ($best !== null) return ['id' => $best['id'], 'label' => $best['label']];
        }

        return null;
    }

    /** هل التوكن يطابق كود/اسم/مرادفات الوحدة مباشرة؟ */
    private function matchesAny(string $token, array $unit): bool
    {
        $t = ParseUtils::n($token);
        if ($t === '') return false;

        if (($unit['n_code'] ?? null) && $t === $unit['n_code']) return true;
        if ($t === ($unit['n_name'] ?? '')) return true;

        foreach ($unit['aliases'] as $al) {
            if ($t === ParseUtils::n($al)) return true;
        }

        // regex للكود/الاسم ككلمات كاملة
        return (bool) preg_match($unit['regex'], $token);
    }

    /** مطابقة غامضة على الاسم/الكود الطبيعي للوحدة */
    private function fuzzyMatchTokenAgainstUnits(string $token, array $units): ?array
    {
        $t = ParseUtils::n($token);
        if ($t === '') return null;

        $best  = null;
        $score = 0.0;

        foreach ($units as $u) {
            $candidates = array_filter([$u['n_code'], $u['n_name']]);

            foreach ($candidates as $cand) {
                $s  = ParseUtils::similarity($t, $cand);
                $ok = ($s >= 0.78) || (ParseUtils::lev($t, $cand) <= 1 && max(strlen($t), strlen($cand)) <= 5);
                if ($ok && $s > $score) {
                    $score = $s;
                    $best = $u;
                }
            }

            foreach ($u['aliases'] as $al) {
                $cand = ParseUtils::n($al);
                $s    = ParseUtils::similarity($t, $cand);
                $ok   = ($s >= 0.78) || (ParseUtils::lev($t, $cand) <= 1 && max(strlen($t), strlen($cand)) <= 5);
                if ($ok && $s > $score) {
                    $score = $s;
                    $best = $u;
                }
            }
        }

        return $best;
    }

    /* ===================== Units Dictionary (بدون كاش) ===================== */

    /**
     * @return array<int, array{
     *   id:int, code:?string, name:string, label:string, regex:string,
     *   n_code:?string, n_name:string, aliases:array<int,string>
     * }>
     */
    private function loadUnitsDictionary(): array
    {
        $common = ParseUtils::defaultAliases();

        return Unit::query()
            ->active()
            ->get(['id', 'code', 'name'])
            ->map(function (Unit $u) use ($common) {
                $label = $u->code ?: $u->name;

                $tokens = array_unique(array_filter([
                    $u->code ? preg_quote($u->code, '/') : null,
                    $u->name ? preg_quote($u->name, '/') : null,
                ]));

                if ($u->name && str_contains($u->name, ' ')) {
                    $nameVariant = preg_quote(preg_replace('/\s+/', '[-\s]', $u->name), '/');
                    $tokens[] = $nameVariant;
                }

                // ملاحظة: يمكن إضافة /u لاحقاً للأسماء غير اللاتينية
                $pattern = '/\b(' . implode('|', $tokens) . ')\b/i';

                $nCode = ParseUtils::n($u->code ?? '');
                $nName = ParseUtils::n($u->name);

                $aliases = array_values(array_unique(array_filter(array_merge(
                    $common[$nCode] ?? [],
                    $common[$nName] ?? [],
                    ParseUtils::pluralize($u->name),
                    $u->code ? [$u->code] : []
                ))));

                return [
                    'id'      => $u->id,
                    'code'    => $u->code,
                    'name'    => $u->name,
                    'label'   => strtoupper($label),
                    'regex'   => $pattern,
                    'n_code'  => $nCode ?: null,
                    'n_name'  => $nName,
                    'aliases' => $aliases,
                ];
            })
            ->values()
            ->all();
    }

    private function pickBestProduct(array $bucket): ?string
    {
        // فضّل المقاطع العربية الأطول
        $arabic = array_values(array_filter($bucket, fn($t) => preg_match('/\p{Arabic}/u', $t)));
        if ($arabic) {
            usort($arabic, fn($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));
            return $arabic[0];
        }
        // وإلا خذ أطول نص غير رقمي
        $candidates = array_values(array_filter($bucket, fn($t) => !preg_match('/^\s*[\d\.\,%-]+\s*$/u', $t)));
        if ($candidates) {
            usort($candidates, fn($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));
            return $candidates[0];
        }
        return null;
    }
}
