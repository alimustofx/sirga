<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComponentType;
use App\Http\Controllers\Controller;
use App\Models\EarningComponent;
use App\Models\PayrollRecordItem;
use App\Services\Payroll\SourceFormat;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ComponentController extends Controller
{
    public function index()
    {
        $usage = PayrollRecordItem::selectRaw('earning_component_id, count(*) c')->groupBy('earning_component_id')->pluck('c', 'earning_component_id');

        return Inertia::render('admin/Components', [
            'components' => EarningComponent::orderBy('component_type')->orderBy('display_order')->orderBy('id')->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'type' => $c->component_type->value, 'type_label' => $c->component_type->label(),
                'applicable_status' => $c->applicable_status, 'display_order' => $c->display_order, 'is_active' => $c->is_active, 'usage' => (int) ($usage[$c->id] ?? 0),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $code = SourceFormat::code(ComponentType::from($data['component_type']), $data['name']);
        if (EarningComponent::where('code', $code)->exists()) {
            return back()->withErrors(['name' => 'Komponen dengan nama tersebut sudah ada.']);
        }
        $c = EarningComponent::create($data + ['code' => $code]);
        Audit::log('component.create', $c, null, $c->only(['name', 'component_type']));
        $this->toast('success', 'Komponen ditambahkan.');

        return back();
    }

    public function update(Request $request, EarningComponent $component)
    {
        $data = $this->validated($request);
        unset($data['component_type']); // jenis tidak diubah agar riwayat tetap konsisten
        $old = $component->only(['name', 'applicable_status', 'display_order', 'is_active']);
        $component->update($data);
        Audit::log('component.update', $component, $old, $component->only(array_keys($old)));
        $this->toast('success', 'Komponen disimpan.');

        return back();
    }

    public function destroy(EarningComponent $component)
    {
        if (PayrollRecordItem::where('earning_component_id', $component->id)->exists()) {
            $this->toast('error', 'Komponen sudah dipakai pada data gaji. Nonaktifkan saja.');

            return back();
        }
        Audit::log('component.delete', $component, $component->only(['name']));
        $component->delete();
        $this->toast('success', 'Komponen dihapus.');

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'component_type' => ['required', Rule::enum(ComponentType::class)],
            'applicable_status' => ['required', Rule::in(['all', 'PNS', 'PPPK'])],
            'display_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
