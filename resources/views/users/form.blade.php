@extends('layouts.odoo')
@php
    $isNew = ! $user->exists;
    $self = $user->is(auth()->user());
    $has = fn ($k) => in_array($k, old('permissions', $user->permissions ?? []), true);
    $scope = old('company_scope', ($user->all_companies || $isNew) ? 'all' : 'selected');
    $picked = collect(old('company_ids', $user->exists ? $user->companies->pluck('id')->all() : []))->map(fn ($i) => (int) $i)->all();
    $isAdmin = (bool) old('is_admin', $user->is_admin);
@endphp
@section('title', ($isNew ? __('مستخدم جديد') : $user->name).' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('حسابات الدخول'), route('users.index')], [$isNew ? __('جديد') : $user->name, null]]">
  <x-slot:actions><button form="uf" class="btn">{{ __('حفظ') }}</button><a class="btn sec" href="{{ route('users.index') }}">{{ __('إلغاء') }}</a></x-slot:actions>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="o-sheet">
  <form id="uf" method="POST" action="{{ $isNew ? route('users.store') : route('users.update', $user) }}">
    @csrf @unless($isNew) @method('PUT') @endunless
    <div class="o-title"><label>{{ __('اسم المستخدم') }}</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
    <div class="o-fields">
      <div class="o-f"><label>{{ __('البريد الإلكتروني') }}</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required dir="ltr"></div>
      @if($isNew)<div class="o-f"><label>{{ __('كلمة المرور (10 أحرف فأكثر)') }}</label><input type="password" name="password" required minlength="10" autocomplete="new-password" dir="ltr"></div>@endif
    </div>

    @if($self)
      <p class="warn" style="margin-top:18px">{{ __('هذا حسابك: لا يمكنك تغيير صلاحياتك أو إيقافه بنفسك. يمكنك تعديل الاسم والبريد وتغيير كلمتك من «حسابي».') }}</p>
    @else
      <div class="o-notebook">
        <div class="o-tabs"><a class="on">{{ __('الصلاحيات') }}</a></div>
        <label class="chkrow" style="margin-bottom:14px;font-weight:600"><input type="checkbox" name="is_admin" value="1" id="is_admin" @checked($isAdmin)> {{ __('مدير النظام (كل الصلاحيات، وكل الشركات، وإدارة المستخدمين)') }}</label>

        <div id="perm-box" style="{{ $isAdmin ? 'opacity:.4;pointer-events:none' : '' }}">
          <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-bottom:10px"><span class="sub" style="margin:0">{{ __('تعبئة سريعة:') }}</span>
            @foreach(\App\Support\Permissions::PRESETS as $name => $list)
              <button type="button" class="btn sm sec" data-preset="{{ json_encode($list) }}">{{ __('types.preset.'.$name) }}</button>
            @endforeach
          </div>
          <div class="o-list tbl-wrap"><table>
            <thead><tr><th>{{ __('القسم') }}</th><th style="text-align:center">{{ __('عرض / استخدام') }}</th><th style="text-align:center">{{ __('تعديل') }}</th></tr></thead>
            <tbody>
            @foreach(\App\Support\Permissions::MATRIX as [$label, $view, $edit])
              <tr><td><b>{{ __($label) }}</b></td>
                <td style="text-align:center">@if($view)<input type="checkbox" name="permissions[]" value="{{ $view }}" data-view="{{ $view }}" @checked($has($view)) aria-label="{{ __($label) }} — {{ __('عرض') }}">@else — @endif</td>
                <td style="text-align:center">@if($edit)<input type="checkbox" name="permissions[]" value="{{ $edit }}" data-edit="{{ $edit }}" data-needs="{{ $view }}" @checked($has($edit)) aria-label="{{ __($label) }} — {{ __('تعديل') }}">@else — @endif</td></tr>
            @endforeach
            </tbody>
          </table></div>
          <p class="sub" style="margin-top:8px">{{ __('التعديل يستلزم العرض تلقائيًا. الحذف النهائي وإدارة المستخدمين لمدير النظام فقط.') }}</p>

          <h3 style="margin-top:22px">{{ __('الشركات المسموح بها') }}</h3>
          <label class="chkrow"><input type="radio" name="company_scope" value="all" @checked($scope === 'all')> {{ __('كل الشركات (الحالية والمستقبلية)') }}</label>
          <label class="chkrow" style="margin-top:6px"><input type="radio" name="company_scope" value="selected" @checked($scope === 'selected')> {{ __('شركات محددة فقط') }}</label>
          <div id="co-list" style="margin:8px 28px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;{{ $scope === 'selected' ? '' : 'opacity:.4;pointer-events:none' }}">
            @forelse($companies as $c)<label class="chkrow"><input type="checkbox" name="company_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $picked, true))> {{ $c->displayName() }}</label>
            @empty<span class="sub">{{ __('لا شركات بعد.') }}</span>@endforelse
          </div>
        </div>
      </div>
    @endif
  </form>

  @unless($isNew)
    <div class="o-notebook">
      <div class="o-tabs"><a class="on">{{ __('الحساب') }}</a></div>
      <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
        <form method="POST" action="{{ route('users.password', $user) }}" style="display:flex;gap:6px;align-items:flex-end;flex-wrap:wrap">@csrf @method('PUT')
          <label class="f" style="display:flex;flex-direction:column;font-size:13px;color:var(--mut);gap:4px">{{ __('تعيين كلمة مرور جديدة') }}<input type="password" name="password" minlength="10" required placeholder="{{ __('10 أحرف فأكثر') }}" dir="ltr" autocomplete="new-password"></label>
          <button class="btn sec">{{ __('تعيين') }}</button>
        </form>
        @unless($self)
          <form method="POST" action="{{ route('users.toggle', $user) }}">@csrf<button class="btn {{ $user->is_active ? 'red' : 'grn' }}">{{ $user->is_active ? __('إيقاف الحساب') : __('تفعيل الحساب') }}</button></form>
        @endunless
        <span class="pill {{ $user->is_active ? 'grn' : 'red' }}">{{ $user->is_active ? __('نشط') : __('موقوف') }}</span>
      </div>
    </div>
  @endunless
</div>
<script>
(function(){
  var f=document.getElementById('uf'),adm=document.getElementById('is_admin'),box=document.getElementById('perm-box');if(!adm)return;
  function setAdmin(){box.style.opacity=adm.checked?.4:1;box.style.pointerEvents=adm.checked?'none':''}
  adm.addEventListener('change',setAdmin);
  f.querySelectorAll('[data-preset]').forEach(function(b){b.addEventListener('click',function(){var l=JSON.parse(b.dataset.preset);f.querySelectorAll('input[name="permissions[]"]').forEach(function(i){i.checked=l.indexOf(i.value)>-1})})});
  f.querySelectorAll('[data-edit]').forEach(function(e){e.addEventListener('change',function(){if(e.checked&&e.dataset.needs){var v=f.querySelector('[data-view="'+e.dataset.needs+'"]');if(v)v.checked=true}})});
  f.querySelectorAll('[data-view]').forEach(function(v){v.addEventListener('change',function(){if(!v.checked){var e=f.querySelector('[data-needs="'+v.dataset.view+'"]');if(e)e.checked=false}})});
  var co=document.getElementById('co-list');
  f.querySelectorAll('input[name=company_scope]').forEach(function(r){r.addEventListener('change',function(){var sel=f.querySelector('input[name=company_scope]:checked').value==='selected';co.style.opacity=sel?1:.4;co.style.pointerEvents=sel?'':'none'})});
})();
</script>
@endsection
