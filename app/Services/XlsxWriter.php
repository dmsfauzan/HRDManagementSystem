<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Penulis XLSX minimal tanpa dependensi eksternal (mengandalkan ekstensi ZIP).
 */
class XlsxWriter
{
    public static function download(string $filename, array $headers, iterable $rows): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'hris_xlsx_');

        $zip = new \ZipArchive;

        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat berkas XLSX.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($headers, $rows));
        $zip->close();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private static function sheet(array $headers, iterable $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $xml .= self::row(1, $headers);

        $r = 2;
        foreach ($rows as $row) {
            $xml .= self::row($r, is_array($row) ? $row : $row->toArray());
            $r++;
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function row(int $r, array $values): string
    {
        $xml = '<row r="'.$r.'">';

        $index = 0;
        foreach ($values as $value) {
            $ref = self::columnLetter($index).$r;

            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
            } else {
                $xml .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.self::escape((string) $value).'</t></is></c>';
            }
            $index++;
        }

        return $xml.'</row>';
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';

        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26) - 1;
        }

        return $letter;
    }

    private static function escape(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Laporan" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }
}
