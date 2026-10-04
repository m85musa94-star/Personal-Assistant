<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return view('settings.companies.index', ['companies' => Company::allowed()->withCount(['employees', 'vehicles'])->orderBy('name')->get()]);
    }

    public function create(Request $request)
    {
        $this->permit($request, 'companies.edit');

        return view('settings.companies.form', ['company' => new Company(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->permit($request, 'companies.edit');
        $company = Company::create($this->validated($request));

        return redirect()->route('companies.show', $company)->with('ok', __('تمت إضافة الشركة.'));
    }

    public function show(Company $company)
    {
        $this->guardCompany($company->id);
        $company->loadCount(['employees', 'vehicles']);

        return view('settings.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->permit($request, 'companies.edit');
        $this->guardCompany($company->id);
        $company->update($this->validated($request));

        return redirect()->route('companies.show', $company)->with('ok', __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403, __('هذه الصفحة للمدير فقط'));
        if ($company->employees()->exists() || $company->vehicles()->exists()) {
            return back()->with('warn', __('لا يمكن حذف شركة فيها موظفون أو مركبات. انقلهم أولًا أو عطّل الشركة.'));
        }
        $company->delete();

        return redirect()->route('companies.index')->with('ok', __('تم حذف الشركة.'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'name_en' => ['nullable', 'string', 'max:160'],
            'cr_number' => ['nullable', 'string', 'max:40'],
            'tax_number' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
