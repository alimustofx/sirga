<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load(['role', 'employee']);
        $e = $user->employee;

        return Inertia::render($user->isFinance() ? 'admin/Profile' : 'guru/Profile', [
            'profile' => [
                'name' => $user->name, 'nip' => $user->nip, 'email' => $user->email, 'role' => $user->role->label,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'employee' => $e ? ['status' => $e->employment_status->value, 'rank' => $e->rank, 'position' => $e->position, 'unit' => $e->unit, 'account' => $e->bank_account_number] : null,
            ],
        ]);
    }

    public function updateEmail(Request $request)
    {
        $data = $request->validate(['email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($request->user()->id)]]);
        $request->user()->update(['email' => $data['email'] ?: null]);
        Audit::log('user.update_email', $request->user()->employee);
        $this->toast('success', 'Data akun berhasil disimpan.');

        return back();
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);
        $request->user()->update(['password' => $data['password'], 'must_change_password' => false]);
        Audit::log('user.change_password', $request->user()->employee);
        $this->toast('success', 'Password berhasil diperbarui.');

        return back();
    }
}
