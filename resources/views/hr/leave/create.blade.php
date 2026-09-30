@extends('layouts.shell')
@section('title', __('طلب إجازة').' — '.__('مركز القيادة'))
@section('content')
<div class="page-h"><h1>{{ __('طلب إجازة') }}</h1></div>
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="card" style="max-width:720px">
  <form method="POST" action="{{ route('hr.leave.store') }}" class="f" id="lf">@csrf
    @if($isHr)
      <label class="w">{{ __('الموظف') }}<select name="employee_id" required>@foreach($employees as $e)<option value="{{ $e->id }}" @selected((string) old('employee_id', $me?->id) === (string) $e->id)>{{ $e->displayName() }}</option>@endforeach</select></label>
    @else
      <div class="w"><b>{{ $me?->displayName() }}</b></div>
    @endif
    <label class="w">{{ __('نوع الإجازة') }}<select name="leave_type_id" required>@foreach($types as $t)<option value="{{ $t->id }}" @selected((string) old('leave_type_id') === (string) $t->id)>{{ $t->displayName() }}</option>@endforeach</select></label>
    <label>{{ __('من') }}<input type="date" name="start_date" value="{{ old('start_date') }}" required></label>
    <label>{{ __('إلى') }}<input type="date" name="end_date" value="{{ old('end_date') }}" required></label>
    <label>{{ __('عدد الأيام') }}<input type="number" step="0.5" min="0.5" name="days" id="days" value="{{ old('days') }}" dir="ltr"></label>
    <div class="sub" style="align-self:end;margin:0" id="hint">{{ __('يُحتسب تلقائيًا بالأيام التقويمية (شاملة الجمعة والسبت والعطلات). عدّله يدويًا إن لزم.') }}</div>
    <label class="w">{{ __('السبب (اختياري)') }}<textarea name="reason" rows="3">{{ old('reason') }}</textarea></label>
    @if($isHr)<label class="chkrow w"><input type="checkbox" name="approve_now" value="1"> {{ __('اعتماد مباشر (إجازة مسجّلة من الموارد البشرية)') }}</label>@endif
    <div class="w" style="display:flex;gap:8px"><button class="btn">{{ __('إرسال') }}</button><a class="btn sec" href="{{ route('hr.leave.index') }}">{{ __('إلغاء') }}</a></div>
  </form>
</div>
<script>
(function(){var f=document.getElementById('lf'),s=f.start_date,e=f.end_date,d=document.getElementById('days');
function calc(){if(!s.value||!e.value)return;var a=new Date(s.value),b=new Date(e.value);var n=Math.round((b-a)/864e5)+1;if(n>0&&!d.dataset.manual)d.value=n}
d.addEventListener('input',function(){d.dataset.manual=1});s.addEventListener('change',calc);e.addEventListener('change',calc);calc()})();
</script>
@endsection
