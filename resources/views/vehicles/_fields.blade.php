@php $dis = ! auth()->user()->can('vehicles.edit'); $dt = fn ($k) => old($k, $vehicle->{$k}?->format('Y-m-d')); $opt = fn ($list, $group, $cur) => collect($list)->map(fn ($x) => '<option value="'.$x.'" '.($cur === $x ? 'selected' : '').'>'.e(__('types.'.$group.'.'.$x)).'</option>')->implode(''); @endphp
<div class="o-title"><label>{{ __('رقم اللوحة') }}</label><input name="plate" value="{{ old('plate', $vehicle->plate) }}" required @disabled($dis) placeholder="ABC 1234" dir="ltr" style="text-align:start"></div>
<div class="o-fields">
  <div class="o-f"><label>{{ __('الشركة') }}</label><select name="company_id" required @disabled($dis)>@foreach($companies as $c)<option value="{{ $c->id }}" @selected((string) old('company_id', $vehicle->company_id) === (string) $c->id)>{{ $c->displayName() }}</option>@endforeach</select></div>
  <div class="o-f"><label>{{ __('السائق المسؤول') }}</label><select name="driver_id" @disabled($dis)><option value="">—</option>@foreach($drivers as $d)<option value="{{ $d->id }}" @selected((string) old('driver_id', $vehicle->driver_id) === (string) $d->id)>{{ $d->displayName() }}</option>@endforeach</select></div>
  <div class="o-f"><label>{{ __('الماركة') }}</label><input name="make" value="{{ old('make', $vehicle->make) }}" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('الموديل') }}</label><input name="model" value="{{ old('model', $vehicle->model) }}" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('سنة الصنع') }}</label><input type="number" name="year" value="{{ old('year', $vehicle->year) }}" min="1950" max="2100" dir="ltr" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('اللون') }}</label><input name="color" value="{{ old('color', $vehicle->color) }}" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('النوع') }}</label><select name="type" @disabled($dis)><option value="">—</option>{!! $opt(\App\Models\Vehicle::TYPES, 'vehicle_type', old('type', $vehicle->type)) !!}</select></div>
  <div class="o-f"><label>{{ __('الوقود') }}</label><select name="fuel" @disabled($dis)><option value="">—</option>{!! $opt(\App\Models\Vehicle::FUELS, 'fuel', old('fuel', $vehicle->fuel)) !!}</select></div>
  <div class="o-f"><label>{{ __('رقم الهيكل (VIN)') }}</label><input name="vin" value="{{ old('vin', $vehicle->vin) }}" dir="ltr" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('العداد (كم)') }}</label><input type="number" name="odometer" value="{{ old('odometer', $vehicle->odometer) }}" min="0" dir="ltr" @disabled($dis)></div>
  <div class="o-f"><label>{{ __('تاريخ الشراء') }}</label><input type="date" name="purchase_date" value="{{ $dt('purchase_date') }}" @disabled($dis)></div>
</div>
