@php
    use App\Support\Format;
    $draft = $this->draft();
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Isi dulu</x-slot>
        <x-slot name="description">
            Bukti lembur diambil otomatis dari saldo yang akan terpakai (urutan hangus terdekat), dan tiap bukti menaut ke daftar timesheet Kimai pada tanggal lemburnya.
        </x-slot>

        <form class="space-y-6">{{ $this->form }}</form>
    </x-filament::section>

    @if ($draft)
        @if ($draft['shortfall'] > 0)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-200">
                <strong>Saldo kurang {{ Format::durasi($draft['shortfall']) }}</strong> pada tanggal itu. Draft hanya memuat lembur yang tersedia.
            </div>
        @endif

        <x-filament::section wire:key="draft-{{ md5($draft['body']) }}">
            <x-slot name="heading">Draft email</x-slot>

            <div x-data="{ subject: @js($draft['subject']), body: @js($draft['body']), done: null,
                salin(teks, nama) { navigator.clipboard.writeText(teks); this.done = nama; setTimeout(() => this.done = null, 1500) } }"
                 class="space-y-4">
                <div>
                    <p class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Perihal</p>
                    <input type="text" readonly x-model="subject" onclick="this.select()"
                           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                </div>

                <div>
                    <p class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Isi email</p>
                    <textarea readonly rows="20" x-model="body" onclick="this.select()"
                              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm dark:border-white/10 dark:bg-white/5"></textarea>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-filament::button type="button" icon="heroicon-m-clipboard" x-on:click="salin(body, 'body')">
                        <span x-text="done === 'body' ? 'Tersalin' : 'Salin isi email'"></span>
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" x-on:click="salin(subject, 'subject')">
                        <span x-text="done === 'subject' ? 'Tersalin' : 'Salin perihal'"></span>
                    </x-filament::button>
                    <x-filament::button tag="a" color="gray" icon="heroicon-m-envelope"
                        x-bind:href="'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body)">
                        Buka di aplikasi email
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
