<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CalendarHolidayFileController extends Controller
{
    private function holidayFilePath(): string
    {
        return base_path('calendar/holidays.json');
    }

    /**
     * Download file JSON hari libur yang sedang digunakan.
     */
    public function download(): BinaryFileResponse
    {
        $path = $this->holidayFilePath();

        abort_unless(File::exists($path) && File::isFile($path), 404, 'File JSON hari libur tidak ditemukan.');

        return response()->download(
            $path,
            'holidays.json',
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'keterangan' => ['required', 'string', 'max:255'],
            'original_tanggal' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
        ]);

        try {
            $holidays = $this->readHolidayEntries();
        } catch (\JsonException|\RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Data hari libur yang tersimpan tidak valid. Perbaiki datanya sebelum menyimpan.'], 503);
        }

        $byDate = [];
        foreach ($holidays as $holiday) {
            $byDate[$holiday['tanggal']] = $holiday;
        }
        if (!empty($data['original_tanggal']) && !isset($byDate[$data['original_tanggal']])) {
            return response()->json(['message' => 'Hari libur yang akan diubah tidak ditemukan.'], 404);
        }
        if (!empty($data['original_tanggal'])) {
            unset($byDate[$data['original_tanggal']]);
        }
        $byDate[$data['tanggal']] = [
            'tanggal' => $data['tanggal'],
            'keterangan' => trim($data['keterangan']),
        ];
        ksort($byDate);

        try {
            $this->writeHolidayEntries(array_values($byDate));
        } catch (\RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Hari libur tidak berhasil disimpan. Periksa izin tulis folder calendar.'], 503);
        }

        return response()->json([
            'message' => 'Hari libur berhasil disimpan.',
            'holiday' => $byDate[$data['tanggal']],
        ]);
    }

    public function destroyHoliday(string $date): JsonResponse
    {
        Validator::make(
            ['date' => $date],
            ['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31']],
        )->validate();

        try {
            $holidays = $this->readHolidayEntries();
        } catch (\JsonException|\RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Data hari libur yang tersimpan tidak valid. Perbaiki datanya sebelum menghapus.'], 503);
        }

        $remaining = array_values(array_filter(
            $holidays,
            static fn (array $holiday): bool => $holiday['tanggal'] !== $date,
        ));

        if (count($remaining) === count($holidays)) {
            return response()->json(['message' => 'Hari libur tidak ditemukan.'], 404);
        }

        try {
            $this->writeHolidayEntries($remaining);
        } catch (\RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Hari libur tidak berhasil dihapus. Periksa izin tulis folder calendar.'], 503);
        }

        return response()->json(['message' => 'Hari libur berhasil dihapus.']);
    }

    /**
     * @return array<int, array{tanggal: string, keterangan: string}>
     */
    private function readHolidayEntries(): array
    {
        $path = $this->holidayFilePath();
        if (!File::exists($path)) {
            return [];
        }

        $holidays = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($holidays) || !array_is_list($holidays)) {
            throw new \RuntimeException('The holiday file must contain a JSON list.');
        }

        foreach ($holidays as $holiday) {
            if (
                !is_array($holiday)
                || !is_string($holiday['tanggal'] ?? null)
                || !is_string($holiday['keterangan'] ?? null)
                || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $holiday['tanggal'])
                || !checkdate(
                    (int) substr($holiday['tanggal'], 5, 2),
                    (int) substr($holiday['tanggal'], 8, 2),
                    (int) substr($holiday['tanggal'], 0, 4),
                )
            ) {
                throw new \RuntimeException('The holiday file contains an invalid entry.');
            }
        }

        return array_map(static fn (array $holiday): array => [
            'tanggal' => $holiday['tanggal'],
            'keterangan' => $holiday['keterangan'],
        ], $holidays);
    }

    /**
     * @param  array<int, array{tanggal: string, keterangan: string}>  $holidays
     */
    private function writeHolidayEntries(array $holidays): void
    {
        $directory = dirname($this->holidayFilePath());
        if (!File::isDirectory($directory) || !is_writable($directory)) {
            throw new \RuntimeException('The calendar directory is missing or not writable.');
        }

        $path = $this->holidayFilePath();
        $temporaryPath = $directory.DIRECTORY_SEPARATOR.'holidays_'.bin2hex(random_bytes(8)).'.tmp';
        $json = json_encode(
            $holidays,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;

        try {
            if (File::put($temporaryPath, $json) === false || !rename($temporaryPath, $path)) {
                throw new \RuntimeException('Unable to replace the holiday data file.');
            }
        } finally {
            if (File::exists($temporaryPath)) {
                File::delete($temporaryPath);
            }
        }
    }

    /**
     * Import JSON dan mengganti file hari libur yang aktif.
     */
    public function import(Request $request)
    {
        $request->validate([
            'holiday_json' => [
                'required',
                'file',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (strtolower($value->getClientOriginalExtension()) !== 'json') {
                        $fail('File harus berformat .json.');
                    }
                },
            ],
        ]);

        $uploadedFile = $request->file('holiday_json');
        $contents = File::get($uploadedFile->getRealPath());

        try {
            $holidays = json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw ValidationException::withMessages([
                'holiday_json' => 'Isi file bukan JSON yang valid.',
            ]);
        }

        // Widget kalender mengharapkan daftar hari libur berbentuk array.
        if (!is_array($holidays) || !array_is_list($holidays)) {
            throw ValidationException::withMessages([
                'holiday_json' => 'Format JSON harus berupa array daftar hari libur.',
            ]);
        }

        foreach ($holidays as $index => $holiday) {
            $validator = Validator::make(
                ['holiday' => $holiday],
                [
                    'holiday' => ['required', 'array'],
                    'holiday.tanggal' => [
                        'required',
                        'date_format:Y-m-d',
                        'after_or_equal:2000-01-01',
                        'before_or_equal:2100-12-31',
                    ],
                    'holiday.keterangan' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                [
                    'holiday.tanggal.required' => 'Tanggal wajib diisi.',
                    'holiday.tanggal.date_format' => 'Tanggal harus berformat YYYY-MM-DD.',
                    'holiday.keterangan.required' => 'Keterangan hari libur wajib diisi.',
                ]
            );

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'holiday_json' => sprintf(
                        'Data hari libur nomor %d tidak valid: %s',
                        $index + 1,
                        implode(' ', $validator->errors()->all())
                    ),
                ]);
            }
        }

        $directory = base_path('calendar');

        if (!File::isDirectory($directory) || !is_writable($directory)) {
            return back()->withErrors([
                'holiday_json' => 'Folder calendar tidak ditemukan atau tidak dapat ditulisi.',
            ]);
        }

        $path = $this->holidayFilePath();
        $existingHolidays = [];

        if (File::exists($path)) {
            try {
                $existingHolidays = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                return back()->withErrors([
                    'holiday_json' => 'File holidays.json yang lama tidak valid. Import dibatalkan.',
                ]);
            }

            if (!is_array($existingHolidays) || !array_is_list($existingHolidays)) {
                return back()->withErrors([
                    'holiday_json' => 'Format holidays.json yang lama tidak valid. Import dibatalkan.',
                ]);
            }
        }

        $mergedByDate = [];
        foreach ($existingHolidays as $index => $item) {
            if (
                !is_array($item)
                || !isset($item['tanggal'], $item['keterangan'])
                || !is_string($item['tanggal'])
                || !is_string($item['keterangan'])
            ) {
                return back()->withErrors([
                    'holiday_json' => 'Data lama nomor ' . ($index + 1) . ' tidak valid. Import dibatalkan.',
                ]);
            }

            $mergedByDate[$item['tanggal']] = [
                'tanggal' => $item['tanggal'],
                'keterangan' => $item['keterangan'],
            ];
        }

        // Tanggal yang sama dari file baru akan memperbarui entri lama.
        foreach ($holidays as $item) {
            $mergedByDate[$item['tanggal']] = [
                'tanggal' => $item['tanggal'],
                'keterangan' => $item['keterangan'],
            ];
        }

        $json = json_encode(
            array_values($mergedByDate),
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        ) . PHP_EOL;
        $temporaryPath = $directory . DIRECTORY_SEPARATOR
            . 'holidays_import_' . bin2hex(random_bytes(8)) . '.tmp';

        try {
            if (File::put($temporaryPath, $json) === false || !@rename($temporaryPath, $path)) {
                throw new \RuntimeException('Gagal menyimpan holidays.json.');
            }
        } catch (\Throwable $exception) {
            if (File::exists($temporaryPath)) {
                File::delete($temporaryPath);
            }

            report($exception);
            return back()->withErrors([
                'holiday_json' => 'Import gagal. File holidays.json tidak berhasil diperbarui.',
            ]);
        }

        return back()->with(
            'status',
            count($holidays) . ' data dari file import diproses. Total '
                . count($mergedByDate) . ' hari libur tersimpan di holidays.json.'
        );
    }
}
