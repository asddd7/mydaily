<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

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

    private function findUserFile(int $userId, int $fileId): object
    {
        $file = DB::table('file_manager')
            ->where('id', $fileId)
            ->where('user_id', $userId)
            ->first();

        abort_unless($file, 404);

        return $file;
    }

    private function folderNameExists(
        int $userId,
        string $name,
        ?int $exceptFolderId = null
    ): bool {
        $query = DB::table('file_folders')
            ->where('user_id', $userId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

        if ($exceptFolderId !== null) {
            $query->where('id', '!=', $exceptFolderId);
        }

        return $query->exists();
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
        $userId = $this->currentUserId($request);

        $folderFileCounts = DB::table('file_manager')
            ->select('folder_id')
            ->selectRaw('COUNT(*) as files_count')
            ->where('user_id', $userId)
            ->whereNotNull('folder_id')
            ->groupBy('folder_id');

        $folders = DB::table('file_folders')
            ->leftJoinSub($folderFileCounts, 'folder_counts', function ($join) {
                $join->on('file_folders.id', '=', 'folder_counts.folder_id');
            })
            ->where('file_folders.user_id', $userId)
            ->select('file_folders.*')
            ->selectRaw('COALESCE(folder_counts.files_count, 0) as files_count')
            ->orderBy('file_folders.name')
            ->get();

        $activeFolder = null;
        $folderId = $request->query('folder');

        if ($folderId !== null && $folderId !== '') {
            abort_unless(filter_var($folderId, FILTER_VALIDATE_INT) !== false, 404);

            $activeFolder = DB::table('file_folders')
                ->where('id', $folderId)
                ->where('user_id', $userId)
                ->first();

            abort_unless($activeFolder, 404);
        }

        $filesQuery = DB::table('file_manager')
            ->where('user_id', $userId);

        if ($activeFolder) {
            $filesQuery->where('folder_id', $activeFolder->id);
        }

        $files = $filesQuery
            ->orderByDesc('id')
            ->get();

        $totalFiles = DB::table('file_manager')
            ->where('user_id', $userId)
            ->count();

        $totalSize = DB::table('file_manager')
            ->where('user_id', $userId)
            ->sum('file_size');

        return view('files.index', compact(
            'files',
            'folders',
            'activeFolder',
            'totalFiles',
            'totalSize'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $this->currentUserId($request);
        $allowedTypes = 'mimes:' . implode(',', self::ALLOWED_EXTENSIONS);

        $request->validate([
            'files' => ['nullable', 'array'],
            'files.*' => [
                'required',
                'file',
                'max:' . self::MAX_FILE_SIZE_KB,
                $allowedTypes,
            ],
            'file' => [
                'nullable',
                'file',
                'max:' . self::MAX_FILE_SIZE_KB,
                $allowedTypes,
            ],
            'folder_id' => ['nullable', 'integer'],
            'new_folder_name' => ['nullable', 'string', 'max:100'],
            'file_display_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Mendukung input baru files[] dan input lama file.
        $uploads = array_values(array_filter(
            $request->file('files', [])
        ));

        if ($request->hasFile('file')) {
            $uploads[] = $request->file('file');
        }

        if ($uploads === []) {
            return back()->withErrors([
                'file' => 'Pilih minimal satu file untuk diunggah.',
            ])->withInput();
        }

        $folderId = $request->input('folder_id');
        $newFolderName = trim((string) $request->input('new_folder_name', ''));

        if ($newFolderName !== '' && $folderId !== null && $folderId !== '') {
            return back()
                ->withErrors([
                    'folder_id' => 'Pilih grup yang sudah ada atau buat grup baru, bukan keduanya.',
                ])
                ->withInput();
        }

        if ($newFolderName === '' && $folderId !== null && $folderId !== '') {
            $folderExists = DB::table('file_folders')
                ->where('id', $folderId)
                ->where('user_id', $userId)
                ->exists();

            if (! $folderExists) {
                return back()
                    ->withErrors([
                        'folder_id' => 'Grup tujuan tidak valid.',
                    ])
                    ->withInput();
            }
        } else {
            $folderId = null;
        }

        $directory = $this->storageDirectory();

        File::ensureDirectoryExists($directory);

        $storedPaths = [];

        try {
            $targetFolderId = DB::transaction(function () use (
                $uploads,
                $userId,
                $folderId,
                $request,
                $directory,
                $newFolderName,
                &$storedPaths
            ): ?int {
                $targetFolderId = $folderId;

                if ($newFolderName !== '') {
                    if ($this->folderNameExists($userId, $newFolderName)) {
                        throw ValidationException::withMessages([
                            'new_folder_name' => 'Nama grup tersebut sudah digunakan.',
                        ]);
                    }

                    $targetFolderId = DB::table('file_folders')->insertGetId([
                        'user_id' => $userId,
                        'name' => $newFolderName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($uploads as $upload) {
                    $fileSize = $upload->getSize();
                    $extension = strtolower($upload->getClientOriginalExtension());
                    $physicalName = Str::random(64) . '.' . $extension;
                    $upload->move($directory, $physicalName);
                    $storedPaths[] = $directory . DIRECTORY_SEPARATOR . $physicalName;

                    // Nama khusus hanya berlaku untuk upload satu file.
                    if (count($uploads) === 1 && $request->filled('file_display_name')) {
                        $displayName = $this->sanitizeDisplayName(
                            $request->input('file_display_name')
                        );

                        if ($displayName === '') {
                            $displayName = pathinfo(
                                $upload->getClientOriginalName(),
                                PATHINFO_FILENAME
                            );
                        }

                        $originalName = $displayName . '.' . $extension;
                    } else {
                        $originalName = $this->sanitizeDisplayName(
                            $upload->getClientOriginalName()
                        );
                    }

                    DB::table('file_manager')->insert([
                        'user_id' => $userId,
                        'folder_id' => $targetFolderId,
                        'file_name' => $physicalName,
                        'file_original' => $originalName,
                        'file_size' => $fileSize,
                        'file_type' => $extension,
                        'created_at' => now(),
                    ]);
                }

                return $targetFolderId === null ? null : (int) $targetFolderId;
            });
        } catch (Throwable $e) {
            // Bersihkan file fisik jika penyimpanan database gagal.
            $failedPaths = [];

            foreach ($storedPaths as $path) {
                if (File::exists($path) && ! File::delete($path)) {
                    $failedPaths[] = $path;
                }
            }

            if ($failedPaths !== []) {
                throw new RuntimeException(
                    count($failedPaths) . ' file sementara gagal dibersihkan setelah upload dibatalkan.',
                    previous: $e
                );
            }

            throw $e;
        }

        $message = count($uploads) === 1
            ? 'File berhasil diunggah.'
            : count($uploads) . ' file berhasil diunggah.';

        if ($targetFolderId !== null) {
            return redirect()
                ->route('files.index', ['folder' => $targetFolderId])
                ->with('status', $message);
        }

        return redirect()
            ->route('files.index')
            ->with('status', $message);
    }

    public function preview(Request $request, int $file): BinaryFileResponse
    {
        $userId = $this->currentUserId($request);
        $record = $this->findUserFile($userId, $file);

        $filename = basename((string) ($record->file_name ?? ''));

        abort_if(
            $filename === '' || $filename === '.' || $filename === '..',
            404
        );

        $path = $this->storageDirectory()
            . DIRECTORY_SEPARATOR
            . $filename;

        abort_unless(is_file($path) && is_readable($path), 404);

        // Kolom file_type pada aplikasi ini menyimpan ekstensi file.
        $extension = strtolower(trim((string) ($record->file_type ?? '')));

        if ($extension === '' || str_contains($extension, '/')) {
            $extension = strtolower(
                pathinfo(
                    (string) ($record->file_original ?? ''),
                    PATHINFO_EXTENSION
                )
            );
        }

        // Hanya izinkan format gambar yang didukung untuk preview.
        $contentTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
        ];

        abort_unless(isset($contentTypes[$extension]), 404);

        return response()->file($path, [
            'Content-Type' => $contentTypes[$extension],
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function update(Request $request, int $file): RedirectResponse
    {
        $data = $request->validate([
            'file_original' => ['required', 'string', 'max:255'],
        ]);

        $userId = $this->currentUserId($request);
        $record = $this->findUserFile($userId, $file);

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
            ->route(
                'files.index',
                $record->folder_id ? ['folder' => $record->folder_id] : []
            )
            ->with('status', 'Nama file berhasil diubah.');
    }

    public function destroy(Request $request, int $file): RedirectResponse
    {
        $userId = $this->currentUserId($request);
        $record = $this->findUserFile($userId, $file);

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
            ->route(
                'files.index',
                $record->folder_id ? ['folder' => $record->folder_id] : []
            )
            ->with('status', 'File berhasil dihapus.');
    }

    public function download(Request $request, int $file): BinaryFileResponse
    {
        $record = $this->findUserFile(
            $this->currentUserId($request),
            $file
        );

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

    public function storeFolder(Request $request): RedirectResponse
    {
        $userId = $this->currentUserId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $name = trim($validated['name']);

        if ($this->folderNameExists($userId, $name)) {
            return back()->withErrors([
                'folder' => 'Nama folder tersebut sudah digunakan.',
            ]);
        }

        DB::table('file_folders')->insert([
            'user_id' => $userId,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Folder berhasil dibuat.');
    }

    public function updateFolder(Request $request, int $folder): RedirectResponse
    {
        $userId = $this->currentUserId($request);

        $folderQuery = DB::table('file_folders')
            ->where('id', $folder)
            ->where('user_id', $userId);

        abort_unless($folderQuery->exists(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $name = trim($validated['name']);

        if ($this->folderNameExists($userId, $name, $folder)) {
            return back()->withErrors([
                'folder' => 'Nama folder tersebut sudah digunakan.',
            ]);
        }

        $folderQuery->update([
            'name' => $name,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Nama folder berhasil diperbarui.');
    }

    public function destroyFolder(Request $request, int $folder): RedirectResponse
    {
        $userId = $this->currentUserId($request);

        DB::transaction(function () use ($folder, $userId) {
            $folderExists = DB::table('file_folders')
                ->where('id', $folder)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->exists();

            abort_unless($folderExists, 404);

            // File tetap disimpan; hanya dilepas dari folder.
            DB::table('file_manager')
                ->where('user_id', $userId)
                ->where('folder_id', $folder)
                ->update(['folder_id' => null]);

            DB::table('file_folders')
                ->where('id', $folder)
                ->where('user_id', $userId)
                ->delete();
        });

        return redirect()
            ->route('files.index')
            ->with('status', 'Folder dihapus. File di dalamnya tetap ada di Semua File.');
    }

    public function move(Request $request, int $file): RedirectResponse
    {
        $userId = $this->currentUserId($request);

        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer'],
        ]);

        $this->findUserFile($userId, $file);

        $folderId = $validated['folder_id'] ?? null;

        if ($folderId !== null) {
            $folderExists = DB::table('file_folders')
                ->where('id', $folderId)
                ->where('user_id', $userId)
                ->exists();

            if (! $folderExists) {
                return back()->withErrors([
                    'folder_id' => 'Folder tujuan tidak valid.',
                ]);
            }
        }

        DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $userId)
            ->update(['folder_id' => $folderId]);

        return back()->with('status', 'File berhasil dipindahkan.');
    }
}
