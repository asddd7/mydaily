<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotesController extends Controller
{
    public function index(Request $request): View
    {
        $notes = DB::table('notes')
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->orderByDesc('created_at')
            ->get();

        return view('notes.index', compact('notes'));
    }

    public function show(Request $request, int $note): View
    {
        $note = DB::table('notes')
            ->where('id', $note)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->first();
        abort_unless($note, 404);

        return view('notes.show', compact('note'));
    }

    public function image(Request $request, int $note): BinaryFileResponse
    {
        $record = DB::table('notes')
            ->where('id', $note)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->first(['image']);
        abort_unless($record && $record->image, 404);

        $path = base_path('uploads/'.basename($record->image));
        abort_unless(File::isFile($path), 404);
        abort_unless(in_array(File::mimeType($path), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true), 404);

        return response()->file($path, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
        $imageName = $request->file('image')?->hashName();

        if ($request->hasFile('image')) {
            $request->file('image')->move(base_path('uploads'), $imageName);
        }

        DB::table('notes')->insert([
            'user_id' => $request->attributes->get('currentUser')->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'image' => $imageName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('notes.index')->with('status', 'Catatan berhasil disimpan.');
    }

    public function update(Request $request, int $note): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
        $userId = $request->attributes->get('currentUser')->id;
        $existing = DB::table('notes')->where('id', $note)->where('user_id', $userId)->first();
        abort_unless($existing, 404);

        $imageName = $existing->image;
        if ($request->hasFile('image')) {
            $imageName = $request->file('image')->hashName();
            $request->file('image')->move(base_path('uploads'), $imageName);
        }

        DB::table('notes')->where('id', $note)->where('user_id', $userId)->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'image' => $imageName,
            'updated_at' => now(),
        ]);

        if ($imageName !== $existing->image && $existing->image) {
            $oldPath = base_path('uploads/'.$existing->image);
            if (File::exists($oldPath) && !File::delete($oldPath)) {
                throw new \RuntimeException('Unable to remove the replaced note image.');
            }
        }

        return redirect()->route('notes.show', $note)->with('status', 'Catatan berhasil diperbarui.');
    }

    public function destroy(Request $request, int $note): RedirectResponse
    {
        $userId = $request->attributes->get('currentUser')->id;
        $existing = DB::table('notes')->where('id', $note)->where('user_id', $userId)->first();
        abort_unless($existing, 404);

        if ($existing->image) {
            $imagePath = base_path('uploads/'.$existing->image);
            if (File::exists($imagePath) && !File::delete($imagePath)) {
                throw new \RuntimeException('Unable to remove the note image.');
            }
        }

        DB::table('notes')->where('id', $note)->where('user_id', $userId)->delete();

        return redirect()->route('notes.index')->with('status', 'Catatan berhasil dihapus.');
    }

    public function compatibilityAction(Request $request): RedirectResponse
    {
        if ($request->has('update_note') && $request->filled('note_id')) {
            return $this->update($request, $request->integer('note_id'));
        }

        return $this->store($request);
    }
}
