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
        $this->manage($request);
        $vehicle->documents()->create($this->validatedDocument($request, VehicleDocument::TYPES));

        return $this->back($vehicle, __('تمت إضافة الوثيقة.'));
    }

    public function update(Request $request, VehicleDocument $document): RedirectResponse
    {
        $this->manage($request);
        $document->update($this->validatedDocument($request, VehicleDocument::TYPES));

        return $this->back($document->vehicle, __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, VehicleDocument $document): RedirectResponse
    {
        $this->manage($request);
        $vehicle = $document->vehicle;
        $document->delete();

        return $this->back($vehicle, __('تم حذف الوثيقة.'));
    }

    private function back(Vehicle $vehicle, string $msg): RedirectResponse
    {
        return redirect()->route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'documents'])->with('ok', $msg);
    }
}
