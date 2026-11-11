<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class PasswordChangeController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('auth/ChangePassword', ['forced' => $request->user()->must_change_password]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);

        $user = $request->user();
        $user->update(['password' => $data['password'], 'must_change_password' => false]);
        Audit::log('user.change_password', $user->employee, null, null, null, $user);
        $this->toast('success', 'Password berhasil diperbarui.');

        return redirect('/');
    }
}
