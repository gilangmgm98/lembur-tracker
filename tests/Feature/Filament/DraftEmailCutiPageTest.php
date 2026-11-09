<?php

namespace Tests\Feature\Filament;

use App\Domain\Lembur\ClaimEmailDraft;
use App\Enums\ClaimType;
use App\Filament\Pages\DraftEmailCuti;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DraftEmailCutiPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeDate('2026-03-20');
        $this->baselineRule();
    }

    #[Test]
    public function draft_memuat_penerima_tanggal_cuti_dan_link_kimai_per_tanggal_lembur(): void
    {
        $user = $this->employee();
        $this->actingAs($user);
        $this->logOvertime($user, '2026-03-12', '09:00', '19:00');   // 8 jam

        $draft = Livewire::test(DraftEmailCuti::class)
            ->fillForm(['kepada' => 'Bapak Budi', 'claim_type' => ClaimType::FullDay->value, 'claim_date' => '2026-04-01'])
            ->instance()->draft();

        $this->assertStringContainsString('Yth. Bapak Budi,', $draft['body']);
        $this->assertStringContainsString('Rabu, 1 April 2026', $draft['body']);
        $this->assertStringContainsString('Kamis, 12 Maret 2026 · 8 jam', $draft['body']);
        $this->assertStringContainsString(ClaimEmailDraft::kimaiUrl(CarbonImmutable::parse('2026-03-12')), $draft['body']);
        $this->assertStringContainsString('1 April 2026', $draft['subject']);
        $this->assertSame(0, $draft['shortfall']);
    }

    #[Test]
    public function saldo_kurang_dilaporkan_dan_draft_tanpa_tanggal_kosong(): void
    {
        $this->actingAs($this->employee());

        $page = Livewire::test(DraftEmailCuti::class);
        $this->assertNull($page->instance()->draft());

        $draft = $page->fillForm(['kepada' => 'HRD', 'claim_date' => '2026-04-01'])->instance()->draft();

        $this->assertSame(480, $draft['shortfall']);
    }

    #[Test]
    public function tombol_draft_email_ada_di_halaman_cuti_pengganti(): void
    {
        $this->actingAs($this->employee());

        Livewire::test(\App\Filament\Pages\CutiPengganti::class)->assertSee('Draft email');
    }
}
