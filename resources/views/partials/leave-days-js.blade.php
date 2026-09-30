<script>
document.querySelectorAll('[data-leave-form], #lf').forEach(function(f){
  var s=f.start_date,e=f.end_date,d=f.days;if(!s||!e||!d)return;
  function calc(){if(!s.value||!e.value||d.dataset.manual)return;var n=Math.round((new Date(e.value)-new Date(s.value))/864e5)+1;if(n>0)d.value=n}
  d.addEventListener('input',function(){d.dataset.manual=1});s.addEventListener('change',calc);e.addEventListener('change',calc);calc();
});
</script>
