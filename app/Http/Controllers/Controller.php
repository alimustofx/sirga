<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use InvalidArgumentException;

abstract class Controller
{
    protected function toast(string $type, string $message): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }

    /**
     * Jalankan aksi domain; kesalahan bisnis (DomainException) menjadi toast error, bukan 500.
     */
    protected function attempt(callable $action, string $success, ?callable $redirect = null): RedirectResponse
    {
        try {
            $result = $action();
        } catch (\DomainException|InvalidArgumentException $e) {
            $this->toast('error', $e->getMessage());

            return back();
        }
        $this->toast('success', $success);

        return $redirect ? $redirect($result) : back();
    }
}
