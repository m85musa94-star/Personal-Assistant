{{-- نموذج وثيقة (إضافة/تعديل) للموظفين والمركبات. المتغيرات: action, method, types, group, doc|null, cancel|null --}}
<form method="POST" action="{{ $action }}" class="inline-form">
  @csrf @if($method !== 'POST') @method($method) @endif
  <label>{{ __('النوع') }}<select name="type" required onchange="this.form.title.closest('label').style.display=this.value==='other'?'flex':'none'">@foreach($types as $t)<option value="{{ $t }}" @selected(($doc->type ?? null) === $t)>{{ __('types.'.$group.'.'.$t) }}</option>@endforeach</select></label>
  <label style="display:{{ ($doc->type ?? '') === 'other' ? 'flex' : 'none' }}">{{ __('اسم الوثيقة') }}<input name="title" value="{{ $doc->title ?? '' }}"></label>
  <label>{{ __('الرقم') }}<input name="number" value="{{ $doc->number ?? '' }}" dir="ltr"></label>
  <label>{{ __('الجهة / الشركة') }}<input name="provider" value="{{ $doc->provider ?? '' }}"></label>
  <label>{{ __('تاريخ الإصدار') }}<input type="date" name="issue_date" value="{{ $doc?->issue_date?->format('Y-m-d') }}"></label>
  <label>{{ __('تاريخ الانتهاء') }}<input type="date" name="expiry_date" value="{{ $doc?->expiry_date?->format('Y-m-d') }}"></label>
  <label>{{ __('ملاحظات') }}<input name="notes" value="{{ $doc->notes ?? '' }}"></label>
  <div style="display:flex;gap:6px"><button class="btn">{{ $doc ? __('حفظ') : __('إضافة') }}</button>@if($cancel)<a class="btn sec" href="{{ $cancel }}">{{ __('إلغاء') }}</a>@endif</div>
</form>
