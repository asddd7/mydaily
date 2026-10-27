<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CalendarHolidayFileController extends Controller
{
    private function holidayFilePath(): string
    {
        return base_path('calendar/holidays_2026.json');
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
            'holidays_2026.json',
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
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

        $path = $this->holidayFilePath();
        $directory = dirname($path);

        if (!File::isDirectory($directory) || !is_writable($directory)) {
            return back()->withErrors([
                'holiday_json' => 'Folder calendar tidak ditemukan atau tidak dapat ditulisi.',
            ]);
        }

        // Format ulang JSON agar rapi dan tetap UTF-8.
        $json = json_encode(
            $holidays,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        $temporaryPath = $directory
            . DIRECTORY_SEPARATOR
            . 'holidays_import_' . bin2hex(random_bytes(8)) . '.tmp';

        try {
            if (File::put($temporaryPath, $json) === false) {
                throw new \RuntimeException('Gagal menulis file sementara.');
            }

            if (!@rename($temporaryPath, $path)) {
                throw new \RuntimeException('Gagal mengganti file JSON hari libur.');
            }
        } catch (\Throwable $exception) {
            if (File::exists($temporaryPath)) {
                File::delete($temporaryPath);
            }

            report($exception);

            return back()->withErrors([
                'holiday_json' => 'Import gagal. File hari libur lama tidak berhasil diganti.',
            ]);
        }

        return back()->with(
            'status',
            'Import berhasil. ' . count($holidays) . ' data hari libur telah dimuat.'
        );
    }
}
