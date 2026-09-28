<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Sport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class NoxEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $padel = Sport::query()->where('slug', 'padel')->firstOrFail();

        foreach ($this->items() as $item) {
            $image = $item['image'];
            $imagePath = "equipment/nox/{$image}";
            $sourcePath = database_path("seeders/assets/nox-equipment/{$image}");

            if (is_file($sourcePath)) {
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

    /** @return array<int, array<string, int|string|bool>> */
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

            $this->sale('PRTNXNEBLBAG', 'BAG OF 6 NOX BLACK PROTECTORS', 1100, 'prtnxneblbag.png'),
            $this->sale('PRTNXROBLBAG', 'BAG OF 6 NOX RED PROTECTORS', 1100, 'prtnxroblbag.png'),
            $this->sale('PRTNXAZBLBAG', 'BAG OF 6 NOX BLUE PROTECTORS', 1100, 'prtnxazblbag.png'),
            $this->sale('MUBLAM2UDBOX', 'BLISTER WITH 2 WHITE/BLUE LOGO WRISTBANDS', 900, 'mublam2udbox.png', 'Pakovanje sadrži šest blistera.'),
            $this->sale('MULBLNEG2UDBOX', 'BAG WITH 2 WHITE/BLACK LOGO LONG WRISTBANDS', 900, 'mulblneg2udbox.png', 'Pakovanje sadrži šest blistera.'),
            $this->sale('OVPRO120BL', 'CAN WITH 120 WHITE PRO OVERGRIPS', 300, 'ovpro120bl.png'),
            $this->sale('CAHMCNLVBLBAG', 'MID LENGTH BLACK/WHITE TECHNICAL SOCKS', 799, 'cahmcnlvblbag.png', 'Muške čarape 39–45. Pakovanje od šest pari.'),
            $this->sale('CAHMCBLVAZBAG', 'MID LENGTH WHITE/BLUE TECHNICAL SOCKS', 799, 'cahmcblvazbag.png', 'Muške čarape 39–45. Pakovanje od šest pari.'),
            $this->sale('BOTNOXWH', 'WHITE NOX BOTTLE 550 ml', 1560, 'botnoxwh.jpg', 'Aluminijumska flašica od 550 ml.'),
            $this->sale('BOLS20LLAVGENIUS1226', 'BAG WITH 20 AT10 GENIUS 12K 26 RUBBER KEYCHAINS', 720, 'bols20llavgenius1226.png'),
            $this->sale('BOLS20LLAVGENIUS1826', 'BAG WITH 20 AT10 GENIUS 18K 26 RUBBER KEYCHAINS', 720, 'bols20llavgenius1826.png'),

            $this->sale('T24CASPATBL', 'CAMISETA SPONSORS AT10 BLACK', 6700, 't24caspatbl.png', 'Majica. Dostupne veličine XS–XXL.'),
            $this->sale('T26SSHCAWG', "PRO WHITE/IVY GREEN MEN'S T-SHIRT", 5940, 't26sshcawg.png', 'Muška majica. Dostupne veličine S–XXL.'),
            $this->sale('T26SSHSHIG', "MEN'S PRO IVY GREEN SHORTS", 6600, 't26sshshig.png', 'Muški šorts. Dostupne veličine S–XXL.'),
        ];
    }

    /** @return array<string, int|string|bool> */
    private function sale(string $sku, string $name, int $salePrice, string $image, ?string $description = null): array
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

    /** @return array<string, int|string|bool> */
    private function rental(string $sku, string $name, string $image, ?string $description = null): array
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
