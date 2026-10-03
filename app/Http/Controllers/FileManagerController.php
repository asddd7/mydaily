<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileManagerController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'mkv', 'webm',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt',
    ];

    public function index(Request $request): View
    {
        $files = DB::table('file_manager')
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->orderByDesc('id')
            ->get();

        return view('files.index', compact('files'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:51200']]);
        $upload = $request->file('file');
        $extension = strtolower($upload->getClientOriginalExtension());
        abort_unless(in_array($extension, self::ALLOWED_EXTENSIONS, true), 422, 'Format file tidak diizinkan.');

        $mime = $upload->getMimeType();
        $allowedMime = [
            'image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm',
            'video/x-msvideo', 'video/quicktime', 'video/x-matroska',
            'application/octet-stream', 'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip', 'application/x-rar-compressed', 'text/plain',
        ];
        abort_unless(in_array($mime, $allowedMime, true), 422, 'Tipe file tidak valid.');

        $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $upload->getClientOriginalName());
        $newName = Str::random(64).'.'.$extension;
        $fileSize = $upload->getSize();
        $directory = base_path('uploads/files');
        File::ensureDirectoryExists($directory);
        $upload->move($directory, $newName);

        DB::table('file_manager')->insert([
            'user_id' => $request->attributes->get('currentUser')->id,
            'file_name' => $newName,
            'file_original' => $originalName,
            'file_size' => $fileSize,
            'file_type' => $extension,
            'created_at' => now(),
        ]);

        return redirect()->route('files.index')->with('status', 'File berhasil diunggah.');
    }

    public function update(Request $request, int $file): RedirectResponse
    {
        $data = $request->validate(['file_original' => ['required', 'string', 'max:255']]);
        $updated = DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->update(['file_original' => preg_replace('/[^a-zA-Z0-9._ -]/', '_', $data['file_original'])]);
        abort_unless($updated, 404);

        return redirect()->route('files.index')->with('status', 'Nama file berhasil diubah.');
    }

    public function destroy(Request $request, int $file): RedirectResponse
    {
        $userId = $request->attributes->get('currentUser')->id;
        $record = DB::table('file_manager')->where('id', $file)->where('user_id', $userId)->first();
        abort_unless($record, 404);

        $path = base_path('uploads/files/'.basename($record->file_name));
        if (File::exists($path) && !File::delete($path)) {
            throw new \RuntimeException('Unable to remove an uploaded file from disk.');
        }
        DB::table('file_manager')->where('id', $file)->where('user_id', $userId)->delete();

        return redirect()->route('files.index')->with('status', 'File berhasil dihapus.');
    }

    public function download(Request $request, int $file): BinaryFileResponse
    {
        $record = DB::table('file_manager')
            ->where('id', $file)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->first();
        abort_unless($record, 404);

        $path = base_path('uploads/files/'.basename($record->file_name));
        abort_unless(File::isFile($path), 404);

        return response()->download($path, basename($record->file_original));
    }
}
