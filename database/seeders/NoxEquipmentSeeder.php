<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Sport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class NoxEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $padel = Sport::query()->where('slug', 'padel')->firstOrFail();

        $items = collect($this->items())->keyBy('sku');

        foreach ($this->excelItems() as $item) {
            if (! $items->has($item['sku'])) {
                $items->put($item['sku'], $item);
            }
        }

        foreach ($items as $item) {
            $image = $item['image'];
            $imagePath = $image;
            $sourcePath = $image && ! str_starts_with($image, 'equipment/')
                ? database_path("seeders/assets/nox-equipment/{$image}")
                : null;

            if ($sourcePath && is_file($sourcePath)) {
                $imagePath = "equipment/nox/{$image}";
                Storage::disk('public')->put($imagePath, file_get_contents($sourcePath));
            }

            Equipment::query()->updateOrCreate(
                ['sku' => $item['sku']],
                [
                    ...$item,
                    'sport_id' => $padel->id,
                    'image' => $imagePath,
                    'stock_quantity' => 10,
                    'is_active' => true,
                ],
            );
        }
    }

    /** @return array<int, array<string, int|string|bool|null>> */
    private function items(): array
    {
        return [
            $this->sale('PAT10GENIUS12LI26', 'AT10 GENIUS 12K ALUM Xtrem LITE by Agustin Tapia', 39800, 'pat10genius12li26.png'),
            $this->sale('PAT10GENIUSAT1826', 'AT10 GENIUS ATTACK 18K ALUM Xtrem by Agustin Tapia', 42150, 'pat10geniusat1826.png'),
            $this->sale('PEA10VENTUSH1226', 'EA10 VENTUS HYBRID 12K Xtrem by Edu Alonso', 39800, 'pea10ventush1226.png'),
            $this->sale('PVENTUS12HL', 'VENTUS HYBRID 12K LITE', 35000, 'pventus12hl.png'),
            $this->sale('PNFANGHYB26', 'NEXTGEN PRO HYBRID 3K 2026', 24000, 'pnfanghyb26.png'),

            $this->rental('PAT10PCH26', 'AT10 PRO CUP HARD by Agustin Tapia 2026', 'pat10pch26.png'),
            $this->rental('PEQUHADV26', 'EQUATION HARD ADVANCED 2026', 'pequhadv26.png', 'Premium rental reket za napredne rekreativce.'),
            $this->rental('PXZERORE', 'X-ZERO RED 2026', 'pxzerore.png'),
            $this->rental('PXZEROBL', 'X-ZERO BLUE 2026', 'pxzerobl.png'),

            $this->sale('CAL26LUXWHGR', 'AT10 LUX WHITE/GREY SHOES', 17400, 'cal26luxwhgr.jpg', 'Patike za padel. Dostupne veličine 36–48.'),
            $this->sale('CAL26LUXFGRA', 'AT10 LUX FEATHER GRAY/RAVEN SHOES', 17400, 'cal26luxfgra.jpg', 'Patike za padel. Dostupne veličine 39–48.'),
            $this->sale('MOCPROSCAMEL', 'PRO SERIES CAMEL BACKPACK', 7900, 'mocproscamel.jpg'),
            $this->sale('BPAT10TEWH', 'AT10 TEAM WHITE PADELBAG', 8900, 'bpat10tewh.jpg'),

            $this->sale('PRTNXNEBLBAG', 'BAG OF 6 NOX BLACK PROTECTORS', 1100, null),
            $this->sale('PRTNXROBLBAG', 'BAG OF 6 NOX RED PROTECTORS', 1100, null),
            $this->sale('PRTNXAZBLBAG', 'BAG OF 6 NOX BLUE PROTECTORS', 1100, null),
            $this->sale('MUBLAM2UDBOX', 'BLISTER WITH 2 WHITE/BLUE LOGO WRISTBANDS', 900, null, 'Pakovanje sadrži šest blistera.'),
            $this->sale('MULBLNEG2UDBOX', 'BAG WITH 2 WHITE/BLACK LOGO LONG WRISTBANDS', 900, null, 'Pakovanje sadrži šest blistera.'),
            $this->sale('OVPRO120BL', 'CAN WITH 120 WHITE PRO OVERGRIPS', 300, 'ovpro120bl.png'),
            $this->sale('CAHMCNLVBLBAG', 'MID LENGTH BLACK/WHITE TECHNICAL SOCKS', 799, null, 'Muške čarape 39–45. Pakovanje od šest pari.'),
            $this->sale('CAHMCBLVAZBAG', 'MID LENGTH WHITE/BLUE TECHNICAL SOCKS', 799, null, 'Muške čarape 39–45. Pakovanje od šest pari.'),
            $this->sale('BOTNOXWH', 'WHITE NOX BOTTLE 550 ml', 1560, 'botnoxwh.jpg', 'Aluminijumska flašica od 550 ml.'),
            $this->sale('BOLS20LLAVGENIUS1226', 'BAG WITH 20 AT10 GENIUS 12K 26 RUBBER KEYCHAINS', 720, 'bols20llavgenius1226.png'),
            $this->sale('BOLS20LLAVGENIUS1826', 'BAG WITH 20 AT10 GENIUS 18K 26 RUBBER KEYCHAINS', 720, 'bols20llavgenius1826.png'),

            $this->sale('T24CASPATBL', 'CAMISETA SPONSORS AT10 BLACK', 6700, 't24caspatbl.png', 'Majica. Dostupne veličine XS–XXL.'),
            $this->sale('T26SSHCAWG', "PRO WHITE/IVY GREEN MEN'S T-SHIRT", 5940, 't26sshcawg.png', 'Muška majica. Dostupne veličine S–XXL.'),
            $this->sale('T26SSHSHIG', "MEN'S PRO IVY GREEN SHORTS", 6600, 't26sshshig.png', 'Muški šorts. Dostupne veličine S–XXL.'),
        ];
    }

    /**
     * Imports every product family from the red and yellow NOX catalog blocks.
     * Green rows are represented by the manually curated rental items above.
     *
     * @return array<int, array<string, int|string|bool|null>>
     */
    private function excelItems(): array
    {
        $catalogPath = database_path('seeders/assets/nox-equipment-catalog.xlsx');

        if (! is_file($catalogPath)) {
            throw new RuntimeException('NOX equipment catalog file is missing.');
        }

        $zip = new ZipArchive;

        if ($zip->open($catalogPath) !== true) {
            throw new RuntimeException('Unable to read the NOX equipment catalog.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $images = $this->catalogImages($zip);
            $sheets = [
                'sheet2.xml' => ['code' => 'B', 'name' => 'C', 'prices' => ['F']],
                'sheet3.xml' => ['code' => 'B', 'name' => 'D', 'prices' => ['E']],
                'sheet4.xml' => ['code' => 'B', 'name' => 'H', 'prices' => ['F', 'E']],
                'sheet5.xml' => ['code' => 'B', 'name' => 'E', 'prices' => ['I']],
                'sheet6.xml' => ['code' => 'B', 'name' => 'C', 'prices' => ['F']],
                'sheet7.xml' => ['code' => 'B', 'name' => 'C', 'prices' => ['F']],
                'sheet8.xml' => ['code' => 'C', 'name' => 'D', 'prices' => ['G']],
                'sheet9.xml' => ['code' => 'C', 'name' => 'D', 'prices' => ['G']],
                'sheet10.xml' => ['code' => 'B', 'name' => 'H', 'prices' => ['E']],
                'sheet11.xml' => ['code' => 'B', 'name' => 'G', 'prices' => ['E']],
            ];
            $items = [];

            foreach ($sheets as $sheet => $columns) {
                $xml = $zip->getFromName("xl/worksheets/{$sheet}");

                if ($xml === false) {
                    continue;
                }

                foreach ($this->sheetRows($xml, $sharedStrings) as $row) {
                    $sku = trim((string) ($row[$columns['code']] ?? ''));
                    $name = trim(preg_replace('/\s+/', ' ', (string) ($row[$columns['name']] ?? '')) ?? '');
                    $price = collect($columns['prices'])
                        ->map(fn (string $column) => $row[$column] ?? null)
                        ->first(fn (mixed $value) => is_numeric($value));

                    if ($sku === '' || $name === '' || ! is_numeric($price)) {
                        continue;
                    }

                    $items[$sku] ??= $this->sale(
                        $sku,
                        $name,
                        (int) round((float) $price),
                        $this->storeCatalogImage($images[$sheet][(int) $row['__row']] ?? null, $sku),
                    );
                }
            }

            return array_values($items);
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $document = new SimpleXMLElement($xml);
        $document->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return array_map(
            fn (SimpleXMLElement $string): string => trim(html_entity_decode(strip_tags($string->asXML()), ENT_QUOTES | ENT_XML1)),
            $document->xpath('//main:si') ?: [],
        );
    }

    /** @return array<int, array<string, string|float>> */
    private function sheetRows(string $xml, array $sharedStrings): array
    {
        $document = new SimpleXMLElement($xml);
        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $worksheet = $document->children($namespace);
        $rows = [];

        foreach ($worksheet->sheetData->row as $row) {
            $values = ['__row' => (string) $row->attributes()['r']];

            foreach ($row->children($namespace)->c as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) $attributes['r'];
                $column = preg_replace('/\d+/', '', $reference);
                $value = (string) ($cell->children($namespace)->v ?? '');

                if ((string) $attributes['t'] === 's' && $value !== '') {
                    $value = $sharedStrings[(int) $value] ?? '';
                }

                $values[$column] = $value;
            }

            $rows[] = $values;
        }

        return $rows;
    }

    /** @return array<string, array<int, array{contents: string, extension: string}>> */
    private function catalogImages(ZipArchive $zip): array
    {
        $images = [];

        for ($sheetNumber = 2; $sheetNumber <= 11; $sheetNumber++) {
            $sheet = "sheet{$sheetNumber}.xml";
            $sheetXml = $zip->getFromName("xl/worksheets/{$sheet}");
            $sheetRelations = $zip->getFromName("xl/worksheets/_rels/{$sheet}.rels");

            if ($sheetXml === false || $sheetRelations === false) {
                continue;
            }

            $worksheet = new SimpleXMLElement($sheetXml);
            $relationships = new SimpleXMLElement($sheetRelations);
            $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            $drawingId = (string) ($worksheet->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->drawing
                ->attributes($relationshipNamespace)['id'] ?? '');

            if ($drawingId === '') {
                continue;
            }

            $drawingTarget = $this->relationshipTarget($relationships, $drawingId);

            if ($drawingTarget === null) {
                continue;
            }

            $drawingName = basename($drawingTarget);
            $drawingXml = $zip->getFromName("xl/drawings/{$drawingName}");
            $drawingRelationsXml = $zip->getFromName("xl/drawings/_rels/{$drawingName}.rels");

            if ($drawingXml === false || $drawingRelationsXml === false) {
                continue;
            }

            $drawing = new SimpleXMLElement($drawingXml);
            $drawingRelations = new SimpleXMLElement($drawingRelationsXml);
            $drawingNamespace = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';
            $pictureNamespace = 'http://schemas.openxmlformats.org/drawingml/2006/main';

            foreach ($drawing->children($drawingNamespace) as $anchor) {
                $from = $anchor->children($drawingNamespace)->from;
                $rowNumber = (int) ($from->children($drawingNamespace)->row ?? -1) + 1;
                $blip = $anchor->xpath('.//*[local-name()="blip"]')[0] ?? null;

                if (! $blip instanceof SimpleXMLElement || $rowNumber < 1) {
                    continue;
                }

                $embedId = (string) $blip->attributes($relationshipNamespace)['embed'];
                $imageTarget = $this->relationshipTarget($drawingRelations, $embedId);

                if ($imageTarget === null) {
                    continue;
                }

                $imageName = basename($imageTarget);
                $contents = $zip->getFromName("xl/media/{$imageName}");

                if ($contents === false) {
                    continue;
                }

                $images[$sheet][$rowNumber] = [
                    'contents' => $contents,
                    'extension' => strtolower(pathinfo($imageName, PATHINFO_EXTENSION)),
                ];
            }
        }

        return $images;
    }

    private function relationshipTarget(SimpleXMLElement $relationships, string $id): ?string
    {
        foreach ($relationships->children('http://schemas.openxmlformats.org/package/2006/relationships') as $relationship) {
            $attributes = $relationship->attributes();

            if ((string) $attributes['Id'] === $id) {
                return (string) $attributes['Target'];
            }
        }

        return null;
    }

    /** @param array{contents: string, extension: string}|null $image */
    private function storeCatalogImage(?array $image, string $sku): ?string
    {
        if ($image === null || ! in_array($image['extension'], ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $filename = strtolower($sku).'.'.$image['extension'];
        $path = "equipment/nox/catalog/{$filename}";

        Storage::disk('public')->put($path, $image['contents']);

        return $path;
    }

    /** @return array<string, int|string|bool|null> */
    private function sale(string $sku, string $name, int $salePrice, ?string $image, ?string $description = null): array
    {
        return [
            'sku' => $sku,
            'name' => $name,
            'image' => $image,
            'short_description' => $description ?? 'NOX oprema za padel.',
            'description' => $description ?? 'NOX oprema za padel.',
            'rental_price' => 0,
            'sale_price' => $salePrice,
            'is_rentable' => false,
            'is_sellable' => true,
        ];
    }

    /** @return array<string, int|string|bool|null> */
    private function rental(string $sku, string $name, ?string $image, ?string $description = null): array
    {
        return [
            'sku' => $sku,
            'name' => $name,
            'image' => $image,
            'short_description' => $description ?? 'NOX reket za iznajmljivanje uz padel termin.',
            'description' => $description ?? 'NOX reket za iznajmljivanje uz padel termin.',
            'rental_price' => 400,
            'sale_price' => 0,
            'is_rentable' => true,
            'is_sellable' => false,
        ];
    }
}
