/* مركز القيادة — منظومة مهام وجدولة وتتبع وقت. بدون خادم؛ البيانات في المتصفح مع تصدير/استيراد. */
'use strict';

// ---------- ثوابت ----------
const STATUS = { todo: 'للتنفيذ', doing: 'قيد العمل', review: 'مراجعة/انتظار', done: 'مكتملة' };
const PRIO = { low: 'منخفضة', med: 'متوسطة', high: 'عالية', urgent: 'عاجلة' };
const ROLES = { office: 'إدارة المكتب', accounts: 'الحسابات', exec: 'مساعدة المدير', personal: 'شخصي' };
const REPEAT = { none: 'بدون تكرار', daily: 'يومي', weekly: 'أسبوعي', monthly: 'شهري', yearly: 'سنوي' };
const DAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
const MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
// قوالب جدولة دورية شائعة — التواريخ اقتراحات قابلة للتعديل، تحقق من الموعد الرسمي لكل جهة.
const TEMPLATES = [
  { title: 'مطابقة الحساب البنكي مع الدفاتر', role: 'accounts', repeat: 'monthly', day: 3, prio: 'high' },
  { title: 'إقرار ضريبة القيمة المضافة (VAT)', role: 'accounts', repeat: 'monthly', day: 25, prio: 'urgent', note: 'تحقق من موعد هيئة الزكاة والضريبة والجمارك لفترتك (شهري/ربع سنوي).' },
  { title: 'إعداد ومراجعة مسير الرواتب', role: 'accounts', repeat: 'monthly', day: 25, prio: 'high' },
  { title: 'سداد اشتراكات التأمينات الاجتماعية (GOSI)', role: 'accounts', repeat: 'monthly', day: 12, prio: 'high', note: 'تحقق من الموعد النظامي الحالي.' },
  { title: 'متابعة الفواتير المستحقة والتحصيل', role: 'accounts', repeat: 'weekly', day: 0, prio: 'high' },
  { title: 'جدولة مدفوعات الموردين', role: 'accounts', repeat: 'weekly', day: 2, prio: 'med' },
  { title: 'تقرير الإدارة الشهري (CFO Report)', role: 'exec', repeat: 'monthly', day: 7, prio: 'high' },
  { title: 'دفع إيجار المكتب', role: 'office', repeat: 'monthly', day: 1, prio: 'med' },
  { title: 'فواتير الكهرباء والاتصالات والإنترنت', role: 'office', repeat: 'monthly', day: 5, prio: 'med' },
  { title: 'مراجعة تجديد الرخص والإقامات والعقود', role: 'office', repeat: 'monthly', day: 10, prio: 'med' },
  { title: 'جرد المستلزمات المكتبية', role: 'office', repeat: 'monthly', day: 28, prio: 'low' },
  { title: 'تجهيز أجندة اجتماع الأسبوع وإرسالها للمدير', role: 'exec', repeat: 'weekly', day: 0, prio: 'high' },
  { title: 'مراجعة جدول المدير للأسبوع القادم', role: 'exec', repeat: 'weekly', day: 4, prio: 'med' },
  { title: 'الإقرار السنوي للزكاة وإعداد القوائم', role: 'accounts', repeat: 'yearly', day: 1, prio: 'urgent', note: 'تحقق من الموعد النظامي (بعد نهاية السنة المالية).' },
  { title: 'النسخ الاحتياطي للملفات والأنظمة', role: 'office', repeat: 'weekly', day: 4, prio: 'low' },
];

// ---------- أدوات ----------
const $ = (s, r = document) => r.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const uid = () => Math.random().toString(36).slice(2, 10) + Date.now().toString(36).slice(-4);
const pad = n => String(n).padStart(2, '0');
const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const parse = s => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
const today = () => fmt(new Date());
const addDays = (s, n) => { const d = parse(s); d.setDate(d.getDate() + n); return fmt(d); };
const addMonths = (s, n) => {
  const d = parse(s), day = d.getDate();
  d.setDate(1); d.setMonth(d.getMonth() + n);
  const last = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
  d.setDate(Math.min(day, last)); return fmt(d);
};
const nextDate = (s, rep) => rep === 'daily' ? addDays(s, 1) : rep === 'weekly' ? addDays(s, 7) : rep === 'monthly' ? addMonths(s, 1) : rep === 'yearly' ? addMonths(s, 12) : null;
const dur = ms => { const s = Math.max(0, Math.floor(ms / 1000)); return `${pad(Math.floor(s / 3600))}:${pad(Math.floor(s / 60) % 60)}:${pad(s % 60)}`; };
const hrs = ms => (ms / 3600000).toFixed(1);
const dateLabel = s => { const d = parse(s), t = today(); return s === t ? 'اليوم' : s === addDays(t, 1) ? 'غدًا' : s === addDays(t, -1) ? 'أمس' : `${DAYS[d.getDay()]} ${d.getDate()} ${MONTHS[d.getMonth()]}`; };
const hijri = d => { try { return new Intl.DateTimeFormat('ar-SA-u-ca-islamic-umalqura', { dateStyle: 'long' }).format(d); } catch { return ''; } };
function toast(m) { const t = $('#toast'); t.textContent = m; t.style.display = 'block'; clearTimeout(toast.h); toast.h = setTimeout(() => t.style.display = 'none', 2200); }

// ---------- الحالة والتخزين ----------
const KEY = 'markaz-alqiyada-v1';
let S;
function seed() {
  const t = today();
  const mk = (o) => Object.assign({ id: uid(), title: '', notes: '', status: 'todo', priority: 'med', due: '', time: '', role: 'office', project: '', assignee: '', tags: [], subtasks: [], repeat: 'none', myDay: false, important: false, estimate: 0, remind: 15, created: Date.now(), doneAt: null }, o);
  return {
    v: 1, tasks: [
      mk({ title: 'مثال: إرسال تقرير المبيعات للمدير', due: t, time: '11:00', role: 'exec', priority: 'high', myDay: true, project: 'تقارير الإدارة' }),
      mk({ title: 'مثال: مطابقة كشف البنك', due: addDays(t, 1), role: 'accounts', status: 'doing', project: 'الإقفال الشهري' }),
      mk({ title: 'مثال: تجديد عقد الصيانة', due: addDays(t, 5), role: 'office', priority: 'low' }),
    ], events: [], projects: ['تقارير الإدارة', 'الإقفال الشهري', 'عمليات المكتب'], people: [], logs: [], attendance: [], timer: null, notified: {}, theme: 'auto',
  };
}
const fix = x => { x.tasks ||= []; x.people ||= []; x.notified ||= {}; x.logs ||= []; x.attendance ||= []; x.events ||= []; x.projects ||= []; x.theme ||= 'auto'; return x; };

// ---------- الحساب والمزامنة (الخادم) ----------
const USER = window.MARKAZ_USER || null; // {id,name,email,admin} — يحقنه الخادم بعد تسجيل الدخول
const skey = () => USER ? `${KEY}:${USER.id}` : KEY;
const readLocal = k => { try { return JSON.parse(localStorage.getItem(k)); } catch { return null; } };
const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
let pushing = false, dirty = false;
function setSync(t) { setSync.last = t; const e = $('#sync'); if (e) e.textContent = t; }
function load() { S = fix(readLocal(skey()) || seed()); }
function save() {
  S.ts = Date.now();
  try { localStorage.setItem(skey(), JSON.stringify(S)); } catch { toast('تعذر الحفظ المحلي: مساحة التخزين ممتلئة أو محظورة'); }
  if (USER) { dirty = true; setSync('…جارٍ الحفظ'); clearTimeout(save.h); save.h = setTimeout(push, 1000); }
}
async function api(method, body) {
  const r = await fetch('/api/state', { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: body ? JSON.stringify(body) : undefined });
  if (r.status === 401 || r.status === 419) { location.href = '/login'; throw new Error('auth'); }
  return r;
}
const adopt = (remote, ts) => { S = fix(remote); S.ts = ts; try { localStorage.setItem(skey(), JSON.stringify(S)); } catch { } };
async function push() {
  if (!USER || !S || pushing) { if (pushing) dirty = true; return; }
  pushing = true; dirty = false;
  try {
    const r = await api('PUT', { data: S, ts: S.ts });
    if (r.status === 409) { const j = await r.json(); adopt(j.data, j.ts); render(); setSync('✓ حُدّثت من جهاز آخر'); }
    else if (!r.ok) setSync('⚠ تعذرت المزامنة (محفوظ على الجهاز)');
    else setSync('✓ تمت المزامنة ' + new Date().toLocaleTimeString('ar-SA', { timeStyle: 'short' }));
  } catch (e) { if (e.message !== 'auth') setSync('⚠ لا اتصال — محفوظ على الجهاز وسيُزامَن لاحقًا'); }
  pushing = false;
  if (dirty) setTimeout(push, 500);
}
async function pull(initial) {
  try {
    const r = await api('GET'); if (!r.ok) throw new Error('bad');
    const { data, ts } = await r.json();
    if (data && ts > (S.ts || 0)) {
      if (!initial && $('#dlg').open) return; // لا نقاطع نموذجًا مفتوحًا
      adopt(data, ts); render(); setSync('✓ تمت المزامنة');
    } else if (!data || ts < (S.ts || 0)) { S.ts ||= Date.now(); push(); }
    else setSync('✓ تمت المزامنة');
  } catch (e) { if (e.message !== 'auth') setSync('⚠ لا اتصال — يعمل من نسخة الجهاز'); }
}
function boot() {
  load(); render();
  if (!USER) return;
  pull(true);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) pull(false); });
  setInterval(() => { if (!document.hidden && !pushing && !dirty) pull(false); }, 60000);
}

const ui = { view: 'dash', q: '', role: '', status: '', prio: '', project: '', assignee: '', calMonth: today().slice(0, 7), calSel: today(), repWeek: 0 };

// ---------- منطق المهام ----------
const byId = id => S.tasks.find(t => t.id === id);
const isOver = t => t.status !== 'done' && t.due && (t.due < today() || (t.due === today() && t.time && t.time < new Date().toTimeString().slice(0, 5)));
function setStatus(t, st) {
  const was = t.status; t.status = st;
  if (st === 'done' && was !== 'done') {
    t.doneAt = Date.now();
    if (t.repeat !== 'none') {
      const base = t.due || today(); let nd = nextDate(base, t.repeat);
      while (nd < today()) nd = nextDate(nd, t.repeat); // تخطَّ الفترات الفائتة
      S.tasks.push({ ...t, id: uid(), status: 'todo', due: nd, doneAt: null, created: Date.now(), myDay: false, subtasks: t.subtasks.map(s => ({ ...s, d: false })) });
      toast(`تم الإنجاز — أُنشئت المهمة التالية ${dateLabel(nd)}`);
    }
    if (S.timer && S.timer.taskId === t.id) stopTimer();
  } else if (st !== 'done') t.doneAt = null;
}
function quickAdd(text) {
  let due = '', prio = 'med', title = text.trim();
  const rules = [[/\bبعد غد\b/, 2], [/\bغدا\b|\bغدًا\b/, 1], [/\bاليوم\b/, 0]];
  for (const [re, n] of rules) if (re.test(title)) { due = addDays(today(), n); title = title.replace(re, '').trim(); break; }
  if (/(^|\s)(!|عاجل)(\s|$)/.test(title)) { prio = 'urgent'; title = title.replace(/(^|\s)(!|عاجل)(?=\s|$)/, ' ').trim(); }
  if (!title) return;
  const role = ui.role || 'office';
  S.tasks.push(newTask({ title, due, priority: prio, role, myDay: due === today() }));
  save(); render(); toast('أُضيفت المهمة');
}
function newTask(o = {}) { return Object.assign({ id: uid(), title: '', notes: '', status: 'todo', priority: 'med', due: '', time: '', role: 'office', project: '', assignee: '', tags: [], subtasks: [], repeat: 'none', myDay: false, important: false, estimate: 0, remind: 15, created: Date.now(), doneAt: null }, o); }

// ---------- تتبع الوقت والدوام ----------
function startTimer(taskId) { if (S.timer) stopTimer(); S.timer = { taskId, start: Date.now() }; save(); render(); toast('بدأ التتبع'); }
function stopTimer() {
  if (!S.timer) return; const { taskId, start } = S.timer, end = Date.now(); S.timer = null;
  if (end - start >= 1000) S.logs.push({ id: uid(), taskId, start, end });
  save(); render();
}
const clockedIn = () => S.attendance.length && !S.attendance[S.attendance.length - 1].out;
function toggleClock() { if (clockedIn()) S.attendance[S.attendance.length - 1].out = Date.now(); else S.attendance.push({ id: uid(), in: Date.now(), out: null }); save(); render(); }
const taskMs = id => S.logs.filter(l => l.taskId === id).reduce((a, l) => a + l.end - l.start, 0);
const startOfDay = d => { const x = new Date(d); x.setHours(0, 0, 0, 0); return x.getTime(); };
function weekRange(off = 0) { const d = new Date(); d.setDate(d.getDate() - d.getDay() + off * 7); const s = startOfDay(d); return [s, s + 7 * 864e5]; }
const logsIn = (a, b) => S.logs.filter(l => l.start >= a && l.start < b);
const attMs = (a, b) => S.attendance.filter(x => x.in >= a && x.in < b).reduce((s, x) => s + (x.out || Date.now()) - x.in, 0);

// ---------- مكونات العرض ----------
function taskRow(t) {
  const over = isOver(t), run = S.timer && S.timer.taskId === t.id;
  const sd = t.subtasks.length ? `${t.subtasks.filter(s => s.d).length}/${t.subtasks.length}` : '';
  return `<div class="row ${t.status === 'done' ? 'done' : ''}" data-act="edit" data-id="${t.id}">
    <button class="chk" data-act="toggle" data-id="${t.id}" title="إنجاز"></button>
    <div class="ttl">${esc(t.title)}<div class="meta">
      ${t.due ? `<span class="tag ${over ? 'over' : ''}">📅 ${dateLabel(t.due)}${t.time ? ' ' + esc(t.time) : ''}</span>` : ''}
      <span class="tag">${ROLES[t.role]}</span>
      ${t.priority === 'urgent' || t.priority === 'high' ? `<span class="tag p-${t.priority}">${PRIO[t.priority]}</span>` : ''}
      ${t.project ? `<span class="tag">📁 ${esc(t.project)}</span>` : ''}
      ${t.assignee ? `<span class="tag">👤 ${esc(t.assignee)}</span>` : ''}
      ${t.repeat !== 'none' ? `<span class="tag">🔁 ${REPEAT[t.repeat]}</span>` : ''}
      ${sd ? `<span class="tag">☑ ${sd}</span>` : ''}
      ${taskMs(t.id) ? `<span class="tag">⏱ ${hrs(taskMs(t.id))} س</span>` : ''}
    </div></div>
    ${t.status !== 'done' ? `<button class="btn sm sec" data-act="${run ? 'stop' : 'start'}" data-id="${t.id}">${run ? '⏹ إيقاف' : '▶ بدء'}</button>` : ''}
    <button class="star ${t.important ? 'on' : ''}" data-act="star" data-id="${t.id}" title="مهم">★</button></div>`;
}
const list = (arr, empty = 'لا توجد مهام') => arr.length ? arr.map(taskRow).join('') : `<div class="empty">${empty}</div>`;
const open = () => S.tasks.filter(t => t.status !== 'done');
const sortT = (a, b) => (a.due || '9') .localeCompare(b.due || '9') || (a.time || '').localeCompare(b.time || '');
const pr = { urgent: 0, high: 1, med: 2, low: 3 };

function filtered() {
  const q = ui.q.trim().toLowerCase();
  return S.tasks.filter(t => (!q || (t.title + t.notes + t.project + t.assignee + t.tags.join(' ')).toLowerCase().includes(q))
    && (!ui.role || t.role === ui.role) && (!ui.status || t.status === ui.status) && (!ui.prio || t.priority === ui.prio)
    && (!ui.project || t.project === ui.project) && (!ui.assignee || t.assignee === ui.assignee));
}
function filterBar(withStatus = true) {
  const sel = (k, o, ph) => `<select data-f="${k}"><option value="">${ph}</option>${Object.entries(o).map(([v, l]) => `<option value="${v}" ${ui[k] === v ? 'selected' : ''}>${l}</option>`).join('')}</select>`;
  const arr = (k, a, ph) => `<select data-f="${k}"><option value="">${ph}</option>${a.map(v => `<option ${ui[k] === v ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select>`;
  return `<div class="filters"><input type="search" placeholder="بحث…" value="${esc(ui.q)}" data-f="q">${sel('role', ROLES, 'كل المجالات')}${withStatus ? sel('status', STATUS, 'كل الحالات') : ''}${sel('prio', PRIO, 'كل الأولويات')}${arr('project', S.projects, 'كل المشاريع')}${arr('assignee', S.people, 'كل المسؤولين')}</div>`;
}

// ---------- الشاشات ----------
const V = {};
V.dash = () => {
  const t = today(), o = open(), od = o.filter(isOver), td = o.filter(x => x.due === t), wk = o.filter(x => x.due > t && x.due <= addDays(t, 7)).sort(sortT);
  const [a, b] = weekRange(), done7 = S.tasks.filter(x => x.doneAt && x.doneAt >= Date.now() - 7 * 864e5).length;
  const evs = S.events.filter(e => e.date >= t).sort((x, y) => (x.date + x.time).localeCompare(y.date + y.time)).slice(0, 5);
  return `<h2>لوحة القيادة</h2><div class="grid g4">
    <div class="card kpi"><b>${td.length}</b><span>مهام اليوم</span></div>
    <div class="card kpi"><b style="color:var(--red)">${od.length}</b><span>متأخرة</span></div>
    <div class="card kpi"><b>${wk.length}</b><span>خلال 7 أيام</span></div>
    <div class="card kpi"><b>${done7}</b><span>أُنجزت آخر 7 أيام</span></div>
    <div class="card kpi"><b>${hrs(attMs(a, b))}</b><span>ساعات الدوام هذا الأسبوع</span></div></div><br>
    <div class="grid g2">
    <div class="card"><h3>⚠ متأخرة</h3>${list(od.sort(sortT), 'لا توجد مهام متأخرة 👏')}</div>
    <div class="card"><h3>📌 اليوم</h3>${list(td.sort((x, y) => pr[x.priority] - pr[y.priority]), 'لا مهام مجدولة اليوم')}</div>
    <div class="card"><h3>🗓 القادم</h3>${list(wk.slice(0, 8), 'لا شيء خلال 7 أيام')}</div>
    <div class="card"><h3>🎯 مواعيد قادمة</h3>${evs.length ? evs.map(e => `<div class="row" data-act="event" data-id="${e.id}">${dateLabel(e.date)} ${esc(e.time)} — ${esc(e.title)}</div>`).join('') : '<div class="empty">لا مواعيد</div>'}</div></div>`;
};
V.myday = () => {
  const t = today(), items = open().filter(x => x.myDay || x.due === t).sort((x, y) => pr[x.priority] - pr[y.priority]);
  const est = items.reduce((s, x) => s + (+x.estimate || 0), 0);
  return `<h2>يومي</h2><p class="date">${DAYS[new Date().getDay()]} — ${hijri(new Date())}${est ? ` — الوقت المقدَّر: ${(est / 60).toFixed(1)} ساعة` : ''}</p><div class="card">${list(items, 'يومك فارغ. أضف مهمة أو اختر «إضافة ليومي» داخل أي مهمة.')}</div>`;
};
V.tasks = () => {
  const arr = filtered().sort((x, y) => (x.status === 'done') - (y.status === 'done') || sortT(x, y));
  return `<h2>كل المهام</h2>${filterBar()}<div class="card">${list(arr)}</div>`;
};
V.important = () => `<h2>المميّزة</h2><div class="card">${list(open().filter(t => t.important).sort(sortT), 'لا مهام مميّزة بنجمة')}</div>`;
V.board = () => {
  const arr = filtered();
  return `<h2>لوحة كانبان</h2>${filterBar(false)}<div class="board">${Object.entries(STATUS).map(([k, l]) => {
    const c = arr.filter(t => t.status === k).sort((x, y) => pr[x.priority] - pr[y.priority] || sortT(x, y));
    return `<div class="col" data-drop="${k}"><h3>${l}<span class="tag">${c.length}</span></h3>${c.map(t => `<div class="kc tp-${t.priority}" draggable="true" data-drag="${t.id}" data-act="edit" data-id="${t.id}">
      <div>${esc(t.title)}</div><div class="meta">${t.due ? `<span class="tag ${isOver(t) ? 'over' : ''}">${dateLabel(t.due)}</span>` : ''}<span class="tag">${ROLES[t.role]}</span>${t.assignee ? `<span class="tag">👤 ${esc(t.assignee)}</span>` : ''}</div></div>`).join('')}
      <button class="btn sm sec" data-act="addto" data-st="${k}">+ مهمة</button></div>`;
  }).join('')}</div>`;
};
V.calendar = () => {
  const [y, m] = ui.calMonth.split('-').map(Number), first = new Date(y, m - 1, 1), start = new Date(y, m - 1, 1 - first.getDay());
  let cells = '';
  for (let i = 0; i < 42; i++) {
    const d = new Date(start); d.setDate(start.getDate() + i); const s = fmt(d);
    const ts = S.tasks.filter(t => t.due === s), es = S.events.filter(e => e.date === s);
    cells += `<div class="d ${d.getMonth() !== m - 1 ? 'off' : ''} ${s === today() ? 'today' : ''}" data-act="day" data-date="${s}"><div class="n"><span>${d.getDate()}</span></div>
      ${es.slice(0, 2).map(e => `<div class="ev">${esc(e.time)} ${esc(e.title)}</div>`).join('')}
      ${ts.slice(0, 3).map(t => `<div class="ev t ${t.status === 'done' ? 'dn' : ''}">${esc(t.title)}</div>`).join('')}
      ${ts.length + es.length > 5 ? `<div class="n">+${ts.length + es.length - 5}</div>` : ''}</div>`;
  }
  const sel = ui.calSel, dayT = S.tasks.filter(t => t.due === sel).sort(sortT), dayE = S.events.filter(e => e.date === sel).sort((a, b) => a.time.localeCompare(b.time));
  return `<h2>الجدولة والتقويم</h2><div class="top"><button class="btn sec sm" data-act="calprev">→</button><b>${MONTHS[m - 1]} ${y}</b><button class="btn sec sm" data-act="calnext">←</button><button class="btn sec sm" data-act="caltoday">اليوم</button><button class="btn sm" data-act="event">+ موعد</button></div>
  <div class="cal">${DAYS.map(d => `<div class="h">${d}</div>`).join('')}${cells}</div><br>
  <div class="card"><h3>${dateLabel(sel)} — ${hijri(parse(sel))}</h3>
  ${dayE.map(e => `<div class="row" data-act="event" data-id="${e.id}">🎯 ${esc(e.time)} ${esc(e.title)}${e.place ? ' — ' + esc(e.place) : ''}</div>`).join('')}${list(dayT, dayE.length ? '' : 'لا شيء في هذا اليوم')}
  <button class="btn sm" data-act="new" data-date="${sel}">+ مهمة في هذا اليوم</button></div>`;
};
V.time = () => {
  const [a, b] = weekRange(ui.repWeek), wl = logsIn(a, b), run = S.timer, tk = run && byId(run.taskId);
  const byP = {}; wl.forEach(l => { const t = byId(l.taskId), k = t?.project || 'بدون مشروع'; byP[k] = (byP[k] || 0) + l.end - l.start; });
  const tot = Object.values(byP).reduce((x, y) => x + y, 0), att = S.attendance.filter(x => x.in >= a && x.in < b);
  return `<h2>تتبع الوقت والدوام</h2><div class="grid g2">
  <div class="card"><h3>الدوام</h3><p>${clockedIn() ? 'أنت على رأس العمل منذ ' + new Date(S.attendance.at(-1).in).toLocaleTimeString('ar-SA', { timeStyle: 'short' }) : 'لم تسجّل حضورك'}</p><button class="btn ${clockedIn() ? 'red' : ''}" data-act="clock">${clockedIn() ? '⏹ تسجيل انصراف' : '▶ تسجيل حضور'}</button></div>
  <div class="card"><h3>مؤقّت المهمة</h3>${run ? `<div class="timer" id="tmr">${dur(Date.now() - run.start)}</div><p>${esc(tk?.title || '—')}</p><button class="btn red" data-act="stop">⏹ إيقاف وحفظ</button>` : '<p class="empty">اضغط ▶ بدء بجانب أي مهمة</p>'}</div></div><br>
  <div class="card"><div class="top"><button class="btn sec sm" data-act="wprev">→ أسبوع أسبق</button><b>${new Date(a).toLocaleDateString('ar-SA', { day: 'numeric', month: 'long' })} — الأسبوع</b><button class="btn sec sm" data-act="wnext">أحدث ←</button></div>
  <p>إجمالي الدوام: <b>${hrs(attMs(a, b))} س</b> — وقت المهام المتتبَّع: <b>${hrs(tot)} س</b></p>
  ${Object.entries(byP).map(([k, v]) => `<div>${esc(k)} — ${hrs(v)} س<div class="bar"><i style="width:${tot ? v / tot * 100 : 0}%"></i></div></div>`).join('') || '<div class="empty">لا سجلات هذا الأسبوع</div>'}</div><br>
  <div class="card"><h3>سجل الدوام</h3><table><tr><th>اليوم</th><th>حضور</th><th>انصراف</th><th>المدة</th></tr>${att.slice().reverse().map(x => `<tr><td>${dateLabel(fmt(new Date(x.in)))}</td><td>${new Date(x.in).toLocaleTimeString('ar-SA', { timeStyle: 'short' })}</td><td>${x.out ? new Date(x.out).toLocaleTimeString('ar-SA', { timeStyle: 'short' }) : '—'}</td><td>${hrs((x.out || Date.now()) - x.in)} س</td></tr>`).join('') || '<tr><td colspan=4 class="empty">—</td></tr>'}</table></div>`;
};
V.templates = () => `<h2>قوالب الجدولة الدورية</h2><p class="date">مهام متكررة شائعة لمدير المكتب والحسابات ومساعد المدير. المواعيد المقترحة قابلة للتعديل — تحقق من المواعيد النظامية الرسمية (الزكاة والضريبة، التأمينات) قبل الاعتماد.</p>
  <div class="card">${TEMPLATES.map((t, i) => `<div class="row" style="cursor:default"><div class="ttl">${esc(t.title)}<div class="meta"><span class="tag">${ROLES[t.role]}</span><span class="tag">🔁 ${REPEAT[t.repeat]}</span><span class="tag">${t.repeat === 'weekly' ? 'يوم: ' + DAYS[t.day] : t.repeat === 'yearly' ? 'سنوي' : 'يوم ' + t.day + ' من الشهر'}</span></div></div><button class="btn sm" data-act="tpl" data-i="${i}">+ أضف للجدول</button></div>`).join('')}</div><br>
  <button class="btn" data-act="tplall">إضافة كل القوالب دفعة واحدة</button>`;
V.reports = () => {
  const all = S.tasks, dn = all.filter(t => t.status === 'done'), op = open();
  const rate = all.length ? Math.round(dn.length / all.length * 100) : 0;
  const cnt = (o, fn) => Object.entries(o).map(([k, l]) => { const n = all.filter(t => fn(t) === k).length; return `<div>${l} — ${n}<div class="bar"><i style="width:${all.length ? n / all.length * 100 : 0}%"></i></div></div>`; }).join('');
  const days = [...Array(7)].map((_, i) => { const d = addDays(today(), i - 6), n = all.filter(t => t.doneAt && fmt(new Date(t.doneAt)) === d).length; return [d, n]; }), mx = Math.max(1, ...days.map(x => x[1]));
  const ppl = {}; op.forEach(t => { const k = t.assignee || 'غير مُسند'; ppl[k] = (ppl[k] || 0) + 1; });
  return `<h2>التقارير</h2><div class="grid g4"><div class="card kpi"><b>${rate}%</b><span>نسبة الإنجاز</span></div><div class="card kpi"><b>${op.length}</b><span>مفتوحة</span></div><div class="card kpi"><b style="color:var(--red)">${op.filter(isOver).length}</b><span>متأخرة</span></div><div class="card kpi"><b>${hrs(S.logs.reduce((s, l) => s + l.end - l.start, 0))}</b><span>ساعات متتبَّعة</span></div></div><br>
  <div class="grid g2"><div class="card"><h3>حسب المجال</h3>${cnt(ROLES, t => t.role)}</div><div class="card"><h3>حسب الحالة</h3>${cnt(STATUS, t => t.status)}</div>
  <div class="card"><h3>المنجَز آخر 7 أيام</h3>${days.map(([d, n]) => `<div>${dateLabel(d)} — ${n}<div class="bar"><i style="width:${n / mx * 100}%"></i></div></div>`).join('')}</div>
  <div class="card"><h3>المهام المفتوحة لكل مسؤول</h3>${Object.entries(ppl).map(([k, n]) => `<div>${esc(k)} — ${n}<div class="bar"><i style="width:${n / op.length * 100}%"></i></div></div>`).join('') || '<div class="empty">—</div>'}</div></div><br>
  <button class="btn sec" onclick="window.print()">🖨 طباعة / PDF</button>`;
};
V.settings = () => `<h2>الإعدادات والبيانات</h2><div class="grid g2">
  <div class="card"><h3>المشاريع</h3>${S.projects.map((p, i) => `<div class="row" style="cursor:default">${esc(p)}<button class="btn sm sec" style="margin-inline-start:auto" data-act="delproj" data-i="${i}">حذف</button></div>`).join('')}<div class="filters"><input id="np" placeholder="مشروع جديد"><button class="btn sm" data-act="addproj">إضافة</button></div></div>
  <div class="card"><h3>الفريق / المسؤولون</h3>${S.people.map((p, i) => `<div class="row" style="cursor:default">👤 ${esc(p)}<button class="btn sm sec" style="margin-inline-start:auto" data-act="delperson" data-i="${i}">حذف</button></div>`).join('')}<div class="filters"><input id="npp" placeholder="اسم شخص"><button class="btn sm" data-act="addperson">إضافة</button></div></div>
  <div class="card"><h3>المظهر والتنبيهات</h3><div class="filters"><select data-f="theme"><option value="auto">تلقائي</option><option value="light" ${S.theme === 'light' ? 'selected' : ''}>فاتح</option><option value="dark" ${S.theme === 'dark' ? 'selected' : ''}>داكن</option></select><button class="btn sm sec" data-act="notif">تفعيل تنبيهات المتصفح</button></div><p class="date">التنبيهات تعمل أثناء فتح الصفحة.</p></div>
  <div class="card"><h3>النسخ الاحتياطي</h3><p class="date">البيانات محفوظة في هذا المتصفح فقط. صدِّر نسخة بانتظام لنقلها لجهاز آخر.</p><div class="filters"><button class="btn sm" data-act="export">تصدير JSON</button><button class="btn sm sec" data-act="import">استيراد</button><button class="btn sm sec" data-act="csv">تصدير المهام CSV</button><button class="btn sm red" data-act="reset">مسح كل البيانات</button></div><input type="file" id="imp" accept=".json" hidden></div></div>`;

const NAV = [['dash', '🏠', 'لوحة القيادة'], ['myday', '☀', 'يومي'], ['tasks', '✅', 'كل المهام'], ['important', '★', 'المميّزة'], ['board', '🗂', 'كانبان'], ['calendar', '📅', 'الجدولة'], ['time', '⏱', 'الوقت والدوام'], ['templates', '🔁', 'قوالب دورية'], ['reports', '📊', 'التقارير'], ['settings', '⚙', 'الإعدادات']];
function render() {
  const o = open(), counts = { myday: o.filter(x => x.myDay || x.due === today()).length, tasks: o.length, important: o.filter(x => x.important).length, dash: o.filter(isOver).length };
  document.documentElement.dataset.theme = S.theme === 'auto' ? (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light') : S.theme;
  const keep = document.activeElement?.dataset?.f, pos = document.activeElement?.selectionStart;
  $('#app').innerHTML = `<aside><h1>🧭 مركز القيادة</h1><nav class="nav">${NAV.map(([k, i, l]) => `<button class="${ui.view === k ? 'on' : ''}" data-nav="${k}">${i} ${l}${counts[k] ? `<span class="cnt">${counts[k]}</span>` : ''}</button>`).join('')}</nav>${USER ? `<div class="date" style="margin:14px 8px 0;word-break:break-all">👤 ${esc(USER.name)}<br><span dir="ltr">${esc(USER.email)}</span><div id="sync">${esc(setSync.last || '')}</div><div style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap"><a class="btn sm sec" href="/account">حسابي</a>${USER.admin ? '<a class="btn sm sec" href="/users">المستخدمون</a>' : ''}<button class="btn sm sec" data-act="logout">خروج</button></div></div>` : ''}</aside>
  <main><div class="top"><input class="q" id="qa" placeholder="أضف مهمة سريعة… (مثال: اتصال بالمورد غدا !)"><button class="btn" data-act="new">+ مهمة</button><span class="date">${DAYS[new Date().getDay()]} ${new Date().getDate()} ${MONTHS[new Date().getMonth()]} · ${hijri(new Date())}</span></div>${V[ui.view]()}</main>`;
  if (keep) { const el = $(`[data-f="${keep}"]`); if (el) { el.focus(); try { el.setSelectionRange(pos, pos); } catch { } } }
}

// ---------- نماذج ----------
function taskForm(t, isNew) {
  const dl = $('#dlg'), opt = (o, v) => Object.entries(o).map(([k, l]) => `<option value="${k}" ${v === k ? 'selected' : ''}>${l}</option>`).join('');
  const lst = (a, v) => `<option value=""></option>${a.map(x => `<option ${x === v ? 'selected' : ''}>${esc(x)}</option>`).join('')}`;
  dl.innerHTML = `<form method="dialog" id="tf"><h3>${isNew ? 'مهمة جديدة' : 'تعديل المهمة'}</h3><div class="f">
    <label class="w">العنوان<input name="title" required value="${esc(t.title)}"></label>
    <label>الحالة<select name="status">${opt(STATUS, t.status)}</select></label><label>الأولوية<select name="priority">${opt(PRIO, t.priority)}</select></label>
    <label>تاريخ الاستحقاق<input type="date" name="due" value="${t.due}"></label><label>الوقت<input type="time" name="time" value="${t.time}"></label>
    <label>المجال<select name="role">${opt(ROLES, t.role)}</select></label><label>التكرار<select name="repeat">${opt(REPEAT, t.repeat)}</select></label>
    <label>المشروع<select name="project">${lst(S.projects, t.project)}</select></label><label>المسؤول<input name="assignee" list="ppl" value="${esc(t.assignee)}"><datalist id="ppl">${S.people.map(p => `<option>${esc(p)}</option>`).join('')}</datalist></label>
    <label>تنبيه قبل (دقيقة)<input type="number" name="remind" min="0" value="${t.remind}"></label><label>الوقت المقدَّر (دقيقة)<input type="number" name="estimate" min="0" value="${t.estimate}"></label>
    <label class="w">الوسوم (مفصولة بفاصلة)<input name="tags" value="${esc(t.tags.join(', '))}"></label>
    <label class="w">ملاحظات<textarea name="notes" rows="3">${esc(t.notes)}</textarea></label>
    <div class="w"><b style="font-size:13px;color:var(--mut)">المهام الفرعية</b><div id="subs">${t.subtasks.map(s => subRow(s)).join('')}</div><button type="button" class="btn sm sec" id="addsub">+ خطوة</button></div>
    <label class="w" style="flex-direction:row;align-items:center;gap:6px"><input type="checkbox" name="myDay" ${t.myDay ? 'checked' : ''}> إضافة ليومي</label></div>
    <div class="top" style="margin:14px 0 0"><button class="btn" value="ok">حفظ</button><button class="btn sec" value="cancel" formnovalidate>إلغاء</button>${isNew ? '' : '<button type="button" class="btn red" id="deltask" style="margin-inline-start:auto">حذف</button>'}</div></form>`;
  dl.showModal();
  $('#addsub').onclick = () => $('#subs').insertAdjacentHTML('beforeend', subRow({ t: '', d: false }));
  dl.onclick = e => { if (e.target.classList.contains('rmsub')) e.target.parentElement.remove(); };
  if (!isNew) $('#deltask').onclick = () => { if (confirm('حذف المهمة نهائيًا؟')) { S.tasks = S.tasks.filter(x => x.id !== t.id); save(); dl.close(); render(); } };
  $('#tf').onsubmit = e => {
    if (e.submitter?.value !== 'ok') return;
    const f = new FormData(e.target), g = k => f.get(k);
    Object.assign(t, { title: g('title').trim(), priority: g('priority'), due: g('due'), time: g('time'), role: g('role'), repeat: g('repeat'), project: g('project'), assignee: g('assignee').trim(), remind: +g('remind') || 0, estimate: +g('estimate') || 0, notes: g('notes'), myDay: f.has('myDay'), tags: g('tags').split(/[,،]/).map(x => x.trim()).filter(Boolean) });
    t.subtasks = [...dl.querySelectorAll('.sub')].map(r => ({ t: r.querySelector('[type=text]').value.trim(), d: r.querySelector('[type=checkbox]').checked })).filter(s => s.t);
    if (t.assignee && !S.people.includes(t.assignee)) S.people.push(t.assignee);
    delete S.notified[t.id];
    if (isNew) S.tasks.push(t);
    setStatus(t, g('status')); save(); render();
  };
}
const subRow = s => `<div class="sub"><input type="checkbox" ${s.d ? 'checked' : ''}><input type="text" value="${esc(s.t)}" placeholder="خطوة"><button type="button" class="btn sm sec rmsub">✕</button></div>`;
function eventForm(ev, isNew) {
  const dl = $('#dlg');
  dl.innerHTML = `<form method="dialog" id="ef"><h3>${isNew ? 'موعد جديد' : 'تعديل الموعد'}</h3><div class="f"><label class="w">العنوان<input name="title" required value="${esc(ev.title)}"></label>
  <label>التاريخ<input type="date" name="date" required value="${ev.date}"></label><label>الوقت<input type="time" name="time" value="${ev.time}"></label><label class="w">المكان/الرابط<input name="place" value="${esc(ev.place || '')}"></label></div>
  <div class="top" style="margin:14px 0 0"><button class="btn" value="ok">حفظ</button><button class="btn sec" value="cancel" formnovalidate>إلغاء</button>${isNew ? '' : '<button type="button" class="btn red" id="delev" style="margin-inline-start:auto">حذف</button>'}</div></form>`;
  dl.onclick = null; dl.showModal();
  if (!isNew) $('#delev').onclick = () => { S.events = S.events.filter(x => x.id !== ev.id); save(); dl.close(); render(); };
  $('#ef').onsubmit = e => {
    if (e.submitter?.value !== 'ok') return; const f = new FormData(e.target);
    Object.assign(ev, { title: f.get('title').trim(), date: f.get('date'), time: f.get('time'), place: f.get('place') });
    if (isNew) S.events.push(ev); save(); render();
  };
}

// ---------- الأحداث ----------
function addTemplate(tp) {
  const t = today(), d = parse(t);
  let due;
  if (tp.repeat === 'weekly') { const diff = (tp.day - d.getDay() + 7) % 7; due = addDays(t, diff); }
  else { const last = (y, m) => new Date(y, m + 1, 0).getDate(); let dd = new Date(d.getFullYear(), d.getMonth(), Math.min(tp.day, last(d.getFullYear(), d.getMonth()))); if (fmt(dd) < t) dd = new Date(d.getFullYear(), d.getMonth() + 1, Math.min(tp.day, last(d.getFullYear(), d.getMonth() + 1))); due = fmt(dd); }
  S.tasks.push(newTask({ title: tp.title, role: tp.role, repeat: tp.repeat, priority: tp.prio, notes: tp.note || '', due }));
}
const A = {
  toggle: id => { const t = byId(id); setStatus(t, t.status === 'done' ? 'todo' : 'done'); },
  star: id => { const t = byId(id); t.important = !t.important; },
  start: id => startTimer(id), stop: () => stopTimer(),
  edit: id => { taskForm(byId(id), false); return 1; },
  new: (id, el) => { taskForm(newTask({ role: ui.role || 'office', due: el.dataset.date || '', myDay: ui.view === 'myday' }), true); return 1; },
  addto: (id, el) => { taskForm(newTask({ status: el.dataset.st, role: ui.role || 'office' }), true); return 1; },
  event: (id, el) => { const ev = id ? S.events.find(e => e.id === id) : { id: uid(), title: '', date: ui.calSel, time: '09:00', place: '' }; eventForm(ev, !id); return 1; },
  day: (id, el) => { ui.calSel = el.dataset.date; },
  calprev: () => { const [y, m] = ui.calMonth.split('-').map(Number); ui.calMonth = fmt(new Date(y, m - 2, 1)).slice(0, 7); },
  calnext: () => { const [y, m] = ui.calMonth.split('-').map(Number); ui.calMonth = fmt(new Date(y, m, 1)).slice(0, 7); },
  caltoday: () => { ui.calMonth = today().slice(0, 7); ui.calSel = today(); },
  logout: () => { document.getElementById('logoutForm').submit(); return 1; },
  clock: toggleClock, wprev: () => ui.repWeek--, wnext: () => ui.repWeek++,
  tpl: (id, el) => { addTemplate(TEMPLATES[+el.dataset.i]); toast('أُضيفت للجدول'); },
  tplall: () => { TEMPLATES.forEach(addTemplate); toast('أُضيفت كل القوالب'); },
  addproj: () => { const v = $('#np').value.trim(); if (v && !S.projects.includes(v)) S.projects.push(v); },
  delproj: (id, el) => S.projects.splice(+el.dataset.i, 1),
  addperson: () => { const v = $('#npp').value.trim(); if (v && !S.people.includes(v)) S.people.push(v); },
  delperson: (id, el) => S.people.splice(+el.dataset.i, 1),
  notif: () => { if (!('Notification' in window)) return toast('المتصفح لا يدعم التنبيهات'); Notification.requestPermission().then(p => toast(p === 'granted' ? 'تم تفعيل التنبيهات' : 'لم يُمنح الإذن')); return 1; },
  export: () => { download(`markaz-backup-${today()}.json`, JSON.stringify(S, null, 1), 'application/json'); return 1; },
  csv: () => { const h = ['العنوان', 'الحالة', 'الأولوية', 'المجال', 'الاستحقاق', 'الوقت', 'المشروع', 'المسؤول', 'التكرار'], q = v => `"${String(v ?? '').replace(/"/g, '""')}"`; download(`tasks-${today()}.csv`, '﻿' + [h.map(q).join(',')].concat(S.tasks.map(t => [t.title, STATUS[t.status], PRIO[t.priority], ROLES[t.role], t.due, t.time, t.project, t.assignee, REPEAT[t.repeat]].map(q).join(','))).join('\n'), 'text/csv'); return 1; },
  import: () => { $('#imp').click(); return 1; },
  reset: () => { if (confirm('سيتم مسح كل البيانات. هل أنت متأكد؟ (صدِّر نسخة أولًا)')) { S = fix({}); } },
};
function download(name, txt, type) { const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([txt], { type })); a.download = name; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 1000); }

document.addEventListener('click', e => {
  const nav = e.target.closest('[data-nav]'); if (nav) { ui.view = nav.dataset.nav; render(); return; }
  const el = e.target.closest('[data-act]'); if (!el) return;
  const act = el.dataset.act; if (!A[act]) return;
  e.stopPropagation();
  const skip = A[act](el.dataset.id, el);
  if (!skip) { save(); render(); }
});
document.addEventListener('input', e => { const k = e.target.dataset.f; if (k === 'q') { ui.q = e.target.value; render(); } });
document.addEventListener('change', e => {
  const k = e.target.dataset.f;
  if (k === 'theme') { S.theme = e.target.value; save(); render(); } else if (k && k !== 'q') { ui[k] = e.target.value; render(); }
  if (e.target.id === 'imp' && e.target.files[0]) {
    const r = new FileReader(); r.onload = () => { try { const d = JSON.parse(r.result); if (!Array.isArray(d.tasks)) throw 0; S = fix(d); save(); render(); toast('تم الاستيراد'); } catch { toast('ملف غير صالح'); } };
    r.readAsText(e.target.files[0]);
  }
});
document.addEventListener('keydown', e => { if (e.target.id === 'qa' && e.key === 'Enter' && e.target.value.trim()) { quickAdd(e.target.value); } });
// السحب والإفلات (كانبان)
document.addEventListener('dragstart', e => { const c = e.target.closest?.('[data-drag]'); if (c) e.dataTransfer.setData('text/plain', c.dataset.drag); });
document.addEventListener('dragover', e => { const c = e.target.closest?.('[data-drop]'); if (c) { e.preventDefault(); c.classList.add('over'); } });
document.addEventListener('dragleave', e => e.target.closest?.('[data-drop]')?.classList.remove('over'));
document.addEventListener('drop', e => {
  const c = e.target.closest?.('[data-drop]'); if (!c) return; e.preventDefault();
  const t = byId(e.dataTransfer.getData('text/plain')); if (t) { setStatus(t, c.dataset.drop); save(); render(); }
});

// ---------- تنبيهات ومؤقّت ----------
function tick() {
  if (!S) return;
  const tm = $('#tmr'); if (tm && S.timer) tm.textContent = dur(Date.now() - S.timer.start);
  if (!('Notification' in window) || Notification.permission !== 'granted') return;
  const now = Date.now();
  open().forEach(t => {
    if (!t.due || !t.time) return;
    const at = new Date(`${t.due}T${t.time}`).getTime(), k = t.id + t.due + t.time;
    if (!S.notified[k] && now >= at - t.remind * 60000 && now < at + 3600000) { S.notified[k] = 1; new Notification('تذكير: ' + t.title, { body: `${dateLabel(t.due)} ${t.time}` }); save(); }
  });
}
setInterval(tick, 1000);
boot();
