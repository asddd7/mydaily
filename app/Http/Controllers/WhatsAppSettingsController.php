<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class WhatsAppSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->attributes->get('currentUser')->id;
        $account = DB::table('whatsapp_accounts')->where('user_id', $userId)->first();
        $botConfigured = $this->botConfigured();

        return view('whatsapp.settings', compact('account', 'botConfigured'));
    }

    public function issueLinkCode(Request $request): RedirectResponse
    {
        if (!$this->botConfigured()) {
            return redirect()->route('whatsapp.settings')
                ->with('error', 'Integrasi WhatsApp belum dikonfigurasi oleh administrator.');
        }

        $userId = $request->attributes->get('currentUser')->id;
        $account = DB::table('whatsapp_accounts')->where('user_id', $userId)->first();
        if ($account?->phone_number) {
            return redirect()->route('whatsapp.settings')
                ->with('error', 'Putuskan tautan nomor saat ini sebelum membuat kode baru.');
        }

        $code = null;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = (string) random_int(10000000, 99999999);
            $exists = DB::table('whatsapp_accounts')
                ->where('pairing_code_hash', hash('sha256', $candidate))
                ->exists();
            if (!$exists) {
                $code = $candidate;
                break;
            }
        }

        if ($code === null) {
            throw new RuntimeException('Unable to generate a unique WhatsApp pairing code.');
        }

        DB::table('whatsapp_accounts')->updateOrInsert(
            ['user_id' => $userId],
            [
                'pairing_code_hash' => hash('sha256', $code),
                'pairing_expires_at' => now()->addMinutes(10),
                'phone_number' => null,
                'linked_at' => null,
                'updated_at' => now(),
                'created_at' => $account?->created_at ?? now(),
            ],
        );

        return redirect()->route('whatsapp.settings')->with('link_code', $code);
    }

    public function unlink(Request $request): RedirectResponse
    {
        DB::table('whatsapp_accounts')
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->delete();

        return redirect()->route('whatsapp.settings')
            ->with('status', 'Nomor WhatsApp berhasil diputuskan dari akun MyDaily.');
    }

    private function botConfigured(): bool
    {
        foreach ([
            config('services.whatsapp.access_token'),
            config('services.whatsapp.phone_number_id'),
            config('services.whatsapp.app_secret'),
            config('services.whatsapp.verify_token'),
        ] as $value) {
            if (!is_string($value) || $value === '') {
                return false;
            }
        }

        $version = config('services.whatsapp.graph_api_version');

        return is_string($version) && preg_match('/^v\d+\.\d+$/', $version) === 1;
    }
}
