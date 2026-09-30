@extends('layouts.shell')
@section('title', __('الموارد البشرية').' — '.__('مركز القيادة'))
@section('content')
<div class="page-h"><h1>{{ __('الموارد البشرية') }}</h1><span class="sp"></span>
  @if($isHr)<a class="btn" href="{{ route('hr.employees.create') }}"><x-icon name="plus"/> {{ __('موظف جديد') }}</a>@endif
  <a class="btn sec" href="{{ route('hr.leave.create') }}"><x-icon name="calendar-off"/> {{ __('طلب إجازة') }}</a>
</div>

@if(! $me)
  <div class="warn">{{ __('حسابك غير مرتبط بموظف، فلن تعمل لك ميزات الحضور وطلب الإجازة الذاتية. اطلب من مسؤول الموارد البشرية ربطه.') }}</div>
@endif

<div class="grid g4">
  <div class="card kpi"><span class="ic"><x-icon name="users"/></span><div><b>{{ $headcount }}</b><span>{{ __('موظف نشط') }}</span></div></div>
  <div class="card kpi"><span class="ic vio"><x-icon name="calendar-off"/></span><div><b>{{ $onLeave->count() }}</b><span>{{ __('في إجازة اليوم') }}</span></div></div>
  <div class="card kpi"><span class="ic amb"><x-icon name="inbox"/></span><div><b>{{ $pending->count() }}</b><span>{{ __('بانتظار الاعتماد') }}</span></div></div>
  <div class="card kpi"><span class="ic grn"><x-icon name="clock"/></span><div><b>{{ $checkedIn->count() }}</b><span>{{ __('على رأس العمل الآن') }}</span></div></div>
</div><br>

<div class="grid g2">
  <div class="card">
    <h3><x-icon name="inbox"/> {{ __('بانتظار اعتمادك') }}</h3>
    @forelse($pending as $l)
      <div class="row static">
        <x-avatar :employee="$l->employee"/>
        <div class="ttl"><b>{{ $l->employee->displayName() }}</b>
          <div class="meta"><span class="tag">{{ $l->type->displayName() }}</span><span class="tag">{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}</span><span class="tag">{{ rtrim(rtrim(number_format($l->days, 1), '0'), '.') }} {{ __('يوم') }}</span></div></div>
        <a class="btn sm" href="{{ route('hr.leave.index', ['tab' => 'approvals']) }}">{{ __('مراجعة') }}</a>
      </div>
    @empty <div class="empty">{{ __('لا طلبات معلّقة') }}</div> @endforelse
  </div>

  <div class="card">
    <h3><x-icon name="calendar-off"/> {{ __('في إجازة اليوم') }}</h3>
    @forelse($onLeave as $l)
      <div class="row static"><x-avatar :employee="$l->employee"/>
        <div class="ttl"><b>{{ $l->employee->displayName() }}</b><div class="meta"><span class="tag">{{ $l->type->displayName() }}</span><span class="tag">{{ __('حتى') }} {{ $l->end_date->fmt() }}</span></div></div></div>
    @empty <div class="empty">{{ __('الجميع متواجد') }}</div> @endforelse
    @if($soon->isNotEmpty())
      <h3 style="margin-top:14px">{{ __('إجازات قادمة (14 يومًا)') }}</h3>
      @foreach($soon as $l)
        <div class="row static"><x-avatar :employee="$l->employee" size="sm"/>
          <div class="ttl">{{ $l->employee->displayName() }} <span class="tag">{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}</span></div></div>
      @endforeach
    @endif
  </div>

  @if($isHr)
  <div class="card">
    <h3><x-icon name="alert"/> {{ __('وثائق وعقود منتهية أو قاربت الانتهاء (60 يومًا)') }}</h3>
    @forelse($expiring as $e)
      <div class="row static"><x-avatar :employee="$e"/>
        <div class="ttl"><a href="{{ route('hr.employees.show', $e) }}"><b>{{ $e->displayName() }}</b></a>
          <div class="meta">
            @foreach($e->expiringDocuments() as $doc)
              <span class="pill {{ $doc['days'] < 0 ? 'red' : 'amb' }}">{{ __($doc['label']) }}: {{ $doc['days'] < 0 ? __('منتهية منذ :n يوم', ['n' => abs($doc['days'])]) : __('بعد :n يوم', ['n' => $doc['days']]) }} ({{ $doc['date']->fmt() }})</span>
            @endforeach
          </div></div></div>
    @empty <div class="empty">{{ __('لا وثائق قاربت الانتهاء') }}</div> @endforelse
  </div>
  @endif

  <div class="card">
    <h3><x-icon name="building"/> {{ __('الموظفون حسب القسم') }}</h3>
    @php $max = max(1, $byDept->max('active_count'), $unassigned); @endphp
    @foreach($byDept as $d)
      <div>{{ $d->displayName() }} — {{ $d->active_count }}<div class="bar-p"><i style="width:{{ $d->active_count / $max * 100 }}%"></i></div></div>
    @endforeach
    @if($unassigned)<div>{{ __('بدون قسم') }} — {{ $unassigned }}<div class="bar-p"><i style="width:{{ $unassigned / $max * 100 }}%"></i></div></div>@endif
    @if($byDept->isEmpty() && ! $unassigned)<div class="empty">{{ __('لا بيانات بعد') }}</div>@endif
  </div>

  @if($newHires->isNotEmpty())
  <div class="card">
    <h3><x-icon name="user"/> {{ __('انضموا حديثًا (30 يومًا)') }}</h3>
    @foreach($newHires as $e)
      <div class="row static"><x-avatar :employee="$e"/><div class="ttl"><a href="{{ route('hr.employees.show', $e) }}"><b>{{ $e->displayName() }}</b></a><div class="meta"><span class="tag">{{ $e->job_title }}</span><span class="tag">{{ $e->hire_date->fmt() }}</span></div></div></div>
    @endforeach
  </div>
  @endif
</div>
@endsection
