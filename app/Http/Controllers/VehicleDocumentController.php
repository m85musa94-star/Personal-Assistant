<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDocuments;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VehicleDocumentController extends Controller
{
    use ValidatesDocuments;

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->permit($request, 'vehicles.edit');
        $this->guardCompany($vehicle->company_id);
        $vehicle->documents()->create($this->validatedDocument($request, VehicleDocument::TYPES));

        return $this->back($vehicle, __('تمت إضافة الوثيقة.'));
    }

    public function update(Request $request, VehicleDocument $document): RedirectResponse
    {
        $this->permit($request, 'vehicles.edit');
        $this->guardCompany($document->vehicle->company_id);
        $document->update($this->validatedDocument($request, VehicleDocument::TYPES));

        return $this->back($document->vehicle, __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, VehicleDocument $document): RedirectResponse
    {
        $this->permit($request, 'vehicles.edit');
        $vehicle = $document->vehicle;
        $this->guardCompany($vehicle->company_id);
        $document->delete();

        return $this->back($vehicle, __('تم حذف الوثيقة.'));
    }

    private function back(Vehicle $vehicle, string $msg): RedirectResponse
    {
        return redirect()->route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'documents'])->with('ok', $msg);
    }
}
