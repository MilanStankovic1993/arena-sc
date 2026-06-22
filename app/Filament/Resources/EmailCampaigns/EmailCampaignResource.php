<?php

namespace App\Filament\Resources\EmailCampaigns;

use App\Filament\Resources\EmailCampaigns\Pages\ManageEmailCampaigns;
use App\Models\EmailCampaign;
use App\Services\EmailCampaignService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Mail kampanje';

    protected static string|UnitEnum|null $navigationGroup = 'Korisnici i analitika';

    protected static ?string $modelLabel = 'Mail kampanja';

    protected static ?string $pluralModelLabel = 'Mail kampanje';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sadrzaj kampanje')->schema([
                TextInput::make('name')->label('Naziv kampanje')->required(),
                TextInput::make('subject')->label('Naslov mejla')->required(),
                TextInput::make('preheader')->label('Kratki uvod'),
                TextInput::make('heading')->label('Veliki naslov'),
                FileUpload::make('hero_image')
                    ->label('Naslovna slika')
                    ->image()
                    ->acceptedFileTypes(['image/avif', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('1600')
                    ->imageResizeTargetHeight('1200')
                    ->imageResizeUpscale(false)
                    ->disk('public')
                    ->directory('campaigns'),
                Textarea::make('intro')->label('Uvod')->rows(3)->columnSpanFull(),
                Textarea::make('body')->label('Glavni tekst')->rows(10)->required()->columnSpanFull(),
                TextInput::make('cta_label')->label('Tekst dugmeta'),
                TextInput::make('cta_url')->label('Link dugmeta')->url(),
                Textarea::make('footer_note')->label('Zavrsna napomena')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Aktivna kampanja')->default(true),
            ])->columns(2),
            Section::make('Pregled slanja')
                ->visible(fn (?EmailCampaign $record): bool => filled($record))
                ->schema([
                    Placeholder::make('sent_count')
                        ->label('Ukupno poslato')
                        ->content(fn (?EmailCampaign $record): string => number_format((int) ($record?->sent_count ?? 0), 0, ',', '.')),
                    Placeholder::make('last_sent_at')
                        ->label('Poslednje slanje')
                        ->content(fn (?EmailCampaign $record): string => $record?->last_sent_at?->format('d.m.Y H:i') ?? 'Nije slato'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Kampanja')->searchable()->sortable(),
                TextColumn::make('subject')->label('Naslov')->limit(50),
                IconColumn::make('is_active')->label('Aktivna')->boolean(),
                TextColumn::make('sent_count')->label('Poslato')->sortable(),
                TextColumn::make('last_sent_at')->label('Poslednje slanje')->dateTime(),
                TextColumn::make('updated_at')->label('Azurirano')->since(),
            ])
            ->recordActions([
                Action::make('posaljiSvima')
                    ->label('Posalji svim korisnicima')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (EmailCampaign $record): string => 'Posalji kampanju svim korisnicima')
                    ->modalDescription('Ova akcija salje kampanju svim registrovanim korisnicima koji imaju email adresu.')
                    ->action(function (EmailCampaign $record): void {
                        $sent = app(EmailCampaignService::class)->sendToUsers($record, app(EmailCampaignService::class)->allCustomers());

                        Notification::make()
                            ->title('Kampanja je poslata.')
                            ->body("Poslato korisnicima: {$sent}.")
                            ->success()
                            ->send();
                    }),
                Action::make('pregled')
                    ->label('Pregled')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Zatvori')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalContent(fn (EmailCampaign $record) => new HtmlString(view('filament.resources.email-campaigns.preview', [
                        'campaign' => $record,
                    ])->render())),
                EditAction::make()->modalWidth(Width::Screen),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmailCampaigns::route('/'),
        ];
    }
}
