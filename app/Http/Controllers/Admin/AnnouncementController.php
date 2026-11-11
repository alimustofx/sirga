<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Audit;
use App\Support\PayrollPresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Announcements', [
            'items' => Announcement::with('recipients:id')->orderByDesc('id')->get()->map(fn ($a) => [
                'id' => $a->id, 'title' => $a->title, 'content' => $a->content, 'target_type' => $a->target_type, 'state' => $a->state(),
                'period_id' => $a->payroll_period_id, 'published_at' => $a->published_at?->toIso8601String(), 'recipients' => $a->recipients->pluck('id')->all(),
            ]),
            'employees' => Employee::active()->orderBy('full_name')->get(['id', 'full_name', 'nip']),
            'periods' => PayrollPeriod::newestFirst()->get()->map(fn ($p) => PayrollPresenter::period($p))->values(),
        ]);
    }

    public function store(Request $request)
    {
        [$data, $recipients] = $this->validated($request);
        $a = Announcement::create($data + ['created_by' => $request->user()->id, 'is_active' => true]);
        $a->recipients()->sync($recipients);
        Audit::log('announcement.create', $a, null, ['judul' => $a->title, 'target' => $a->target_type]);
        $this->notifyIfPublished($a);
        $this->toast('success', 'Pengumuman disimpan.');

        return back();
    }

    public function update(Request $request, Announcement $announcement)
    {
        [$data, $recipients] = $this->validated($request);
        $wasPublished = $announcement->state() === 'published';
        $announcement->update($data);
        $announcement->recipients()->sync($recipients);
        Audit::log('announcement.update', $announcement, null, ['judul' => $announcement->title]);
        if (! $wasPublished) {
            $this->notifyIfPublished($announcement);
        }
        $this->toast('success', 'Pengumuman diperbarui.');

        return back();
    }

    public function archive(Announcement $announcement)
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);
        Audit::log('announcement.archive', $announcement, null, ['aktif' => $announcement->is_active]);
        $this->toast('success', $announcement->is_active ? 'Pengumuman diaktifkan kembali.' : 'Pengumuman diarsipkan.');

        return back();
    }

    public function destroy(Announcement $announcement)
    {
        Audit::log('announcement.delete', $announcement, ['judul' => $announcement->title]);
        $announcement->delete();
        $this->toast('success', 'Pengumuman dihapus.');

        return back();
    }

    /** @return array{0:array,1:array} */
    private function validated(Request $request): array
    {
        $d = $request->validate([
            'title' => ['required', 'string', 'max:190'], 'content' => ['required', 'string', 'max:5000'],
            'target_type' => ['required', Rule::in(['all', 'pns', 'pppk', 'individual'])],
            'recipients' => ['nullable', 'array'], 'recipients.*' => ['integer', 'exists:employees,id'],
            'period_id' => ['nullable', 'exists:payroll_periods,id'],
            'publish_mode' => ['required', Rule::in(['draft', 'now', 'schedule'])],
            'publish_at' => ['nullable', 'date', 'required_if:publish_mode,schedule'],
        ], ['publish_at.required_if' => 'Tanggal penjadwalan wajib diisi.']);

        if ($d['target_type'] === 'individual' && empty($d['recipients'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['recipients' => 'Pilih minimal satu penerima.']);
        }

        $publishedAt = match ($d['publish_mode']) { 'now' => now(), 'schedule' => \Illuminate\Support\Carbon::parse($d['publish_at'], config('sirga.timezone')), default => null };

        return [[
            'title' => $d['title'], 'content' => $d['content'], 'target_type' => $d['target_type'],
            'payroll_period_id' => $d['period_id'] ?? null, 'published_at' => $publishedAt,
        ], $d['target_type'] === 'individual' ? $d['recipients'] : []];
    }

    private function notifyIfPublished(Announcement $a): void
    {
        if ($a->state() !== 'published') {
            return;
        }
        $employees = Employee::active()->whereNotNull('user_id')->when($a->target_type === 'pns', fn ($q) => $q->where('employment_status', 'PNS'))
            ->when($a->target_type === 'pppk', fn ($q) => $q->where('employment_status', 'PPPK'))
            ->when($a->target_type === 'individual', fn ($q) => $q->whereIn('id', $a->recipients()->pluck('employees.id')))->pluck('user_id');
        Notifier::send(User::whereIn('id', $employees)->get(), 'announcement', 'Pengumuman baru: '.$a->title, null, '/saya/pengumuman/'.$a->id);
    }
}
