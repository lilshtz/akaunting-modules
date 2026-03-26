<?php

namespace Modules\Inventory\Services;

use App\Models\Common\Item;
use InvalidArgumentException;
use Modules\Inventory\Models\Variant;

class BarcodeService
{
    public function forItem(Item $item, ?Variant $variant = null, string $format = 'code128'): array
    {
        $format = strtolower($format);
        $value = $this->generateValue($item, $variant, $format);

        return [
            'format' => strtoupper($format),
            'value' => $value,
            'label' => $variant ? $item->name . ' - ' . $variant->name : $item->name,
            'svg' => $this->renderSvg($value, $format),
        ];
    }

    public function labels(Item $item, array $variants = [], string $format = 'code128', array $options = []): array
    {
        $paperWidth = (int) ($options['width'] ?? 320);
        $paperHeight = (int) ($options['height'] ?? 120);

        $labels = [];

        if (empty($variants)) {
            $labels[] = array_merge($this->forItem($item, null, $format), [
                'width' => $paperWidth,
                'height' => $paperHeight,
            ]);
        }

        foreach ($variants as $variant) {
            $labels[] = array_merge($this->forItem($item, $variant, $format), [
                'width' => $paperWidth,
                'height' => $paperHeight,
            ]);
        }

        return $labels;
    }

    protected function generateValue(Item $item, ?Variant $variant, string $format): string
    {
        if ($format === 'ean13') {
            $seed = $variant?->sku ?: (string) $item->id;
            $base = str_pad(substr(preg_replace('/\D+/', '', $seed) ?: (string) $item->id, 0, 12), 12, '0', STR_PAD_LEFT);

            return $base . $this->ean13CheckDigit($base);
        }

        if ($format !== 'code128') {
            throw new InvalidArgumentException('Unsupported barcode format.');
        }

        $source = $variant?->sku ?: sprintf('ITEM-%d', $item->id);
        $value = strtoupper(substr(preg_replace('/[^A-Za-z0-9\-_\.]/', '', $source), 0, 32));

        return $value !== '' ? $value : sprintf('ITEM-%d', $item->id);
    }

    protected function ean13CheckDigit(string $base): int
    {
        $sum = 0;

        foreach (str_split($base) as $index => $digit) {
            $sum += ((int) $digit) * (($index % 2) === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }

    protected function renderSvg(string $value, string $format): string
    {
        return $format === 'ean13'
            ? $this->renderEan13($value)
            : $this->renderCode128($value);
    }

    protected function renderCode128(string $value): string
    {
        $patterns = [
            104 => '211214', 106 => '2331112',
            0 => '212222', 1 => '222122', 2 => '222221', 3 => '121223', 4 => '121322', 5 => '131222',
            6 => '122213', 7 => '122312', 8 => '132212', 9 => '221213', 10 => '221312', 11 => '231212',
            12 => '112232', 13 => '122132', 14 => '122231', 15 => '113222', 16 => '123122', 17 => '123221',
            18 => '223211', 19 => '221132', 20 => '221231', 21 => '213212', 22 => '223112', 23 => '312131',
            24 => '311222', 25 => '321122', 26 => '321221', 27 => '312212', 28 => '322112', 29 => '322211',
            30 => '212123', 31 => '212321', 32 => '232121', 33 => '111323', 34 => '131123', 35 => '131321',
            36 => '112313', 37 => '132113', 38 => '132311', 39 => '211313', 40 => '231113', 41 => '231311',
            42 => '112133', 43 => '112331', 44 => '132131', 45 => '113123', 46 => '113321', 47 => '133121',
            48 => '313121', 49 => '211331', 50 => '231131', 51 => '213113', 52 => '213311', 53 => '213131',
            54 => '311123', 55 => '311321', 56 => '331121', 57 => '312113', 58 => '312311', 59 => '332111',
            60 => '314111', 61 => '221411', 62 => '431111', 63 => '111224', 64 => '111422', 65 => '121124',
            66 => '121421', 67 => '141122', 68 => '141221', 69 => '112214', 70 => '112412', 71 => '122114',
            72 => '122411', 73 => '142112', 74 => '142211', 75 => '241211', 76 => '221114', 77 => '413111',
            78 => '241112', 79 => '134111', 80 => '111242', 81 => '121142', 82 => '121241', 83 => '114212',
            84 => '124112', 85 => '124211', 86 => '411212', 87 => '421112', 88 => '421211', 89 => '212141',
            90 => '214121', 91 => '412121', 92 => '111143', 93 => '111341', 94 => '131141', 95 => '114113',
            96 => '114311', 97 => '411113', 98 => '411311', 99 => '113141', 100 => '114131', 101 => '311141',
            102 => '411131', 103 => '211412', 105 => '211232',
        ];

        $codes = [104];
        $checksum = 104;

        foreach (str_split($value) as $position => $char) {
            $code = ord($char) - 32;

            if ($code < 0 || $code > 94) {
                throw new InvalidArgumentException('Code128 supports printable ASCII only.');
            }

            $codes[] = $code;
            $checksum += $code * ($position + 1);
        }

        $codes[] = $checksum % 103;
        $codes[] = 106;

        $bars = '';
        $x = 10;
        $unit = 2;

        foreach ($codes as $code) {
            $pattern = str_split($patterns[$code]);

            foreach ($pattern as $index => $width) {
                $width = (int) $width * $unit;

                if (($index % 2) === 0) {
                    $bars .= '<rect x="' . $x . '" y="10" width="' . $width . '" height="80" fill="#000"/>';
                }

                $x += $width;
            }
        }

        $width = $x + 10;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="110" viewBox="0 0 ' . $width . ' 110">' .
            '<rect width="100%" height="100%" fill="#fff"/>' . $bars .
            '<text x="' . ($width / 2) . '" y="105" text-anchor="middle" font-size="14" font-family="monospace">' . e($value) . '</text>' .
            '</svg>';
    }

    protected function renderEan13(string $value): string
    {
        $setA = ['0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101', '4' => '0100011', '5' => '0110001', '6' => '0101111', '7' => '0111011', '8' => '0110111', '9' => '0001011'];
        $setB = ['0' => '0100111', '1' => '0110011', '2' => '0011011', '3' => '0100001', '4' => '0011101', '5' => '0111001', '6' => '0000101', '7' => '0010001', '8' => '0001001', '9' => '0010111'];
        $setC = ['0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010', '4' => '1011100', '5' => '1001110', '6' => '1010000', '7' => '1000100', '8' => '1001000', '9' => '1110100'];
        $parity = ['0' => 'AAAAAA', '1' => 'AABABB', '2' => 'AABBAB', '3' => 'AABBBA', '4' => 'ABAABB', '5' => 'ABBAAB', '6' => 'ABBBAA', '7' => 'ABABAB', '8' => 'ABABBA', '9' => 'ABBABA'];

        $first = $value[0];
        $left = substr($value, 1, 6);
        $right = substr($value, 7, 6);
        $bits = '101';

        foreach (str_split($left) as $index => $digit) {
            $bits .= $parity[$first][$index] === 'A' ? $setA[$digit] : $setB[$digit];
        }

        $bits .= '01010';

        foreach (str_split($right) as $digit) {
            $bits .= $setC[$digit];
        }

        $bits .= '101';

        $bars = '';
        $x = 10;
        $unit = 2;

        foreach (str_split($bits) as $bit) {
            if ($bit === '1') {
                $bars .= '<rect x="' . $x . '" y="10" width="' . $unit . '" height="80" fill="#000"/>';
            }

            $x += $unit;
        }

        $width = $x + 10;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="110" viewBox="0 0 ' . $width . ' 110">' .
            '<rect width="100%" height="100%" fill="#fff"/>' . $bars .
            '<text x="' . ($width / 2) . '" y="105" text-anchor="middle" font-size="14" font-family="monospace">' . e($value) . '</text>' .
            '</svg>';
    }
}
