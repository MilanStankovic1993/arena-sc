<?php

use App\Models\PricingRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (PricingRule::query()->whereIn('end_time', ['23:00:00', '23:59:00', '23:59:59'])->get() as $rule) {
            $rule->end_time = '00:00:00';

            // Preserve any separately configured late-night tariff.
            if ($rule->is_active && PricingRule::findConflictingRule($rule)) {
                continue;
            }

            DB::table('pricing_rules')->where('id', $rule->id)->update(['end_time' => '00:00:00']);
        }
    }

    public function down(): void
    {
        // Custom tariff values cannot be inferred after an extension.
    }
};
