<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['company' => ['required']]);
        $id = $data['company'] === 'all' ? null : (int) $data['company'];
        abort_if($id !== null && ! Company::whereKey($id)->exists(), 422);
        CompanyContext::set($id);

        return back();
    }
}
