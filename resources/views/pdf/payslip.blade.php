@php
    use App\Support\Money;
    $rp = fn ($v) => Money::rupiah($v);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Slip Gaji {{ $employee->full_name }} - {{ $period->label }}</title>
<style>
    @page { margin: 28px 34px 34px 34px; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { font-size: 10px; color: #17212B; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    .head td { vertical-align: middle; }
    .school { font-size: 14px; font-weight: bold; color: #142B45; }
    .addr { font-size: 9px; color: #64748B; margin-top: 2px; }
    .rule { border-bottom: 2px solid #142B45; margin: 8px 0 10px; }
    .title { text-align: center; font-size: 13px; font-weight: bold; color: #142B45; letter-spacing: .5px; }
    .sub { text-align: center; font-size: 10px; color: #344252; margin: 2px 0 10px; }
    .id td { padding: 2px 4px; font-size: 10px; }
    .id td.k { width: 18%; color: #64748B; }
    .sec { background: #142B45; color: #fff; font-weight: bold; padding: 4px 8px; font-size: 10px; margin-top: 10px; }
    .rows td { padding: 4px 8px; border-bottom: 1px solid #E2E8F0; }
    .rows td.n { text-align: right; width: 32%; }
    .rows tr.tot td { font-weight: bold; background: #F1F5F9; border-bottom: 1px solid #A3AFBC; }
    .hl { margin-top: 10px; }
    .hl td { padding: 7px 8px; font-weight: bold; background: #E6F2F4; border: 1px solid #287B8C; font-size: 11px; }
    .final td { padding: 9px 10px; font-weight: bold; background: #142B45; color: #fff; font-size: 13px; }
    .muted { color: #64748B; }
    .sign { margin-top: 18px; }
    .sign td { vertical-align: top; font-size: 9px; }
    .note { font-size: 8px; color: #64748B; margin-top: 14px; border-top: 1px solid #E2E8F0; padding-top: 6px; }
</style>
</head>
<body>
    <table class="head">
        <tr>
            @if ($logo)
                <td style="width:58px"><img src="{{ $logo }}" style="width:52px;height:52px"></td>
            @endif
            <td>
                <div class="school">{{ strtoupper($setting->school_name) }}</div>
                @if ($setting->school_address)<div class="addr">{{ $setting->school_address }}</div>@endif
            </td>
            <td style="text-align:right;width:30%" class="muted">No. Dokumen<br><strong style="color:#17212B">{{ $docNumber }}</strong></td>
        </tr>
    </table>
    <div class="rule"></div>

    <div class="title">SLIP RINCIAN GAJI ASN</div>
    <div class="sub">Periode {{ $period->label }}</div>

    <table class="id">
        <tr><td class="k">Nama</td><td>: {{ $employee->full_name }}</td><td class="k">Status</td><td>: {{ $employee->employment_status->value }}</td></tr>
        <tr><td class="k">NIP</td><td>: {{ $employee->nip }}</td><td class="k">Golongan</td><td>: {{ $employee->rank ?: '-' }}</td></tr>
        <tr><td class="k">Jabatan</td><td>: {{ $employee->position ?: '-' }}</td><td class="k">Rekening</td><td>: {{ $employee->bank_account_number ?: '-' }}</td></tr>
    </table>

    <div class="sec">PENGHASILAN</div>
    <table class="rows">
        @foreach ($earnings as $i)
            <tr><td>{{ $i->component_name_snapshot }}</td><td class="n">{{ $rp($i->amount) }}</td></tr>
        @endforeach
        <tr class="tot"><td>Gaji Kotor</td><td class="n">{{ $rp($record->gross_salary) }}</td></tr>
    </table>

    <div class="sec">POTONGAN RESMI</div>
    <table class="rows">
        @forelse ($official as $i)
            <tr><td>{{ $i->component_name_snapshot }}</td><td class="n">{{ $rp($i->amount) }}</td></tr>
        @empty
            <tr><td class="muted" colspan="2">Tidak ada potongan resmi.</td></tr>
        @endforelse
        <tr class="tot"><td>Total Potongan Resmi</td><td class="n">{{ $rp($record->official_deductions_total) }}</td></tr>
    </table>

    <table class="hl"><tr><td>PENGHASILAN BERSIH</td><td style="text-align:right">{{ $rp($record->net_salary) }}</td></tr></table>

    <div class="sec">POTONGAN SEKOLAH</div>
    <table class="rows">
        @forelse ($deductions as $d)
            <tr><td>{{ $d->deduction_name_snapshot }}</td><td class="n">{{ $rp($d->amount) }}</td></tr>
        @empty
            <tr><td class="muted" colspan="2">Tidak ada potongan sekolah.</td></tr>
        @endforelse
        <tr class="tot"><td>Total Potongan Sekolah</td><td class="n">{{ $rp($record->school_deductions_total) }}</td></tr>
    </table>

    <table class="final" style="margin-top:12px"><tr><td>YANG DITERIMAKAN</td><td style="text-align:right">{{ $rp($record->take_home_pay) }}</td></tr></table>
    <div class="muted" style="margin-top:3px;font-size:8px">Yang Diterimakan = Penghasilan Bersih − Total Potongan Sekolah</div>

    <table class="sign">
        <tr>
            <td style="width:55%">
                @if ($account && (float) $record->school_deductions_total > 0)
                    <strong>Pembayaran potongan sekolah</strong><br>
                    Transfer {{ $rp($record->school_deductions_total) }} ke {{ $account->bank_name }}<br>
                    No. Rek. {{ $account->account_number }} a.n. {{ $account->account_holder }}
                @endif
            </td>
            <td style="text-align:center">
                Turen, {{ $printedAt->day.' '.\App\Support\Indonesian::month($printedAt->month).' '.$printedAt->year }}<br>Bendahara<br>
                @if ($qr)<img src="{{ $qr }}" style="width:62px;height:62px;margin:4px 0"><br>@else<br><br><br>@endif
                <strong>{{ $setting->treasurer_name ?: '........................' }}</strong><br>
                @if ($setting->treasurer_nip)NIP. {{ $setting->treasurer_nip }}@endif
            </td>
        </tr>
    </table>

    <div class="note">
        Dokumen ini dicetak dari Sistem Informasi Rincian Gaji ASN pada {{ $printedAt->format('d/m/Y H:i') }} WIB.
        QR pada bagian pengesahan merupakan tanda tangan visual dan bukan tanda tangan elektronik tersertifikasi.
    </div>
</body>
</html>
