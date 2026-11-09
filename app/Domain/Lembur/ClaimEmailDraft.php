<?php

namespace App\Domain\Lembur;

use App\Enums\ClaimType;
use App\Support\Format;
use Carbon\CarbonInterface;

/**
 * Teks email pengajuan cuti pengganti. SOP §9.2 mewajibkan tiga hal: tanggal
 * overtime, total jam, dan evidence. Dipakai oleh klaim yang sudah tersimpan
 * (evidence dari SPL) dan halaman draft (evidence berupa link Kimai).
 */
class ClaimEmailDraft
{
    public static function subject(string $nama, CarbonInterface $claimDate): string
    {
        return sprintf('Pengajuan Cuti Pengganti – %s – %s', $nama, Format::tanggalPanjang($claimDate));
    }

    /**
     * @param  array<int, array{date: CarbonInterface, minutes: int, evidence: string}>  $rows
     */
    public static function body(
        string $nama,
        CarbonInterface $claimDate,
        ClaimType $type,
        int $totalMinutes,
        array $rows,
        ?string $kepada = null,
        ?string $jamMasuk = null,
    ): string {
        $bentuk = $type->getLabel();

        if ($type === ClaimType::LateArrival && filled($jamMasuk)) {
            $bentuk .= ' (masuk pukul '.Format::jam($jamMasuk).')';
        }

        $lines = [
            'Yth. '.(filled($kepada) ? $kepada : 'Bapak/Ibu').',',
            '',
            'Dengan hormat,',
            '',
            'Melalui email ini, saya mengajukan penggunaan hak cuti pengganti atas lembur yang telah saya kerjakan, dengan rincian sebagai berikut.',
            '',
            'RENCANA PENGGUNAAN CUTI',
            '• Tanggal cuti : '.self::hariTanggal($claimDate),
            '• Bentuk       : '.$bentuk,
            '• Total jam    : '.Format::durasi($totalMinutes),
            '',
            'DASAR PENGAJUAN (LEMBUR)',
        ];

        foreach (array_values($rows) as $i => $row) {
            $lines[] = sprintf('%d. %s · %s', $i + 1, self::hariTanggal($row['date']), Format::durasi($row['minutes']));
            $lines[] = '   Evidence: '.$row['evidence'];
        }

        return implode("\n", [
            ...$lines,
            '',
            'Mohon kesediaan Bapak/Ibu untuk meninjau dan menyetujui pengajuan ini. Apabila diperlukan informasi tambahan, saya siap melengkapinya.',
            '',
            'Atas perhatian dan persetujuannya, saya ucapkan terima kasih.',
            '',
            'Hormat saya,',
            $nama,
        ]);
    }

    /** Daftar timesheet Kimai yang difilter ke satu tanggal. */
    public static function kimaiUrl(CarbonInterface $date): string
    {
        $tanggal = $date->toDateString();

        return rtrim((string) config('kimai.base_url'), '/').'/en/timesheet/?'
            .http_build_query(['daterange' => "{$tanggal} - {$tanggal}"]);
    }

    /** "Rabu, 1 April 2026" */
    private static function hariTanggal(CarbonInterface $date): string
    {
        return $date->translatedFormat('l').', '.Format::tanggalPanjang($date);
    }
}
