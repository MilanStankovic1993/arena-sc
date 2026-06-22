<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table): void {
            $table->index(['is_active', 'sort_order'], 'sports_active_sort_index');
        });

        Schema::table('courts', function (Blueprint $table): void {
            $table->index(['sport_id', 'is_active', 'name'], 'courts_sport_active_name_index');
        });

        Schema::table('equipment', function (Blueprint $table): void {
            $table->index(['is_active', 'is_rentable', 'sport_id', 'name'], 'equipment_booking_index');
            $table->index(['is_active', 'is_sellable', 'name'], 'equipment_listing_index');
        });

        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->index(['sport_id', 'is_active', 'start_time'], 'pricing_rules_lookup_index');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->index(['court_id', 'status', 'starts_at', 'ends_at'], 'reservations_availability_index');
            $table->index(['starts_at', 'status'], 'reservations_period_status_index');
            $table->index(['sport_id', 'starts_at', 'status'], 'reservations_sport_period_index');
        });

        Schema::table('court_closures', function (Blueprint $table): void {
            $table->index(['court_id', 'is_active', 'starts_at', 'ends_at'], 'court_closures_availability_index');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->index(['status', 'is_featured', 'start_date'], 'events_public_listing_index');
        });

        Schema::table('event_matches', function (Blueprint $table): void {
            $table->index(['event_id', 'scheduled_at'], 'event_matches_schedule_index');
        });

        Schema::table('membership_plans', function (Blueprint $table): void {
            $table->index(['is_active', 'sort_order', 'price'], 'membership_plans_listing_index');
        });

        Schema::table('user_memberships', function (Blueprint $table): void {
            $table->index(
                ['is_active', 'last_expiry_reminder_sent_at', 'ends_at'],
                'user_memberships_expiry_reminder_index',
            );
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index(['role', 'name'], 'users_role_name_index');
            $table->index(['role', 'created_at'], 'users_role_created_index');
        });

        Schema::table('email_campaigns', function (Blueprint $table): void {
            $table->index(['is_active', 'created_at'], 'email_campaigns_active_created_index');
        });

        Schema::table('reservation_participants', function (Blueprint $table): void {
            $table->index(['user_id', 'reservation_id'], 'reservation_participants_user_reservation_index');
        });

        Schema::table('reservation_equipment', function (Blueprint $table): void {
            $table->index(['equipment_id', 'reservation_id'], 'reservation_equipment_item_reservation_index');
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->index(['queue', 'reserved_at', 'available_at'], 'jobs_queue_processing_index');
        });
    }

    public function down(): void
    {
        $this->ensureIndex('courts', ['sport_id'], 'courts_sport_id_foreign');
        $this->ensureIndex('pricing_rules', ['sport_id'], 'pricing_rules_sport_id_foreign');
        $this->ensureIndex('reservations', ['sport_id'], 'reservations_sport_id_foreign');
        $this->ensureIndex('event_matches', ['event_id'], 'event_matches_event_id_foreign');
        $this->ensureIndex('reservation_participants', ['user_id'], 'reservation_participants_user_id_foreign');
        $this->ensureIndex('reservation_equipment', ['equipment_id'], 'reservation_equipment_equipment_id_foreign');

        Schema::table('jobs', fn (Blueprint $table) => $table->dropIndex('jobs_queue_processing_index'));
        Schema::table('reservation_equipment', fn (Blueprint $table) => $table->dropIndex('reservation_equipment_item_reservation_index'));
        Schema::table('reservation_participants', fn (Blueprint $table) => $table->dropIndex('reservation_participants_user_reservation_index'));
        Schema::table('email_campaigns', fn (Blueprint $table) => $table->dropIndex('email_campaigns_active_created_index'));
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_name_index');
            $table->dropIndex('users_role_created_index');
        });
        Schema::table('user_memberships', fn (Blueprint $table) => $table->dropIndex('user_memberships_expiry_reminder_index'));
        Schema::table('membership_plans', fn (Blueprint $table) => $table->dropIndex('membership_plans_listing_index'));
        Schema::table('event_matches', fn (Blueprint $table) => $table->dropIndex('event_matches_schedule_index'));
        Schema::table('events', fn (Blueprint $table) => $table->dropIndex('events_public_listing_index'));
        Schema::table('court_closures', fn (Blueprint $table) => $table->dropIndex('court_closures_availability_index'));
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex('reservations_availability_index');
            $table->dropIndex('reservations_period_status_index');
            $table->dropIndex('reservations_sport_period_index');
        });
        Schema::table('pricing_rules', fn (Blueprint $table) => $table->dropIndex('pricing_rules_lookup_index'));
        Schema::table('equipment', function (Blueprint $table): void {
            $table->dropIndex('equipment_booking_index');
            $table->dropIndex('equipment_listing_index');
        });
        Schema::table('courts', fn (Blueprint $table) => $table->dropIndex('courts_sport_active_name_index'));
        Schema::table('sports', fn (Blueprint $table) => $table->dropIndex('sports_active_sort_index'));
    }

    private function ensureIndex(string $tableName, array $columns, string $indexName): void
    {
        if (Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, fn (Blueprint $table) => $table->index($columns, $indexName));
    }
};
