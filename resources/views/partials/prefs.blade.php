{{-- زر اللغة (إعادة تحميل) وزر المظهر (فوري) --}}
<form method="POST" action="{{ route('preferences') }}" style="display:inline-flex">
  @csrf
  <input type="hidden" name="locale" value="{{ $uiLocale === 'ar' ? 'en' : 'ar' }}">
  <button class="ibtn" title="{{ __('تغيير اللغة') }}"><x-icon name="globe"/> <span class="nm">{{ $uiLocale === 'ar' ? 'English' : 'العربية' }}</span></button>
</form>
<button type="button" class="ibtn" onclick="markazToggleTheme()" title="{{ __('الوضع الفاتح/الداكن') }}" aria-label="{{ __('الوضع الفاتح/الداكن') }}"><span class="ic-sun"><x-icon name="sun"/></span><span class="ic-moon"><x-icon name="moon"/></span></button>
