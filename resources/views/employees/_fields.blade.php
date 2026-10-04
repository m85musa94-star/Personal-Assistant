@php $dis = ! auth()->user()->can('employees.edit'); $dt = fn ($k) => old($k, $employee->{$k}?->format('Y-m-d')); @endphp
<div class="o-title"><label>{{ __('اسم الموظف') }}</label><input name="name" value="{{ old('name', $employee->name) }}" required @disabled($dis) placeholder="{{ __('الاسم بالعربية') }}"></div>
<div class="o-fields">
  <div class="o-f"><label>{{ __('الاسم (إنجليزي)') }}</label><input name="name_en" value="{{ old('name_en', $employee->name_en) }}" dir="ltr" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('الشركة') }}</label><select name="company_id" required @disabled($dis)>@foreach($companies as $c)<option value="{{ $c->id }}" @selected((string) old('company_id', $employee->company_id) === (string) $c->id)>{{ $c->displayName() }}</option>@endforeach</select></div>
  <div class="o-f"><label>{{ __('المسمى الوظيفي') }}</label><input name="job_title" required value="{{ old('job_title', $employee->job_title) }}" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('رقم الموظف') }}</label><input name="code" required value="{{ old('code', $employee->code) }}" dir="ltr" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('الجنسية') }}</label><input name="nationality" required value="{{ old('nationality', $employee->nationality) }}" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('تاريخ التعيين') }}</label><input type="date" name="hire_date" required value="{{ $dt('hire_date') }}" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('الجوال') }}</label><input name="phone" required value="{{ old('phone', $employee->phone) }}" dir="ltr" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('البريد الإلكتروني') }}</label><input type="email" name="email" value="{{ old('email', $employee->email) }}" dir="ltr" @disabled($dis)></div>
</div>
