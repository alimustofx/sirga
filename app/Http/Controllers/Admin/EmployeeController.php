<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Services\Payroll\SourceExcelParser;
use App\Services\UserAccountService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = Employee::with('user.role');
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('full_name', 'like', "%{$s}%")->orWhere('nip', 'like', '%'.preg_replace('/\s+/', '', $s).'%'));
        }
        if (in_array($request->query('status'), ['PNS', 'PPPK'], true)) {
            $q->where('employment_status', $request->query('status'));
        }
        if (in_array($request->query('active'), ['1', '0'], true)) {
            $q->where('is_active', $request->query('active') === '1');
        }

        return Inertia::render('admin/Employees', [
            'employees' => $q->orderBy('full_name')->paginate(15)->withQueryString()->through(fn (Employee $e) => [
                'id' => $e->id, 'nip' => $e->nip, 'full_name' => $e->full_name, 'academic_title' => $e->academic_title,
                'status' => $e->employment_status->value, 'rank' => $e->rank, 'position' => $e->position, 'unit' => $e->unit,
                'account' => $e->bank_account_number, 'npwp' => $e->npwp, 'is_active' => $e->is_active,
                'email' => $e->user?->email, 'role' => $e->user?->role?->name, 'role_label' => $e->user?->role?->label,
                'has_account' => $e->user !== null, 'last_login_at' => $e->user?->last_login_at?->toIso8601String(),
            ]),
            'filters' => ['q' => $request->query('q', ''), 'status' => $request->query('status', ''), 'active' => $request->query('active', '')],
            'canManage' => $request->user()->isSuperAdmin(),
            'roles' => Role::orderBy('id')->get(['name', 'label']),
        ]);
    }

    public function store(Request $request, UserAccountService $accounts)
    {
        $request->merge(['nip' => UserAccountService::normalizeNip((string) $request->input('nip'))]);
        $data = $request->validate([
            ...$this->rules(), 'nip' => ['required', 'regex:/^\d{9,25}$/', 'unique:employees,nip', 'unique:users,nip'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'max:64'],
        ]);

        [$employee, $plain] = $accounts->createEmployee($data, $data['password'] ?? null, $request->user());
        $this->toast('success', 'Akun ASN berhasil dibuat.');

        return back()->with('credentials', ['list' => [['nip' => $employee->nip, 'name' => $employee->full_name, 'password' => $plain]], 'skipped' => 0]);
    }

    public function update(Request $request, Employee $employee, UserAccountService $accounts)
    {
        $request->merge(['nip' => UserAccountService::normalizeNip((string) $request->input('nip'))]);
        $data = $request->validate([
            ...$this->rules(), 'nip' => ['required', 'regex:/^\d{9,25}$/', Rule::unique('employees', 'nip')->ignore($employee->id), Rule::unique('users', 'nip')->ignore($employee->user_id)],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($employee->user_id)],
        ]);
        $accounts->updateEmployee($employee, $data, $request->user());
        $this->toast('success', 'Data ASN berhasil disimpan.');

        return back();
    }

    public function toggle(Request $request, Employee $employee, UserAccountService $accounts)
    {
        abort_if($employee->user_id === $request->user()->id, 422, 'Anda tidak dapat menonaktifkan akun sendiri.');
        $accounts->setActive($employee, ! $employee->is_active, $request->user());
        $this->toast('success', $employee->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');

        return back();
    }

    public function resetPassword(Request $request, Employee $employee, UserAccountService $accounts)
    {
        abort_unless($employee->user, 422, 'ASN ini belum memiliki akun.');
        $plain = $accounts->resetPassword($employee, null, $request->user());
        $this->toast('success', 'Password berhasil direset.');

        return back()->with('credentials', ['list' => [['nip' => $employee->nip, 'name' => $employee->full_name, 'password' => $plain]], 'skipped' => 0]);
    }

    public function destroy(Request $request, Employee $employee)
    {
        if ($employee->payrollRecords()->exists()) {
            $this->toast('error', 'ASN yang sudah memiliki riwayat gaji tidak dapat dihapus. Nonaktifkan akun saja.');

            return back();
        }
        abort_if($employee->user_id === $request->user()->id, 422, 'Anda tidak dapat menghapus akun sendiri.');
        Audit::log('user.delete', $employee, ['nip' => $employee->nip, 'nama' => $employee->full_name]);
        $user = $employee->user;
        $employee->delete();
        $user?->delete();
        $this->toast('success', 'Data ASN dihapus.');

        return back();
    }

    /** Membuat akun massal dari file Excel format penggajian (kolom NIP, Nama, Gol/Ruang, Rekening). Hanya Super Admin. */
    public function bulkImport(Request $request, SourceExcelParser $parser, UserAccountService $accounts)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'], 'employment_status' => ['required', Rule::enum(EmploymentStatus::class)]]);

        try {
            $parsed = $parser->parseFile($request->file('file')->getRealPath());
        } catch (\DomainException $e) {
            $this->toast('error', $e->getMessage());

            return back();
        }

        $list = [];
        $skipped = 0;
        foreach ($parsed['rows'] as $row) {
            $nip = $row['nip'];
            if (! preg_match('/^\d{9,25}$/', $nip) || $row['name'] === '' || Employee::where('nip', $nip)->exists() || \App\Models\User::where('nip', $nip)->exists()) {
                $skipped++;

                continue;
            }
            [$e, $plain] = $accounts->createEmployee([
                'nip' => $nip, 'full_name' => $row['name'], 'employment_status' => $request->input('employment_status'),
                'rank' => $row['rank'], 'bank_account_number' => $row['account'], 'npwp' => $row['npwp'], 'unit' => $row['unit'], 'role' => Role::TEACHER,
            ], null, $request->user());
            $list[] = ['nip' => $e->nip, 'name' => $e->full_name, 'password' => $plain];
        }
        Audit::log('user.bulk_create', null, null, ['dibuat' => count($list), 'dilewati' => $skipped]);
        $this->toast($list ? 'success' : 'warning', count($list).' akun dibuat, '.$skipped.' baris dilewati (NIP kosong/tidak valid/sudah terdaftar).');

        return back()->with('credentials', ['list' => $list, 'skipped' => $skipped]);
    }

    private function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:190'],
            'academic_title' => ['nullable', 'string', 'max:120'],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'rank' => ['nullable', 'string', 'max:32'], 'position' => ['nullable', 'string', 'max:190'], 'unit' => ['nullable', 'string', 'max:190'],
            'bank_account_number' => ['nullable', 'string', 'max:64'], 'npwp' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::in([Role::SUPER_ADMIN, Role::FINANCE, Role::TEACHER])],
        ];
    }
}
