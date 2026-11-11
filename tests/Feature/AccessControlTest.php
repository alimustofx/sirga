<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** AC-02 / AC-03: isolasi data guru dan pembatasan akses admin. */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(string $nip, bool $mustChange = false): array
    {
        $user = User::create(['nip' => $nip, 'name' => "Guru {$nip}", 'password' => 'Password123', 'role_id' => Role::where('name', Role::TEACHER)->value('id'), 'must_change_password' => $mustChange]);
        $emp = Employee::create(['user_id' => $user->id, 'nip' => $nip, 'full_name' => "Guru {$nip}", 'employment_status' => 'PNS']);

        return [$user, $emp];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/saya')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_teacher_cannot_open_admin_area(): void
    {
        [$user] = $this->teacher('111111111111111111');
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/penghasilan')->assertForbidden();
    }

    public function test_must_change_password_redirects(): void
    {
        [$user] = $this->teacher('222222222222222222', mustChange: true);
        $this->actingAs($user)->get('/saya')->assertRedirect('/ganti-password');
    }

    public function test_teacher_cannot_read_other_teachers_payroll_or_proof(): void
    {
        [$a] = $this->teacher('333333333333333333');
        [, $empB] = $this->teacher('444444444444444444');

        $period = PayrollPeriod::create(['period_month' => 11, 'period_year' => 2026, 'status' => 'published']);
        $record = PayrollRecord::create(['payroll_period_id' => $period->id, 'employee_id' => $empB->id]);
        $payment = Payment::create(['payroll_record_id' => $record->id, 'total_amount' => 1000, 'status' => 'menunggu_verifikasi']);
        $proof = PaymentProof::create(['payment_id' => $payment->id, 'file_path' => 'x.png', 'original_filename' => 'x.png', 'mime_type' => 'image/png', 'file_size' => 1, 'uploaded_at' => now()]);

        $this->actingAs($a)->get("/saya/rincian/{$period->id}")->assertNotFound();   // A tidak punya rincian di periode ini
        $this->actingAs($a)->get("/saya/bukti/{$proof->id}")->assertNotFound();
    }

    public function test_unpublished_period_is_hidden_from_teacher(): void
    {
        [$user, $emp] = $this->teacher('555555555555555555');
        $period = PayrollPeriod::create(['period_month' => 12, 'period_year' => 2026, 'status' => 'draft']);
        PayrollRecord::create(['payroll_period_id' => $period->id, 'employee_id' => $emp->id]);

        $this->actingAs($user)->get("/saya/rincian/{$period->id}")->assertNotFound();
        $this->actingAs($user)->get("/saya/slip/{$period->id}")->assertNotFound();
    }

    public function test_login_uses_nip_and_rejects_inactive_accounts(): void
    {
        [$user] = $this->teacher('666666666666666666');
        $this->post('/login', ['nip' => '6666 6666 6666 666666', 'password' => 'Password123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $user->update(['is_active' => false]);
        $this->post('/login', ['nip' => '666666666666666666', 'password' => 'Password123'])->assertSessionHasErrors('nip');
        $this->assertGuest();
    }
}
