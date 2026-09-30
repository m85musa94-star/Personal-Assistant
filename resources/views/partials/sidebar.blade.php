@php
    $u = auth()->user();
    $path = request()->path();
    $on = fn (string $p, bool $exact = false) => ($exact ? $path === $p : ($path === $p || str_starts_with($path, $p.'/'))) ? 'on' : '';
    $initial = mb_substr($u->name, 0, 1);
@endphp
<aside class="side">
  <div class="brand"><span class="logo"><x-icon name="check"/></span>{{ __('مركز القيادة') }}</div>
  <nav class="nav">
    <div class="nav-h">{{ __('المهام والجدولة') }}</div>
    <a href="{{ url('/#dash') }}"><x-icon name="home"/> {{ __('لوحة القيادة') }}</a>
    <a href="{{ url('/#myday') }}"><x-icon name="sun"/> {{ __('يومي') }}</a>
    <a href="{{ url('/#tasks') }}"><x-icon name="check"/> {{ __('كل المهام') }}</a>
    <a href="{{ url('/#board') }}"><x-icon name="board"/> {{ __('كانبان') }}</a>
    <a href="{{ url('/#calendar') }}"><x-icon name="calendar"/> {{ __('الجدولة') }}</a>
    <a href="{{ url('/#time') }}"><x-icon name="clock"/> {{ __('الوقت والدوام') }}</a>
    <div class="nav-h">{{ __('الموارد البشرية') }}</div>
    <a href="{{ route('hr.index') }}" class="{{ $on('hr', true) }}"><x-icon name="home"/> {{ __('نظرة عامة') }}</a>
    <a href="{{ route('hr.employees.index') }}" class="{{ $on('hr/employees') }}"><x-icon name="users"/> {{ __('الموظفون') }}</a>
    <a href="{{ route('hr.org') }}" class="{{ $on('hr/org') }}"><x-icon name="sitemap"/> {{ __('الهيكل التنظيمي') }}</a>
    <a href="{{ route('hr.leave.index') }}" class="{{ $on('hr/leave') }}"><x-icon name="calendar-off"/> {{ __('الإجازات') }}</a>
    <a href="{{ route('hr.attendance.index') }}" class="{{ $on('hr/attendance') }}"><x-icon name="clock"/> {{ __('الحضور والانصراف') }}</a>
    @if($u->isHrManager())
      <a href="{{ route('hr.departments.index') }}" class="{{ $on('hr/departments') }}"><x-icon name="building"/> {{ __('الأقسام') }}</a>
      <a href="{{ route('hr.leave-types.index') }}" class="{{ $on('hr/leave-types') }}"><x-icon name="settings"/> {{ __('أنواع الإجازات') }}</a>
    @endif
    @if($u->is_admin)
      <div class="nav-h">{{ __('الإدارة') }}</div>
      <a href="{{ route('users.index') }}" class="{{ $on('users') }}"><x-icon name="lock"/> {{ __('حسابات الدخول') }}</a>
    @endif
  </nav>
  <div class="side-foot">
    <a class="me" href="{{ route('account') }}" style="color:inherit;text-decoration:none">
      <span class="av">{{ $initial }}</span><span><b>{{ $u->name }}</b><small dir="ltr">{{ $u->email }}</small></span>
    </a>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn sec sm block"><x-icon name="logout"/> {{ __('تسجيل الخروج') }}</button></form>
  </div>
</aside>
