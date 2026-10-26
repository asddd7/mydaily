<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileManagerController extends Controller
{
    private const MAX_FILE_SIZE_KB = 51200;

    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif',
        'mp4', 'mov', 'avi', 'mkv', 'webm',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'zip', 'rar', 'txt',
    ];

    private function currentUserId(Request $request): int
    {
        $user = $request->attributes->get('currentUser');

        abort_unless($user && isset($user->id), 401);

        return (int) $user->id;
    }

    private function storageDirectory(): string
    {
        return base_path('uploads/files');
    }

    /**
     * Bersihkan nama tampilan tanpa mengubah nama file fisik.
     */
    private function sanitizeDisplayName(string $name): string
    {
        $name = trim($name);

        // Hilangkan path, karakter kontrol, dan karakter berbahaya.
        $name = str_replace(['/', '\\'], '_', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name, " .\t\n\r\0\x0B");

        return $name;
    }

    public function index(Request $request): View
    {
        $files = DB::table('file_manager')
            ->where('user_id', $this->currentUserId($request))
            ->orderByDesc('id')
            ->get();

        return view('files.index', compact('files'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            '_form' => ['nullable', 'in:files_upload'],
            'file' => [
                'required',
                'file',
                'max:' . self::MAX_FILE_SIZE_KB,
            ],
            'file_display_name' => ['nullable', 'string', 'max:255'],
        ]);

        $upload = $request->file('file');

        abort_unless($upload && $upload->isValid(), 422, 'Upload file gagal.');

        $extension = strtolower($upload->getClientOriginalExtension());

        abort_unless(
            in_array($extension, self::ALLOWED_EXTENSIONS, true),
            422,
            'Format file tidak diizinkan.'
        );

        $allowedMime = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'video/mp4',
            'video/webm',
            'video/x-msvideo',
            'video/quicktime',
            'video/x-matroska',
            'application/octet-stream',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'text/plain',
        ];

        $mime = $upload->getMimeType();

        abort_unless(
            $mime && in_array($mime, $allowedMime, true),
            422,
            'Tipe file tidak valid atau belum didukung.'
        );

        $userId = $this->currentUserId($request);

        $displayName = $this->sanitizeDisplayName(
            (string) $request->input('file_display_name', '')
        );

        if ($displayName !== '') {
            if (strtolower(pathinfo($displayName, PATHINFO_EXTENSION)) === $extension) {
                $displayName = pathinfo($displayName, PATHINFO_FILENAME);
            }

            $displayName = Str::limit(
                $displayName,
                max(1, 254 - strlen($extension)),
                ''
            );
            $originalName = $displayName . '.' . $extension;
        } else {
            $originalName = $this->sanitizeDisplayName(
                $upload->getClientOriginalName()
            );

            if ($originalName === '') {
                $originalName = 'file.' . $extension;
            }

            $originalName = Str::limit($originalName, 255, '');
        }

        // Nama fisik acak, tidak memakai nama yang dikirim pengguna.
        $newName = Str::random(64) . '.' . $extension;
        $fileSize = (int) $upload->getSize();
        $directory = $this->storageDirectory();

        File::ensureDirectoryExists($directory);

        $upload->move($directory, $newName);

        $path = $directory . DIRECTORY_SEPARATOR . $newName;

        try {
            DB::table('file_manager')->insert([
                'user_id' => $userId,
                'file_name' => $newName,
                'file_original' => $originalName,
                'file_size' => $fileSize,
                'file_type' => $extension,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Jangan meninggalkan file tanpa metadata di database.
            if (File::exists($path)) {
                File::delete($path);
            }

            throw $exception;
        }

        return redirect()
            ->route('files.index')
            ->with('status', 'File berhasil diunggah.');
    }

    public function update(Request $request, int $file): RedirectResponse
    {
        $data = $request->validate([
            'file_original' => ['required', 'string', 'max:255'],
        ]);

        $userId = $this->currentUserId($request);

        // Pastikan file memang milik pengguna sebelum mengubah nama.
        $record = DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $userId)
            ->first();

        abort_unless($record, 404);

        $newName = $this->sanitizeDisplayName($data['file_original']);

        abort_if($newName === '', 422, 'Nama file tidak boleh kosong.');

        $newName = Str::limit($newName, 255, '');

        DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $userId)
            ->update([
                'file_original' => $newName,
            ]);

        return redirect()
            ->route('files.index')
            ->with('status', 'Nama file berhasil diubah.');
    }

    public function destroy(Request $request, int $file): RedirectResponse
    {
        $userId = $this->currentUserId($request);

        $record = DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $userId)
            ->first();

        abort_unless($record, 404);

        // basename memastikan nama file tidak bisa menunjuk ke luar folder.
        $physicalName = basename($record->file_name);
        $path = $this->storageDirectory()
            . DIRECTORY_SEPARATOR
            . $physicalName;

        if (File::exists($path) && !File::delete($path)) {
            throw new RuntimeException(
                'File fisik gagal dihapus. Data database tetap dipertahankan.'
            );
        }

        DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $userId)
            ->delete();

        return redirect()
            ->route('files.index')
            ->with('status', 'File berhasil dihapus.');
    }

    public function download(Request $request, int $file): BinaryFileResponse
    {
        $record = DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $this->currentUserId($request))
            ->first();

        abort_unless($record, 404);

        $physicalName = basename($record->file_name);
        $path = $this->storageDirectory()
            . DIRECTORY_SEPARATOR
            . $physicalName;

        abort_unless(File::isFile($path), 404, 'File tidak ditemukan.');

        $downloadName = $this->sanitizeDisplayName(
            (string) $record->file_original
        );

        if ($downloadName === '') {
            $downloadName = 'download.' . $record->file_type;
        }

        return response()->download($path, $downloadName);
    }
}
