<?php

namespace App\Filament\Pages;

use App\Domain\Lembur\ClaimEmailDraft;
use App\Domain\Lembur\ClaimValidator;
use App\Domain\Lembur\LeaveAllocator;
use App\Enums\ClaimType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

/**
 * Draft email pengajuan cuti pengganti sebelum klaimnya dibuat: pilih tanggal,
 * bukti lembur diambil dari rencana alokasi FIFO, dan tiap bukti menaut ke daftar
 * timesheet Kimai pada tanggal lemburnya.
 */
class DraftEmailCuti extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Draft Email Cuti Pengganti';

    protected string $view = 'filament.pages.draft-email-cuti';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'claim_type' => ClaimType::FullDay->value,
            'arrival_time' => Auth::user()?->default_late_arrival_time ?? '13:00',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('kepada')
                    ->label('Kepada')
                    ->placeholder('mis. Bapak Budi / Tim HRD')
                    ->required()
                    ->live(onBlur: true),

                Radio::make('claim_type')
                    ->label('Bentuk klaim')
                    ->options(fn () => collect(ClaimType::cases())
                        ->mapWithKeys(fn (ClaimType $t) => [$t->value => $t->getDescription()])
                        ->all())
                    ->required()
                    ->live(),

                DatePicker::make('claim_date')
                    ->label('Tanggal cuti')
                    ->native(false)
                    ->displayFormat('j F Y')
                    ->required()
                    ->live(),

                TimePicker::make('arrival_time')
                    ->label('Jam masuk')
                    ->seconds(false)
                    ->native(true)
                    ->visible(fn (Get $get) => $get('claim_type') === ClaimType::LateArrival->value)
                    ->live(onBlur: true),
            ]);
    }

    /** @return array{subject: string, body: string, shortfall: int}|null null selama tanggal cuti belum dipilih */
    public function draft(): ?array
    {
        $data = $this->data;
        $user = Auth::user();

        if ($user === null || blank($data['claim_date'] ?? null)) {
            return null;
        }

        $type = ClaimType::tryFrom((string) ($data['claim_type'] ?? '')) ?? ClaimType::FullDay;
        $date = Date::parse($data['claim_date']);
        $required = app(ClaimValidator::class)->minutesRequired($date, $type);
        $plan = app(LeaveAllocator::class)->plan($user, $date, $required);

        $rows = array_map(fn ($slice) => [
            'date' => $slice->balance->earned_date,
            'minutes' => $slice->minutes,
            'evidence' => ClaimEmailDraft::kimaiUrl($slice->balance->earned_date),
        ], $plan->slices);

        return [
            'subject' => ClaimEmailDraft::subject($user->name, $date),
            'body' => ClaimEmailDraft::body(
                nama: $user->name,
                claimDate: $date,
                type: $type,
                totalMinutes: $required,
                rows: $rows,
                kepada: $data['kepada'] ?? null,
                jamMasuk: $data['arrival_time'] ?? null,
            ),
            'shortfall' => $plan->shortfallMinutes(),
        ];
    }
}
