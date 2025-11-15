<?php

declare(strict_types=1);

namespace App\Services\AWS\Textract\Helpers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\UnitPrice;

class AnalyzeExpenseHelper
{
    public function __construct() {}

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

        if (!empty($map['VENDOR_NAME'])) {
            $vendorId = $this->lookupVendorIdByName((string) $map['VENDOR_NAME']);
            if ($vendorId !== null) {
                $map['VENDOR_ID'] = $vendorId;
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

                $row = [
                    'is_existing'            => false,
                    'product'                => null,
                    'quantity'               => null,
                    'unit_price'             => null,
                    'price'                  => null,
                    'unit_name'              => null,

                    'unit_id'                => null,
                    'package_size'           => null,

                    'existing_product_id'    => null,
                    'existing_product_code'  => null,
                    'existing_product_name'  => null,

                    // القائمة المطلوبة للوحدات المرتبطة عبر UnitPrice
                    'available_units'        => [],
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
                            if ($row['unit_name'] === null && !empty($textBucket)) {
                                $candidate = $this->detectUnitByContext(implode(' ', $textBucket), $units);
                                if ($candidate !== null) {
                                    $row['unit_name'] = $candidate['label'];
                                    $row['unit_id']   = $candidate['id'];
                                }
                            } else {
                                if ($row['unit_id'] === null && !empty($textBucket)) {
                                    $candidate = $this->detectUnitByContext(implode(' ', $textBucket), $units);
                                    if ($candidate !== null) {
                                        $row['unit_id'] = $candidate['id'];
                                    }
                                }
                            }
                            break;

                        case 'EXPENSE_ROW':
                        case 'OTHER':
                            $textBucket[] = $val;

                            if ($row['product'] === null && preg_match('/\p{Arabic}/u', $val)) {
                                $row['product'] = $val;
                            }

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
                        $row['unit_id']   = $candidate['id'];
                    }
                }

                // مطابقة المنتج
                $match = !empty($row['product']) ? Product::bestNameMatch((string) $row['product']) : null;

                if ($match instanceof \App\Models\Product) {
                    $row['is_existing']           = true;
                    $row['existing_product_id']   = $match->id;
                    $row['existing_product_code'] = $match->code;
                    $row['existing_product_name'] = $match->name;

                    // ⬇️ إحضار الوحدات المرتبطة عبر UnitPrice وإرفاقها مع السطر
                    $unitPrices = $match->unitPrices()
                        ->with('unit')
                        ->forOperations() // يمكنك تبديلها بـ usableInManufacturing/forSupply/forOut حسب السياق
                        ->orderByRaw('COALESCE(package_size, 999999) ASC')
                        ->get(['id', 'unit_id', 'product_id', 'price', 'package_size', 'usage_scope', 'selling_price', 'show_in_invoices', 'use_in_orders'])
                        ->map(function (UnitPrice $up) {
                            return [
                                'unit_price_id'    => (int) $up->id,
                                'unit_id'          => (int) $up->unit_id,
                                'unit_name'        => (string) optional($up->unit)->name,
                                'price'            => (float) $up->price,
                                'selling_price'    => (float) ($up->selling_price ?? 0),
                                'package_size'     => $up->package_size === null ? null : (float) $up->package_size,
                                'usage_scope'      => (string) $up->usage_scope,
                                'show_in_invoices' => (int) ($up->show_in_invoices ?? 0),
                                'use_in_orders'    => (int) ($up->use_in_orders ?? 0),
                            ];
                        })
                        ->values();

                    // ... بعد:
                    $row['available_units'] = $unitPrices->all();

                    // ✅ تمييز ما إذا كانت وحدة الفاتورة ضمن الوحدات المتاحة
                    $row['unit_in_available']      = false;
                    $row['matched_unit_price_id']  = null;
                    $row['matched_unit_details']   = null;
                    $row['unit_match_confidence']  = null;

                    // لدينا حالتان: نملك unit_id، أو فقط label/اسم وحدة
                    $unitIdFromInvoice   = $row['unit_id'];    // قد يكون null
                    $unitLabelFromInvoice = $row['unit_name'];  // قد يكون null

                    // dd($unitLabelFromInvoice);
                    $available = collect($row['available_units']);

                    if ($unitIdFromInvoice) {
                        // مطابقة مباشرة بالـ unit_id
                        $match = $available->firstWhere('unit_id', (int)$unitIdFromInvoice);
                        if ($match) {
                            $row['unit_in_available']     = true;
                            $row['matched_unit_price_id'] = (int)$match['unit_price_id'];
                            $row['matched_unit_details']  = $match;
                            $row['unit_match_confidence'] = 1.0; // مطابق ID صريح
                        }
                    }

                    // إن لم نجد و لدينا label من الفاتورة، جرّب مطابقة اسمية (تطبيع خفيف)
                    if (!$row['unit_in_available'] && $unitLabelFromInvoice) {
                        $norm = fn($s) => strtoupper(preg_replace('/[^A-Z]/', '', (string)$s));
                        $target = $norm($unitLabelFromInvoice);

                        // طابق على unit_name ومرادفات شائعة في available_units
                        $match = $available->first(function ($u) use ($target, $norm) {
                            $candidates = array_filter([
                                $norm($u['unit_name'] ?? ''),
                                $norm($u['usage_scope'] ?? ''), // ليس اسم وحدة لكنه قد يساعد نادرًا
                            ]);
                            foreach ($candidates as $c) {
                                // تطابق قوي أو تشابه مرتفع
                                similar_text($target, $c, $p);
                                $lev = levenshtein($target, $c);
                                if ($target === $c || $p >= 90 || ($lev <= 1 && max(strlen($target), strlen($c)) <= 5)) {
                                    return true;
                                }
                            }
                            return false;
                        });

                        if ($match) {
                            $row['unit_in_available']     = true;
                            $row['matched_unit_price_id'] = (int)$match['unit_price_id'];
                            $row['matched_unit_details']  = $match;
                            $row['unit_match_confidence'] = 0.8; // مطابق اسمي/تقريبي
                        }
                    }
                }

                // إن كان لدينا match + unit_id، حاول إيجاد package_size المناسب لهذه الوحدة
                if ($row['is_existing'] && $row['existing_product_id'] && $row['unit_id']) {
                    $row['package_size'] = UnitPrice::where('product_id', $row['existing_product_id'])
                        ->where('unit_id', $row['unit_id'])
                        ->value('package_size');
                }

                // لا نضيف صفوفًا فارغة
                if ($row['product'] !== null || $row['price'] !== null) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /* ===================== Unit Resolver (exact + fuzzy) ===================== */

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

    /**
     * يبحث عن المورّد بالاسم بجزء من النص (LIKE %...%)
     * ويرجّع id أو null إن لم يُعثر عليه.
     */
    private function lookupVendorIdByName(string $vendorName): ?int
    {
        $name = trim(preg_replace('/\s+/', ' ', $vendorName));
        if ($name === '') return null;

        $escaped = addcslashes($name, '%_');

        $query = Supplier::query()
            ->where(function ($q) use ($escaped) {
                $q->where('name', 'LIKE', "%{$escaped}%");
            })
            ->orderByRaw('CHAR_LENGTH(name) asc')
            ->limit(1);

        $id = $query->value('id');

        if ($id === null) {
            $tokens = preg_split('/\s+/u', $name) ?: [];
            $tokens = array_values(array_filter($tokens, fn($t) => mb_strlen($t, 'UTF-8') >= 3));
            usort($tokens, fn($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

            foreach ($tokens as $tok) {
                $tokEsc = addcslashes($tok, '%_');
                $id = Supplier::where('name', 'LIKE', "%{$tokEsc}%")
                    ->orderByRaw('CHAR_LENGTH(name) asc')
                    ->value('id');
                if ($id !== null) break;
            }
        }

        return $id !== null ? (int) $id : null;
    }
}
