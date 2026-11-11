<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\SchoolSetting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        $s = SchoolSetting::current();
        $a = PaymentAccount::current();

        return Inertia::render('admin/Settings', [
            'school' => ['school_name' => $s->school_name, 'school_address' => $s->school_address, 'has_logo' => filled($s->school_logo), 'has_letterhead' => filled($s->school_letterhead)],
            'document' => ['treasurer_name' => $s->treasurer_name, 'treasurer_nip' => $s->treasurer_nip, 'has_qr' => filled($s->treasurer_signature_qr), 'show_zero_deductions' => $s->show_zero_deductions, 'default_bank_information' => $s->default_bank_information],
            'account' => ['bank_name' => $a?->bank_name, 'account_number' => $a?->account_number, 'account_holder' => $a?->account_holder, 'payment_instruction' => $a?->payment_instruction],
            'isSuper' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function school(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:190'], 'school_address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:'.config('sirga.logo_max_kb')],
            'letterhead' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:'.config('sirga.logo_max_kb')],
        ]);
        $s = SchoolSetting::current();
        $s->fill(['school_name' => $data['school_name'], 'school_address' => $data['school_address'] ?? null]);
        $s->school_logo = $this->store($request, 'logo', $s->school_logo, 'logo');
        $s->school_letterhead = $this->store($request, 'letterhead', $s->school_letterhead, 'kop');
        $s->save();
        Audit::log('settings.school', $s, null, ['nama' => $s->school_name]);
        $this->toast('success', 'Identitas sekolah disimpan.');

        return back();
    }

    public function document(Request $request)
    {
        $data = $request->validate([
            'treasurer_name' => ['nullable', 'string', 'max:190'], 'treasurer_nip' => ['nullable', 'string', 'max:32'],
            'show_zero_deductions' => ['required', 'boolean'], 'default_bank_information' => ['nullable', 'string', 'max:500'],
            'qr' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ]);
        $s = SchoolSetting::current();
        $s->fill(collect($data)->except('qr')->all());
        $s->treasurer_signature_qr = $this->store($request, 'qr', $s->treasurer_signature_qr, 'qr');
        $s->save();
        Audit::log('settings.document', $s, null, ['bendahara' => $s->treasurer_name]);
        $this->toast('success', 'Konfigurasi dokumen disimpan.');

        return back();
    }

    public function account(Request $request)
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:120'], 'account_number' => ['required', 'string', 'max:64'],
            'account_holder' => ['required', 'string', 'max:190'], 'payment_instruction' => ['nullable', 'string', 'max:500'],
        ]);
        $old = PaymentAccount::current()?->only(['bank_name', 'account_number', 'account_holder']);
        $account = PaymentAccount::current() ?? new PaymentAccount(['is_active' => true]);
        $account->fill($data)->save();
        Audit::log('settings.account', $account, $old, $account->only(['bank_name', 'account_number', 'account_holder']));
        $this->toast('success', 'Rekening bendahara disimpan.');

        return back();
    }

    private function store(Request $request, string $field, ?string $current, string $prefix): ?string
    {
        if (! $request->hasFile($field)) {
            return $current;
        }
        if ($current) {
            Storage::disk('local')->delete($current);
        }
        $file = $request->file($field);

        return $file->storeAs('school-assets', $prefix.'-'.Str::random(16).'.'.$file->extension(), 'local');
    }
}
