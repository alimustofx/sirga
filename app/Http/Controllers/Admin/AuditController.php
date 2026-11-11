<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AuditLabels;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::with('user');

        if (! $request->user()->isSuperAdmin()) {
            $prefixes = AuditLabels::financePrefixes();
            $q->where(function ($w) use ($prefixes) {
                foreach ($prefixes as $p) {
                    $w->orWhere('action', 'like', $p.'%');
                }
            });
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('action', 'like', "%{$s}%")->orWhere('reason', 'like', "%{$s}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('nip', 'like', "%{$s}%")));
        }
        if ($f = $request->query('from')) {
            $q->whereDate('created_at', '>=', $f);
        }
        if ($t = $request->query('to')) {
            $q->whereDate('created_at', '<=', $t);
        }

        return Inertia::render('admin/Audit', [
            'logs' => $q->latest('id')->paginate(25)->withQueryString()->through(fn ($l) => [
                'id' => $l->id, 'label' => AuditLabels::label($l->action), 'action' => $l->action, 'user' => $l->user?->name ?? 'Sistem/Tidak login',
                'nip' => $l->user?->nip, 'entity' => $l->entity_type ? $l->entity_type.' #'.$l->entity_id : null,
                'old' => $l->old_values, 'new' => $l->new_values, 'reason' => $l->reason, 'ip' => $l->ip_address, 'at' => $l->created_at->toIso8601String(),
            ]),
            'filters' => ['q' => $request->query('q', ''), 'from' => $request->query('from', ''), 'to' => $request->query('to', '')],
        ]);
    }
}
