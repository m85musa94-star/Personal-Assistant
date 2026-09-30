<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDocuments;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    use ValidatesDocuments;

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->manage($request);
        $employee->documents()->create($this->validatedDocument($request, EmployeeDocument::TYPES));

        return $this->back($employee, __('تمت إضافة الوثيقة.'));
    }

    public function update(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->manage($request);
        $document->update($this->validatedDocument($request, EmployeeDocument::TYPES));

        return $this->back($document->employee, __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->manage($request);
        $employee = $document->employee;
        $document->delete();

        return $this->back($employee, __('تم حذف الوثيقة.'));
    }

    private function back(Employee $employee, string $msg): RedirectResponse
    {
        return redirect()->route('employees.show', ['employee' => $employee, 'tab' => 'documents'])->with('ok', $msg);
    }
}
