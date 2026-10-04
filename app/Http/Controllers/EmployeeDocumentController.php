<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDocuments;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    use ValidatesDocuments;

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->permit($request, 'employees.edit');
        $this->guardCompany($employee->company_id);
        $doc = $employee->documents()->create($this->validatedDocument($request, EmployeeDocument::TYPES));
        EmployeeRecord::log($employee, 'doc_added', $this->docData($doc));

        return $this->back($employee, __('تمت إضافة الوثيقة.'));
    }

    public function update(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->permit($request, 'employees.edit');
        $this->guardCompany($document->employee->company_id);
        $oldExpiry = $document->expiry_date?->format('Y-m-d');
        $document->update($this->validatedDocument($request, EmployeeDocument::TYPES));
        $newExpiry = $document->expiry_date?->format('Y-m-d');
        if ($oldExpiry !== $newExpiry) {
            EmployeeRecord::log($document->employee, 'doc_renewed', $this->docData($document) + ['from' => $oldExpiry ?: '—', 'to' => $newExpiry ?: '—']);
        } else {
            EmployeeRecord::log($document->employee, 'doc_edited', $this->docData($document));
        }

        return $this->back($document->employee, __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->permit($request, 'employees.edit');
        $employee = $document->employee;
        $this->guardCompany($employee->company_id);
        EmployeeRecord::log($employee, 'doc_deleted', $this->docData($document));
        $document->delete();

        return $this->back($employee, __('تم حذف الوثيقة.'));
    }

    private function docData(EmployeeDocument $d): array
    {
        $suffix = $d->number ? ' ('.$d->number.')' : '';

        return ['doc' => $d->label('ar').$suffix, 'doc_en' => $d->label('en').$suffix];
    }

    private function back(Employee $employee, string $msg): RedirectResponse
    {
        return redirect()->route('employees.show', ['employee' => $employee, 'tab' => 'documents'])->with('ok', $msg);
    }
}
