<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\CourtClosures\Pages\ManageCourtClosures;
use App\Filament\Resources\Courts\Pages\ManageCourts;
use App\Filament\Resources\EmailCampaigns\Pages\ManageEmailCampaigns;
use App\Filament\Resources\Equipment\Pages\ManageEquipment;
use App\Filament\Resources\EventEntries\Pages\ManageEventEntries;
use App\Filament\Resources\EventMatches\Pages\ManageEventMatches;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Filament\Resources\MembershipPlans\Pages\ManageMembershipPlans;
use App\Filament\Resources\PricingRules\Pages\ManagePricingRules;
use App\Filament\Resources\Sports\Pages\ManageSports;
use App\Filament\Resources\UserMemberships\Pages\ManageUserMemberships;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Court;
use App\Models\EmailCampaign;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\EventEntry;
use App\Models\MembershipPlan;
use App\Models\Sport;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminFormPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]));
    }

    public static function priceModes(): array
    {
        return [
            'sale only' => [false, true, null, 990],
            'rental only' => [true, false, 400, null],
            'both' => [true, true, 400, 990],
            'neither' => [false, false, null, null],
        ];
    }

    #[DataProvider('priceModes')]
    public function test_equipment_create_and_edit_only_require_enabled_prices(bool $rentable, bool $sellable, ?int $rentalPrice, ?int $salePrice): void
    {
        $data = ['name' => 'Loptice', 'is_rentable' => $rentable, 'is_sellable' => $sellable,
            'rental_price' => $rentalPrice, 'sale_price' => $salePrice, 'stock_quantity' => null];
        Livewire::test(ManageEquipment::class)->callAction('create', data: $data)->assertHasNoActionErrors();
        $record = Equipment::sole();
        $this->assertSame((float) ($rentalPrice ?? 0), (float) $record->rental_price);
        $this->assertSame((float) ($salePrice ?? 0), (float) $record->sale_price);
        $this->assertSame(0, (int) $record->stock_quantity);

        Livewire::test(ManageEquipment::class)
            ->callAction(TestAction::make('edit')->table($record), data: [...$data, 'name' => 'Izmenjene loptice'])
            ->assertHasNoActionErrors();
        $this->assertSame('Izmenjene loptice', $record->fresh()->name);
    }

    public function test_active_prices_validate_in_the_form_and_switching_off_clears_the_price(): void
    {
        Livewire::test(ManageEquipment::class)->callAction('create', data: [
            'name' => 'Bez cene', 'is_rentable' => true, 'is_sellable' => true,
            'rental_price' => null, 'sale_price' => null,
        ])->assertHasActionErrors(['rental_price' => 'required', 'sale_price' => 'required']);
        $this->assertDatabaseCount('equipment', 0);

        $record = Equipment::create(['name' => 'Reket', 'rental_price' => 400, 'sale_price' => 900, 'is_rentable' => true, 'is_sellable' => true]);
        Livewire::test(ManageEquipment::class)->callAction(TestAction::make('edit')->table($record), data: [
            'is_rentable' => false, 'is_sellable' => false,
        ])->assertHasNoActionErrors();
        $this->assertSame(0.0, (float) $record->fresh()->rental_price);
        $this->assertSame(0.0, (float) $record->fresh()->sale_price);
    }

    public function test_model_normalizes_explicit_null_numbers_outside_the_admin(): void
    {
        $record = Equipment::create(['name' => 'Import', 'rental_price' => null, 'sale_price' => null, 'stock_quantity' => null]);
        $this->assertSame(0.0, (float) $record->fresh()->rental_price);
        $this->assertSame(0.0, (float) $record->fresh()->sale_price);
        $this->assertSame(0, (int) $record->fresh()->stock_quantity);
    }

    public static function uploadScreens(): array
    {
        return [
            'equipment' => [ManageEquipment::class, Equipment::class, 'image', ['name' => 'Upload artikal', 'is_rentable' => false, 'is_sellable' => false]],
            'sports' => [ManageSports::class, Sport::class, 'cover_image', ['name' => 'Upload sport', 'sort_order' => null]],
            'courts' => [ManageCourts::class, Court::class, 'image', ['name' => 'Upload teren']],
            'events' => [ManageEvents::class, Event::class, 'cover_image', ['title' => 'Upload dogadjaj', 'type' => 'tournament', 'status' => 'draft']],
            'campaigns' => [ManageEmailCampaigns::class, EmailCampaign::class, 'hero_image', ['name' => 'Upload kampanja', 'subject' => 'Naslov', 'body' => 'Tekst']],
        ];
    }

    #[DataProvider('uploadScreens')]
    public function test_all_image_forms_create_and_edit_with_an_upload(string $page, string $model, string $field, array $data): void
    {
        if ($model === Court::class) {
            $data['sport_id'] = Sport::create(['name' => 'Padel'])->id;
        }
        Livewire::test($page)->callAction('create', data: [...$data, $field => UploadedFile::fake()->image('slika.jpg')])->assertHasNoActionErrors();
        $record = $model::query()->where($model === Event::class ? 'title' : 'name', $data['title'] ?? $data['name'])->sole();
        Storage::disk('public')->assertExists($record->{$field});
        Livewire::test($page)->callAction(TestAction::make('edit')->table($record), data: [$field => UploadedFile::fake()->image('nova.jpg')])->assertHasNoActionErrors();
        Storage::disk('public')->assertExists($record->fresh()->{$field});
    }

    #[DataProvider('uploadScreens')]
    public function test_missing_temporary_images_return_validation_errors_on_every_image_form(string $page, string $model, string $field, array $data): void
    {
        if ($model === Court::class) {
            $data['sport_id'] = Sport::create(['name' => 'Padel'])->id;
        }
        $component = Livewire::test($page)->mountAction('create')
            ->fillForm([...$data, $field => UploadedFile::fake()->image('istekla.jpg')]);
        Storage::disk(FileUploadConfiguration::disk())->deleteDirectory(FileUploadConfiguration::directory());
        $component->callMountedAction()->assertHasActionErrors([$field]);
        $this->assertSame(0, $model::query()->where($model === Event::class ? 'title' : 'name', $data['title'] ?? $data['name'])->count());
    }

    public function test_other_admin_forms_persist_optional_blank_values(): void
    {
        Livewire::test(ManageUsers::class)->callAction('create', data: [
            'name' => 'Test korisnik', 'email' => 'admin-form@example.test', 'role' => 'customer',
            'password' => 'Testing-only-123', 'passwordConfirmation' => 'Testing-only-123',
            'registered_at' => null, 'phone' => null,
        ])->assertHasNoActionErrors();
        Livewire::test(ManageSports::class)->callAction('create', data: ['name' => 'Padel', 'sort_order' => null])->assertHasNoActionErrors();
        $sport = Sport::sole();
        $this->assertSame(0, (int) $sport->sort_order);
        Livewire::test(ManageCourts::class)->callAction('create', data: ['name' => 'Teren', 'sport_id' => $sport->id, 'capacity' => null])->assertHasNoActionErrors();
        $court = Court::sole();
        Livewire::test(ManageMembershipPlans::class)->callAction('create', data: [
            'name' => 'Plan', 'duration_days' => null, 'reservation_limit' => 1, 'price' => 1000, 'sort_order' => null,
        ])->assertHasNoActionErrors();
        $plan = MembershipPlan::sole();
        $this->assertSame(0, $plan->sort_order);
        Livewire::test(ManagePricingRules::class)->callAction('create', data: [
            'name' => 'Cena', 'sport_id' => $sport->id, 'days_of_week' => [1], 'start_time' => '10:00', 'end_time' => '20:00',
            'price_60' => 1000, 'price_90' => 1500, 'price_120' => 2000,
        ])->assertHasNoActionErrors();
        Livewire::test(ManageCourtClosures::class)->callAction('create', data: [
            'court_id' => $court->id, 'title' => 'Blokada', 'starts_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
            'ends_at' => now()->addDay()->setTime(11, 0)->toDateTimeString(),
        ])->assertHasNoActionErrors();
        Livewire::test(ManageUserMemberships::class)->callAction('create', data: [
            'user_id' => auth()->id(), 'membership_plan_id' => $plan->id, 'starts_at' => now()->toDateString(), 'ends_at' => now()->addMonth()->toDateString(),
        ])->assertHasNoActionErrors();
        Livewire::test(ManageEvents::class)->callAction('create', data: ['title' => 'Turnir', 'type' => 'tournament', 'status' => 'draft'])->assertHasNoActionErrors();
        $event = Event::sole();
        Livewire::test(ManageEventEntries::class)->callAction('create', data: ['event_id' => $event->id, 'user_id' => auth()->id(), 'team_name' => 'Tim'])->assertHasNoActionErrors();
        Livewire::test(ManageEventMatches::class)->callAction('create', data: ['event_id' => $event->id, 'status' => 'scheduled', 'home_entry_id' => EventEntry::sole()->id])->assertHasNoActionErrors();
        Livewire::test(ManageEmailCampaigns::class)->callAction('create', data: ['name' => 'Nacrt', 'subject' => 'Naslov', 'body' => 'Tekst'])->assertHasNoActionErrors();
    }
}
