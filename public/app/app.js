/* مركز القيادة — واجهة المهام والجدولة. النصوص بالعربية كمفاتيح، ومعجم الإنجليزية في i18n.js */
'use strict';

// ---------- اللغة ----------
const ME = window.MARKAZ_USER || null; // {id,name,email,admin,hr,locale} يحقنه الخادم بعد تسجيل الدخول
const LANG = ME && ME.locale === 'en' ? 'en' : 'ar';
const EN = window.MARKAZ_EN || {};
const t = (s, vars) => {
  let r = LANG === 'en' ? (EN[s] ?? s) : s;
  if (vars) for (const k in vars) r = r.split(':' + k).join(vars[k]);
  return r;
};
const LOC = (LANG === 'en' ? 'en' : 'ar') + '-u-ca-gregory-nu-latn';
const dtf = (d, o) => new Intl.DateTimeFormat(LOC, o).format(d);
const DAYS = [...Array(7)].map((_, i) => dtf(new Date(2024, 0, 7 + i), { weekday: 'long' })); // الأحد أولًا
const MONTHS = [...Array(12)].map((_, i) => dtf(new Date(2024, i, 1), { month: 'long' }));
const hijri = d => { try { return new Intl.DateTimeFormat(LANG === 'en' ? 'en-u-ca-islamic-umalqura-nu-latn' : 'ar-SA-u-ca-islamic-umalqura-nu-latn', { dateStyle: 'long' }).format(d); } catch { return ''; } };
const timeFmt = d => dtf(d, { timeStyle: 'short' });

// ---------- ثوابت ----------
const STATUS = { todo: 'للتنفيذ', doing: 'قيد العمل', review: 'مراجعة/انتظار', done: 'مكتملة' };
const PRIO = { low: 'منخفضة', med: 'متوسطة', high: 'عالية', urgent: 'عاجلة' };
const ROLES = { office: 'إدارة المكتب', accounts: 'الحسابات', exec: 'مساعدة المدير', personal: 'شخصي' };
const REPEAT = { none: 'بدون تكرار', daily: 'يومي', weekly: 'أسبوعي', monthly: 'شهري', yearly: 'سنوي' };
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
const ic = n => `<svg class="i" aria-hidden="true"><use href="#i-${n}"/></svg>`;
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
const dateLabel = s => { const d = parse(s), tt = today(); return s === tt ? t('اليوم') : s === addDays(tt, 1) ? t('غدًا') : s === addDays(tt, -1) ? t('أمس') : `${DAYS[d.getDay()]} ${d.getDate()} ${MONTHS[d.getMonth()]}`; };
function toast(m) { const e = $('#toast'); e.textContent = m; e.style.display = 'block'; clearTimeout(toast.h); toast.h = setTimeout(() => e.style.display = 'none', 2400); }

// ---------- الحالة والتخزين ----------
const KEY = 'markaz-alqiyada-v1';
let S;
const newTask = (o = {}) => Object.assign({ id: uid(), title: '', notes: '', status: 'todo', priority: 'med', due: '', time: '', role: 'office', project: '', assignee: '', tags: [], subtasks: [], repeat: 'none', myDay: false, important: false, estimate: 0, remind: 15, created: Date.now(), doneAt: null }, o);
function seed() {
  const d = today();
  return {
    v: 1, tasks: [
      newTask({ title: t('مثال: إرسال تقرير المبيعات للمدير'), due: d, time: '11:00', role: 'exec', priority: 'high', myDay: true, project: t('تقارير الإدارة') }),
      newTask({ title: t('مثال: مطابقة كشف البنك'), due: addDays(d, 1), role: 'accounts', status: 'doing', project: t('الإقفال الشهري') }),
      newTask({ title: t('مثال: تجديد عقد الصيانة'), due: addDays(d, 5), role: 'office', priority: 'low' }),
    ], events: [], projects: [t('تقارير الإدارة'), t('الإقفال الشهري'), t('عمليات المكتب')], people: [], logs: [], attendance: [], timer: null, notified: {},
  };
}
const fix = x => { x.tasks ||= []; x.people ||= []; x.notified ||= {}; x.logs ||= []; x.attendance ||= []; x.events ||= []; x.projects ||= []; return x; };

// ---------- الحساب والمزامنة (الخادم) ----------
const USER = ME;
const skey = () => USER ? `${KEY}:${USER.id}` : KEY;
const readLocal = k => { try { return JSON.parse(localStorage.getItem(k)); } catch { return null; } };
const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
let pushing = false, dirty = false;
function setSync(s) { setSync.last = s; const e = $('#sync'); if (e) e.textContent = s; }
function load() { S = fix(readLocal(skey()) || seed()); }
function save() {
  S.ts = Date.now();
  try { localStorage.setItem(skey(), JSON.stringify(S)); } catch { toast(t('تعذر الحفظ المحلي: مساحة التخزين ممتلئة أو محظورة')); }
  if (USER) { dirty = true; setSync(t('…جارٍ الحفظ')); clearTimeout(save.h); save.h = setTimeout(push, 1000); }
}
async function api(method, body, url = '/api/state') {
  const r = await fetch(url, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: body ? JSON.stringify(body) : undefined });
  if (r.status === 401 || r.status === 419) { location.href = '/login'; throw new Error('auth'); }
  return r;
}
const adopt = (remote, ts) => { S = fix(remote); S.ts = ts; try { localStorage.setItem(skey(), JSON.stringify(S)); } catch { } };
async function push() {
  if (!USER || !S || pushing) { if (pushing) dirty = true; return; }
  pushing = true; dirty = false;
  try {
    const r = await api('PUT', { data: S, ts: S.ts });
    if (r.status === 409) { const j = await r.json(); adopt(j.data, j.ts); render(); setSync(t('✓ حُدّثت من جهاز آخر')); }
    else if (!r.ok) setSync(t('⚠ تعذرت المزامنة (محفوظ على الجهاز)'));
    else setSync(t('✓ تمت المزامنة') + ' ' + timeFmt(new Date()));
  } catch (e) { if (e.message !== 'auth') setSync(t('⚠ لا اتصال — محفوظ على الجهاز وسيُزامَن لاحقًا')); }
  pushing = false;
  if (dirty) setTimeout(push, 500);
}
async function pull(initial) {
  try {
    const r = await api('GET'); if (!r.ok) throw new Error('bad');
    const { data, ts } = await r.json();
    if (data && ts > (S.ts || 0)) {
      if (!initial && $('#dlg').open) return; // لا نقاطع نموذجًا مفتوحًا
      adopt(data, ts); render(); setSync(t('✓ تمت المزامنة'));
    } else if (!data || ts < (S.ts || 0)) { S.ts ||= Date.now(); push(); }
    else setSync(t('✓ تمت المزامنة'));
  } catch (e) { if (e.message !== 'auth') setSync(t('⚠ لا اتصال — يعمل من نسخة الجهاز')); }
}
const VIEWS = ['dash', 'myday', 'tasks', 'important', 'board', 'calendar', 'time', 'templates', 'reports', 'settings'];
const ui = { view: VIEWS.includes(location.hash.slice(1)) ? location.hash.slice(1) : 'dash', q: '', role: '', status: '', prio: '', project: '', assignee: '', calMonth: today().slice(0, 7), calSel: today(), repWeek: 0 };
function boot() {
  load(); render();
  window.addEventListener('hashchange', () => { const v = location.hash.slice(1); if (VIEWS.includes(v) && v !== ui.view) { ui.view = v; render(); } });
  if (!USER) return;
  pull(true);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) pull(false); });
  setInterval(() => { if (!document.hidden && !pushing && !dirty) pull(false); }, 60000);
}

// ---------- منطق المهام ----------
const byId = id => S.tasks.find(x => x.id === id);
const isOver = x => x.status !== 'done' && x.due && (x.due < today() || (x.due === today() && x.time && x.time < new Date().toTimeString().slice(0, 5)));
function setStatus(x, st) {
  const was = x.status; x.status = st;
  if (st === 'done' && was !== 'done') {
    x.doneAt = Date.now();
    if (x.repeat !== 'none') {
      const base = x.due || today(); let nd = nextDate(base, x.repeat);
      while (nd < today()) nd = nextDate(nd, x.repeat); // تخطَّ الفترات الفائتة
      S.tasks.push({ ...x, id: uid(), status: 'todo', due: nd, doneAt: null, created: Date.now(), myDay: false, subtasks: x.subtasks.map(s => ({ ...s, d: false })) });
      toast(t('تم الإنجاز — أُنشئت المهمة التالية :d', { d: dateLabel(nd) }));
    }
    if (S.timer && S.timer.taskId === x.id) stopTimer();
  } else if (st !== 'done') x.doneAt = null;
}
function quickAdd(text) {
  let due = '', prio = 'med', title = text.trim();
  const rules = [[/(^|\s)(بعد غد|day after tomorrow)(?=\s|$)/i, 2], [/(^|\s)(غدا|غدًا|tomorrow)(?=\s|$)/i, 1], [/(^|\s)(اليوم|today)(?=\s|$)/i, 0]];
  for (const [re, n] of rules) if (re.test(title)) { due = addDays(today(), n); title = title.replace(re, ' ').trim(); break; }
  if (/(^|\s)(!|عاجل|urgent)(\s|$)/i.test(title)) { prio = 'urgent'; title = title.replace(/(^|\s)(!|عاجل|urgent)(?=\s|$)/i, ' ').trim(); }
  if (!title) return;
  S.tasks.push(newTask({ title, due, priority: prio, role: ui.role || 'office', myDay: due === today() }));
  save(); render(); toast(t('أُضيفت المهمة'));
}

// ---------- تتبع الوقت والدوام ----------
function startTimer(taskId) { if (S.timer) stopTimer(); S.timer = { taskId, start: Date.now() }; save(); render(); toast(t('بدأ التتبع')); }
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
const pill = (cls, txt) => `<span class="pill ${cls}">${txt}</span>`;
function taskRow(x) {
  const over = isOver(x), run = S.timer && S.timer.taskId === x.id;
  const sd = x.subtasks.length ? `${x.subtasks.filter(s => s.d).length}/${x.subtasks.length}` : '';
  return `<div class="row ${x.status === 'done' ? 'done' : ''}" data-act="edit" data-id="${x.id}">
    <button class="chk" data-act="toggle" data-id="${x.id}" title="${t('إنجاز')}" aria-label="${t('إنجاز')}"></button>
    <div class="ttl">${esc(x.title)}<div class="meta">
      ${x.due ? `<span class="tag ${over ? 'over' : ''}">${ic('calendar')} ${dateLabel(x.due)}${x.time ? ' ' + esc(x.time) : ''}</span>` : ''}
      <span class="tag">${t(ROLES[x.role])}</span>
      ${x.priority === 'urgent' || x.priority === 'high' ? `<span class="tag p-${x.priority}">${t(PRIO[x.priority])}</span>` : ''}
      ${x.project ? `<span class="tag">${ic('briefcase')} ${esc(x.project)}</span>` : ''}
      ${x.assignee ? `<span class="tag">${ic('user')} ${esc(x.assignee)}</span>` : ''}
      ${x.repeat !== 'none' ? `<span class="tag">${ic('repeat')} ${t(REPEAT[x.repeat])}</span>` : ''}
      ${sd ? `<span class="tag">${ic('check-circle')} ${sd}</span>` : ''}
      ${taskMs(x.id) ? `<span class="tag">${ic('clock')} ${hrs(taskMs(x.id))} ${t('س')}</span>` : ''}
    </div></div>
    ${x.status !== 'done' ? `<button class="btn sm sec" data-act="${run ? 'stop' : 'start'}" data-id="${x.id}">${run ? ic('stop') + ' ' + t('إيقاف') : ic('play') + ' ' + t('بدء')}</button>` : ''}
    <button class="star ${x.important ? 'on' : ''}" data-act="star" data-id="${x.id}" title="${t('مهم')}" aria-label="${t('مهم')}">${ic('star')}</button></div>`;
}
const list = (arr, empty = 'لا توجد مهام') => arr.length ? arr.map(taskRow).join('') : `<div class="empty">${t(empty)}</div>`;
const open = () => S.tasks.filter(x => x.status !== 'done');
const sortT = (a, b) => (a.due || '9').localeCompare(b.due || '9') || (a.time || '').localeCompare(b.time || '');
const pr = { urgent: 0, high: 1, med: 2, low: 3 };
const kpi = (icon, cls, n, label, style = '') => `<div class="card kpi"><span class="ic ${cls}">${ic(icon)}</span><div><b ${style}>${n}</b><span>${label}</span></div></div>`;
const head = (title, extra = '') => `<div class="page-h"><h1>${title}</h1><span class="sp"></span>${extra}</div>`;

function filtered() {
  const q = ui.q.trim().toLowerCase();
  return S.tasks.filter(x => (!q || (x.title + x.notes + x.project + x.assignee + x.tags.join(' ')).toLowerCase().includes(q))
    && (!ui.role || x.role === ui.role) && (!ui.status || x.status === ui.status) && (!ui.prio || x.priority === ui.prio)
    && (!ui.project || x.project === ui.project) && (!ui.assignee || x.assignee === ui.assignee));
}
function filterBar(withStatus = true) {
  const sel = (k, o, ph) => `<select data-f="${k}" aria-label="${t(ph)}"><option value="">${t(ph)}</option>${Object.entries(o).map(([v, l]) => `<option value="${v}" ${ui[k] === v ? 'selected' : ''}>${t(l)}</option>`).join('')}</select>`;
  const arr = (k, a, ph) => `<select data-f="${k}" aria-label="${t(ph)}"><option value="">${t(ph)}</option>${a.map(v => `<option ${ui[k] === v ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select>`;
  return `<div class="filters"><input type="search" placeholder="${t('بحث…')}" value="${esc(ui.q)}" data-f="q">${sel('role', ROLES, 'كل المجالات')}${withStatus ? sel('status', STATUS, 'كل الحالات') : ''}${sel('prio', PRIO, 'كل الأولويات')}${arr('project', S.projects, 'كل المشاريع')}${arr('assignee', S.people, 'كل المسؤولين')}</div>`;
}

// ---------- الشاشات ----------
const V = {};
V.dash = () => {
  const d = today(), o = open(), od = o.filter(isOver), td = o.filter(x => x.due === d), wk = o.filter(x => x.due > d && x.due <= addDays(d, 7)).sort(sortT);
  const [a, b] = weekRange(), done7 = S.tasks.filter(x => x.doneAt && x.doneAt >= Date.now() - 7 * 864e5).length;
  const evs = S.events.filter(e => e.date >= d).sort((x, y) => (x.date + x.time).localeCompare(y.date + y.time)).slice(0, 5);
  return head(t('لوحة القيادة')) + `<div class="grid g4">
    ${kpi('sun', '', td.length, t('مهام اليوم'))}${kpi('alert', 'red', od.length, t('متأخرة'))}${kpi('calendar', 'blu', wk.length, t('خلال 7 أيام'))}
    ${kpi('check-circle', 'grn', done7, t('أُنجزت آخر 7 أيام'))}${kpi('clock', 'vio', hrs(attMs(a, b)), t('ساعات الدوام هذا الأسبوع'))}</div><br>
    <div class="grid g2">
    <div class="card"><h3>${ic('alert')} ${t('متأخرة')}</h3>${list(od.sort(sortT), 'لا توجد مهام متأخرة 👏')}</div>
    <div class="card"><h3>${ic('sun')} ${t('اليوم')}</h3>${list(td.sort((x, y) => pr[x.priority] - pr[y.priority]), 'لا مهام مجدولة اليوم')}</div>
    <div class="card"><h3>${ic('calendar')} ${t('القادم')}</h3>${list(wk.slice(0, 8), 'لا شيء خلال 7 أيام')}</div>
    <div class="card"><h3>${ic('star')} ${t('مواعيد قادمة')}</h3>${evs.length ? evs.map(e => `<div class="row" data-act="event" data-id="${e.id}">${ic('calendar')} ${dateLabel(e.date)} ${esc(e.time)} — ${esc(e.title)}</div>`).join('') : `<div class="empty">${t('لا مواعيد')}</div>`}</div></div>`;
};
V.myday = () => {
  const d = today(), items = open().filter(x => x.myDay || x.due === d).sort((x, y) => pr[x.priority] - pr[y.priority]);
  const est = items.reduce((s, x) => s + (+x.estimate || 0), 0);
  return head(t('يومي')) + `<p class="sub">${DAYS[new Date().getDay()]} — ${hijri(new Date())}${est ? ` — ${t('الوقت المقدَّر: :h ساعة', { h: (est / 60).toFixed(1) })}` : ''}</p><div class="card">${list(items, 'يومك فارغ. أضف مهمة أو اختر «إضافة ليومي» داخل أي مهمة.')}</div>`;
};
V.tasks = () => {
  const arr = filtered().sort((x, y) => (x.status === 'done') - (y.status === 'done') || sortT(x, y));
  return head(t('كل المهام')) + filterBar() + `<div class="card">${list(arr)}</div>`;
};
V.important = () => head(t('المميّزة')) + `<div class="card">${list(open().filter(x => x.important).sort(sortT), 'لا مهام مميّزة بنجمة')}</div>`;
V.board = () => {
  const arr = filtered();
  return head(t('لوحة كانبان')) + filterBar(false) + `<div class="board">${Object.entries(STATUS).map(([k, l]) => {
    const c = arr.filter(x => x.status === k).sort((x, y) => pr[x.priority] - pr[y.priority] || sortT(x, y));
    return `<div class="col ${k}" data-drop="${k}"><h3>${t(l)}<span class="tag">${c.length}</span></h3>${c.map(x => `<div class="kc tp-${x.priority}" draggable="true" data-drag="${x.id}" data-act="edit" data-id="${x.id}">
      <div>${esc(x.title)}</div><div class="meta">${x.due ? `<span class="tag ${isOver(x) ? 'over' : ''}">${dateLabel(x.due)}</span>` : ''}<span class="tag">${t(ROLES[x.role])}</span>${x.assignee ? `<span class="tag">${ic('user')} ${esc(x.assignee)}</span>` : ''}</div></div>`).join('')}
      <button class="btn sm sec" data-act="addto" data-st="${k}">${ic('plus')} ${t('مهمة')}</button></div>`;
  }).join('')}</div>`;
};
V.calendar = () => {
  const [y, m] = ui.calMonth.split('-').map(Number), first = new Date(y, m - 1, 1), start = new Date(y, m - 1, 1 - first.getDay());
  let cells = '';
  for (let i = 0; i < 42; i++) {
    const d = new Date(start); d.setDate(start.getDate() + i); const s = fmt(d);
    const ts = S.tasks.filter(x => x.due === s), es = S.events.filter(e => e.date === s);
    cells += `<div class="d ${d.getMonth() !== m - 1 ? 'off' : ''} ${s === today() ? 'today' : ''} ${s === ui.calSel ? 'sel' : ''}" data-act="day" data-date="${s}"><div class="n"><span>${d.getDate()}</span></div>
      ${es.slice(0, 2).map(e => `<div class="ev">${esc(e.time)} ${esc(e.title)}</div>`).join('')}
      ${ts.slice(0, 3).map(x => `<div class="ev t ${x.status === 'done' ? 'dn' : ''}">${esc(x.title)}</div>`).join('')}
      ${ts.length + es.length > 5 ? `<div class="n">+${ts.length + es.length - 5}</div>` : ''}</div>`;
  }
  const sel = ui.calSel, dayT = S.tasks.filter(x => x.due === sel).sort(sortT), dayE = S.events.filter(e => e.date === sel).sort((a, b) => a.time.localeCompare(b.time));
  const rtl = LANG === 'ar';
  return head(t('الجدولة والتقويم'), `<button class="ibtn" data-act="calprev" aria-label="prev">${rtl ? '→' : '←'}</button><b>${MONTHS[m - 1]} ${y}</b><button class="ibtn" data-act="calnext" aria-label="next">${rtl ? '←' : '→'}</button><button class="btn sec sm" data-act="caltoday">${t('اليوم')}</button><button class="btn sm" data-act="event">${ic('plus')} ${t('موعد')}</button>`) +
    `<div class="cal">${DAYS.map(d => `<div class="h">${d}</div>`).join('')}${cells}</div><br>
  <div class="card"><h3>${dateLabel(sel)} — ${hijri(parse(sel))}</h3>
  ${dayE.map(e => `<div class="row" data-act="event" data-id="${e.id}">${ic('star')} ${esc(e.time)} ${esc(e.title)}${e.place ? ' — ' + esc(e.place) : ''}</div>`).join('')}${list(dayT, dayE.length ? '' : 'لا شيء في هذا اليوم')}
  <button class="btn sm" data-act="new" data-date="${sel}">${ic('plus')} ${t('مهمة في هذا اليوم')}</button></div>`;
};
V.time = () => {
  const [a, b] = weekRange(ui.repWeek), wl = logsIn(a, b), run = S.timer, tk = run && byId(run.taskId);
  const byP = {}; wl.forEach(l => { const x = byId(l.taskId), k = x?.project || t('بدون مشروع'); byP[k] = (byP[k] || 0) + l.end - l.start; });
  const tot = Object.values(byP).reduce((x, y) => x + y, 0), att = S.attendance.filter(x => x.in >= a && x.in < b);
  const rtl = LANG === 'ar';
  return head(t('تتبع الوقت والدوام')) + `<div class="grid g2">
  <div class="card"><h3>${ic('clock')} ${t('الدوام')}</h3><p>${clockedIn() ? t('أنت على رأس العمل منذ :t', { t: timeFmt(new Date(S.attendance.at(-1).in)) }) : t('لم تسجّل حضورك')}</p><button class="btn ${clockedIn() ? 'red' : ''}" data-act="clock">${clockedIn() ? ic('stop') + ' ' + t('تسجيل انصراف') : ic('play') + ' ' + t('تسجيل حضور')}</button>
  <p class="sub" style="margin:10px 0 0">${t('هذا دوامك الشخصي لتنظيم وقتك. الحضور الرسمي في قسم الموارد البشرية.')}</p></div>
  <div class="card"><h3>${ic('play')} ${t('مؤقّت المهمة')}</h3>${run ? `<div class="timer" id="tmr">${dur(Date.now() - run.start)}</div><p>${esc(tk?.title || '—')}</p><button class="btn red" data-act="stop">${ic('stop')} ${t('إيقاف وحفظ')}</button>` : `<p class="empty">${t('اضغط ▶ بدء بجانب أي مهمة')}</p>`}</div></div><br>
  <div class="card"><div class="page-h" style="margin:0 0 8px"><button class="ibtn" data-act="wprev">${rtl ? '→' : '←'}</button><b>${dtf(new Date(a), { day: 'numeric', month: 'long' })}</b><button class="ibtn" data-act="wnext">${rtl ? '←' : '→'}</button></div>
  <p>${t('إجمالي الدوام:')} <b>${hrs(attMs(a, b))} ${t('س')}</b> — ${t('وقت المهام المتتبَّع:')} <b>${hrs(tot)} ${t('س')}</b></p>
  ${Object.entries(byP).map(([k, v]) => `<div>${esc(k)} — ${hrs(v)} ${t('س')}<div class="bar-p"><i style="width:${tot ? v / tot * 100 : 0}%"></i></div></div>`).join('') || `<div class="empty">${t('لا سجلات هذا الأسبوع')}</div>`}</div><br>
  <div class="card"><h3>${t('سجل الدوام')}</h3><div class="tbl-wrap"><table><tr><th>${t('اليوم')}</th><th>${t('حضور')}</th><th>${t('انصراف')}</th><th>${t('المدة')}</th></tr>${att.slice().reverse().map(x => `<tr><td>${dateLabel(fmt(new Date(x.in)))}</td><td>${timeFmt(new Date(x.in))}</td><td>${x.out ? timeFmt(new Date(x.out)) : '—'}</td><td>${hrs((x.out || Date.now()) - x.in)} ${t('س')}</td></tr>`).join('') || `<tr><td colspan=4 class="empty">—</td></tr>`}</table></div></div>`;
};
V.templates = () => head(t('قوالب الجدولة الدورية')) + `<p class="sub">${t('مهام متكررة شائعة لمدير المكتب والحسابات ومساعد المدير. المواعيد المقترحة قابلة للتعديل — تحقق من المواعيد النظامية الرسمية (الزكاة والضريبة، التأمينات) قبل الاعتماد.')}</p>
  <div class="card">${TEMPLATES.map((x, i) => `<div class="row static"><div class="ttl">${t(x.title)}<div class="meta"><span class="tag">${t(ROLES[x.role])}</span><span class="tag">${ic('repeat')} ${t(REPEAT[x.repeat])}</span><span class="tag">${x.repeat === 'weekly' ? t('يوم:') + ' ' + DAYS[x.day] : x.repeat === 'yearly' ? t('سنوي') : t('يوم :n من الشهر', { n: x.day })}</span></div></div><button class="btn sm" data-act="tpl" data-i="${i}">${ic('plus')} ${t('أضف للجدول')}</button></div>`).join('')}</div><br>
  <button class="btn" data-act="tplall">${t('إضافة كل القوالب دفعة واحدة')}</button>`;
V.reports = () => {
  const all = S.tasks, dn = all.filter(x => x.status === 'done'), op = open();
  const rate = all.length ? Math.round(dn.length / all.length * 100) : 0;
  const cnt = (o, fn) => Object.entries(o).map(([k, l]) => { const n = all.filter(x => fn(x) === k).length; return `<div>${t(l)} — ${n}<div class="bar-p"><i style="width:${all.length ? n / all.length * 100 : 0}%"></i></div></div>`; }).join('');
  const days = [...Array(7)].map((_, i) => { const d = addDays(today(), i - 6), n = all.filter(x => x.doneAt && fmt(new Date(x.doneAt)) === d).length; return [d, n]; }), mx = Math.max(1, ...days.map(x => x[1]));
  const ppl = {}; op.forEach(x => { const k = x.assignee || t('غير مُسند'); ppl[k] = (ppl[k] || 0) + 1; });
  return head(t('التقارير'), `<button class="btn sec" onclick="window.print()">${ic('download')} ${t('طباعة / PDF')}</button>`) + `<div class="grid g4">${kpi('check-circle', 'grn', rate + '%', t('نسبة الإنجاز'))}${kpi('list', 'blu', op.length, t('مفتوحة'))}${kpi('alert', 'red', op.filter(isOver).length, t('متأخرة'))}${kpi('clock', 'vio', hrs(S.logs.reduce((s, l) => s + l.end - l.start, 0)), t('ساعات متتبَّعة'))}</div><br>
  <div class="grid g2"><div class="card"><h3>${t('حسب المجال')}</h3>${cnt(ROLES, x => x.role)}</div><div class="card"><h3>${t('حسب الحالة')}</h3>${cnt(STATUS, x => x.status)}</div>
  <div class="card"><h3>${t('المنجَز آخر 7 أيام')}</h3>${days.map(([d, n]) => `<div>${dateLabel(d)} — ${n}<div class="bar-p"><i style="width:${n / mx * 100}%"></i></div></div>`).join('')}</div>
  <div class="card"><h3>${t('المهام المفتوحة لكل مسؤول')}</h3>${Object.entries(ppl).map(([k, n]) => `<div>${esc(k)} — ${n}<div class="bar-p"><i style="width:${n / op.length * 100}%"></i></div></div>`).join('') || `<div class="empty">—</div>`}</div></div>`;
};
V.settings = () => head(t('الإعدادات والبيانات')) + `<div class="grid g2">
  <div class="card"><h3>${ic('briefcase')} ${t('المشاريع')}</h3>${S.projects.map((p, i) => `<div class="row static">${esc(p)}<button class="btn sm sec" style="margin-inline-start:auto" data-act="delproj" data-i="${i}">${t('حذف')}</button></div>`).join('')}<div class="filters" style="margin:10px 0 0"><input id="np" placeholder="${t('مشروع جديد')}"><button class="btn sm" data-act="addproj">${t('إضافة')}</button></div></div>
  <div class="card"><h3>${ic('users')} ${t('الفريق / المسؤولون عن المهام')}</h3>${S.people.map((p, i) => `<div class="row static">${ic('user')} ${esc(p)}<button class="btn sm sec" style="margin-inline-start:auto" data-act="delperson" data-i="${i}">${t('حذف')}</button></div>`).join('')}<div class="filters" style="margin:10px 0 0"><input id="npp" placeholder="${t('اسم شخص')}"><button class="btn sm" data-act="addperson">${t('إضافة')}</button></div></div>
  <div class="card"><h3>${ic('alert')} ${t('التنبيهات')}</h3><button class="btn sm sec" data-act="notif">${t('تفعيل تنبيهات المتصفح')}</button><p class="sub" style="margin:10px 0 0">${t('التنبيهات تعمل أثناء فتح الصفحة.')}</p></div>
  <div class="card"><h3>${ic('download')} ${t('النسخ الاحتياطي')}</h3><p class="sub">${t('بياناتك تُزامَن مع حسابك تلقائيًا. صدِّر نسخة احتياطية عند الحاجة.')}</p><div class="filters"><button class="btn sm" data-act="export">${t('تصدير JSON')}</button><button class="btn sm sec" data-act="import">${t('استيراد')}</button><button class="btn sm sec" data-act="csv">${t('تصدير المهام CSV')}</button><button class="btn sm red" data-act="reset">${t('مسح كل البيانات')}</button></div><input type="file" id="imp" accept=".json" hidden></div></div>`;

const NAV = [['dash', 'home', 'لوحة القيادة'], ['myday', 'sun', 'يومي'], ['tasks', 'check', 'كل المهام'], ['important', 'star', 'المميّزة'], ['board', 'board', 'كانبان'], ['calendar', 'calendar', 'الجدولة'], ['time', 'clock', 'الوقت والدوام'], ['templates', 'repeat', 'قوالب دورية'], ['reports', 'chart', 'التقارير'], ['settings', 'settings', 'الإعدادات']];
const HRNAV = [['/hr', 'home', 'نظرة عامة'], ['/hr/employees', 'users', 'الموظفون'], ['/hr/org', 'sitemap', 'الهيكل التنظيمي'], ['/hr/leave', 'calendar-off', 'الإجازات'], ['/hr/attendance', 'clock', 'الحضور والانصراف']];
function sidebar() {
  const o = open(), counts = { myday: o.filter(x => x.myDay || x.due === today()).length, tasks: o.length, important: o.filter(x => x.important).length, dash: o.filter(isOver).length };
  const nav = NAV.map(([k, i, l]) => `<button class="${ui.view === k ? 'on' : ''}" data-nav="${k}">${ic(i)} ${t(l)}${counts[k] ? `<span class="cnt ${k === 'dash' ? 'red' : ''}">${counts[k]}</span>` : ''}</button>`).join('');
  const hr = HRNAV.map(([h, i, l]) => `<a href="${h}">${ic(i)} ${t(l)}</a>`).join('');
  const foot = USER ? `<div class="side-foot"><a class="me" href="/account" style="color:inherit;text-decoration:none"><span class="av">${esc([...USER.name][0] || '?')}</span><span><b>${esc(USER.name)}</b><small dir="ltr">${esc(USER.email)}</small><small id="sync">${esc(setSync.last || '')}</small></span></a>
    <div class="row-btns">${USER.admin ? `<a class="btn sec sm" href="/users">${ic('lock')} ${t('حسابات الدخول')}</a>` : ''}<button class="btn sec sm" data-act="logout">${ic('logout')} ${t('خروج')}</button></div></div>` : '';
  return `<aside class="side"><div class="brand"><span class="logo">${ic('check')}</span>${t('مركز القيادة')}</div>
    <nav class="nav"><div class="nav-h">${t('المهام والجدولة')}</div>${nav}<div class="nav-h">${t('الموارد البشرية')}</div>${hr}</nav>${foot}</aside>`;
}
function render() {
  document.title = t('مركز القيادة');
  const keep = document.activeElement?.dataset?.f, pos = document.activeElement?.selectionStart;
  const qv = $('#qa')?.value || '';
  const d = new Date();
  $('#app').innerHTML = `<div class="app">${sidebar()}<main class="main">
    <header class="bar"><div class="q">${ic('search')}<input id="qa" value="${esc(qv)}" placeholder="${t('أضف مهمة سريعة… (مثال: اتصال بالمورد غدا !)')}"></div>
    <button class="btn" data-act="new">${ic('plus')} ${t('مهمة')}</button><span class="date">${dtf(d, { weekday: 'long', day: 'numeric', month: 'long' })} · ${hijri(d)}</span>
    <button class="ibtn" data-act="lang" title="${t('تغيير اللغة')}">${ic('globe')} ${LANG === 'ar' ? 'English' : 'العربية'}</button>
    <button class="ibtn" onclick="markazToggleTheme()" title="${t('الوضع الفاتح/الداكن')}" aria-label="${t('الوضع الفاتح/الداكن')}"><span class="ic-sun">${ic('sun')}</span><span class="ic-moon">${ic('moon')}</span></button></header>
    <section>${V[ui.view]()}</section></main></div>`;
  if (keep) { const el = $(`[data-f="${keep}"]`); if (el) { el.focus(); try { el.setSelectionRange(pos, pos); } catch { } } }
}

// ---------- نماذج ----------
function taskForm(x, isNew) {
  const dl = $('#dlg'), opt = (o, v) => Object.entries(o).map(([k, l]) => `<option value="${k}" ${v === k ? 'selected' : ''}>${t(l)}</option>`).join('');
  const lst = (a, v) => `<option value=""></option>${a.map(p => `<option ${p === v ? 'selected' : ''}>${esc(p)}</option>`).join('')}`;
  dl.innerHTML = `<form method="dialog" id="tf"><h2>${isNew ? t('مهمة جديدة') : t('تعديل المهمة')}</h2><div class="f">
    <label class="w">${t('العنوان')}<input name="title" required value="${esc(x.title)}"></label>
    <label>${t('الحالة')}<select name="status">${opt(STATUS, x.status)}</select></label><label>${t('الأولوية')}<select name="priority">${opt(PRIO, x.priority)}</select></label>
    <label>${t('تاريخ الاستحقاق')}<input type="date" name="due" value="${x.due}"></label><label>${t('الوقت')}<input type="time" name="time" value="${x.time}"></label>
    <label>${t('المجال')}<select name="role">${opt(ROLES, x.role)}</select></label><label>${t('التكرار')}<select name="repeat">${opt(REPEAT, x.repeat)}</select></label>
    <label>${t('المشروع')}<select name="project">${lst(S.projects, x.project)}</select></label><label>${t('المسؤول')}<input name="assignee" list="ppl" value="${esc(x.assignee)}"><datalist id="ppl">${S.people.map(p => `<option>${esc(p)}</option>`).join('')}</datalist></label>
    <label>${t('تنبيه قبل (دقيقة)')}<input type="number" name="remind" min="0" value="${x.remind}"></label><label>${t('الوقت المقدَّر (دقيقة)')}<input type="number" name="estimate" min="0" value="${x.estimate}"></label>
    <label class="w">${t('الوسوم (مفصولة بفاصلة)')}<input name="tags" value="${esc(x.tags.join(', '))}"></label>
    <label class="w">${t('ملاحظات')}<textarea name="notes" rows="3">${esc(x.notes)}</textarea></label>
    <div class="w sublist"><b style="font-size:13px;color:var(--mut)">${t('المهام الفرعية')}</b><div id="subs">${x.subtasks.map(s => subRow(s)).join('')}</div><button type="button" class="btn sm sec" id="addsub">${ic('plus')} ${t('خطوة')}</button></div>
    <label class="w chkrow"><input type="checkbox" name="myDay" ${x.myDay ? 'checked' : ''}> ${t('إضافة ليومي')}</label></div>
    <div class="acts"><button class="btn" value="ok">${t('حفظ')}</button><button class="btn sec" value="cancel" formnovalidate>${t('إلغاء')}</button>${isNew ? '' : `<button type="button" class="btn red" id="deltask" style="margin-inline-start:auto">${t('حذف')}</button>`}</div></form>`;
  dl.showModal();
  $('#addsub').onclick = () => $('#subs').insertAdjacentHTML('beforeend', subRow({ t: '', d: false }));
  dl.onclick = e => { if (e.target.closest('.rmsub')) e.target.closest('.sub-row').remove(); };
  if (!isNew) $('#deltask').onclick = () => { if (confirm(t('حذف المهمة نهائيًا؟'))) { S.tasks = S.tasks.filter(z => z.id !== x.id); save(); dl.close(); render(); } };
  $('#tf').onsubmit = e => {
    if (e.submitter?.value !== 'ok') return;
    const f = new FormData(e.target), g = k => f.get(k);
    Object.assign(x, { title: g('title').trim(), priority: g('priority'), due: g('due'), time: g('time'), role: g('role'), repeat: g('repeat'), project: g('project'), assignee: g('assignee').trim(), remind: +g('remind') || 0, estimate: +g('estimate') || 0, notes: g('notes'), myDay: f.has('myDay'), tags: g('tags').split(/[,،]/).map(v => v.trim()).filter(Boolean) });
    x.subtasks = [...dl.querySelectorAll('.sub-row')].map(r => ({ t: r.querySelector('[type=text]').value.trim(), d: r.querySelector('[type=checkbox]').checked })).filter(s => s.t);
    if (x.assignee && !S.people.includes(x.assignee)) S.people.push(x.assignee);
    delete S.notified[x.id];
    if (isNew) S.tasks.push(x);
    setStatus(x, g('status')); save(); render();
  };
}
const subRow = s => `<div class="sub-row"><input type="checkbox" ${s.d ? 'checked' : ''}><input type="text" value="${esc(s.t)}" placeholder="${t('خطوة')}"><button type="button" class="btn sm sec rmsub" aria-label="${t('حذف')}">${ic('x')}</button></div>`;
function eventForm(ev, isNew) {
  const dl = $('#dlg');
  dl.innerHTML = `<form method="dialog" id="ef"><h2>${isNew ? t('موعد جديد') : t('تعديل الموعد')}</h2><div class="f"><label class="w">${t('العنوان')}<input name="title" required value="${esc(ev.title)}"></label>
  <label>${t('التاريخ')}<input type="date" name="date" required value="${ev.date}"></label><label>${t('الوقت')}<input type="time" name="time" value="${ev.time}"></label><label class="w">${t('المكان/الرابط')}<input name="place" value="${esc(ev.place || '')}"></label></div>
  <div class="acts"><button class="btn" value="ok">${t('حفظ')}</button><button class="btn sec" value="cancel" formnovalidate>${t('إلغاء')}</button>${isNew ? '' : `<button type="button" class="btn red" id="delev" style="margin-inline-start:auto">${t('حذف')}</button>`}</div></form>`;
  dl.onclick = null; dl.showModal();
  if (!isNew) $('#delev').onclick = () => { S.events = S.events.filter(z => z.id !== ev.id); save(); dl.close(); render(); };
  $('#ef').onsubmit = e => {
    if (e.submitter?.value !== 'ok') return; const f = new FormData(e.target);
    Object.assign(ev, { title: f.get('title').trim(), date: f.get('date'), time: f.get('time'), place: f.get('place') });
    if (isNew) S.events.push(ev); save(); render();
  };
}

// ---------- الأحداث ----------
function addTemplate(tp) {
  const d0 = today(), d = parse(d0);
  let due;
  if (tp.repeat === 'weekly') { due = addDays(d0, (tp.day - d.getDay() + 7) % 7); }
  else { const last = (y, m) => new Date(y, m + 1, 0).getDate(); let dd = new Date(d.getFullYear(), d.getMonth(), Math.min(tp.day, last(d.getFullYear(), d.getMonth()))); if (fmt(dd) < d0) dd = new Date(d.getFullYear(), d.getMonth() + 1, Math.min(tp.day, last(d.getFullYear(), d.getMonth() + 1))); due = fmt(dd); }
  S.tasks.push(newTask({ title: t(tp.title), role: tp.role, repeat: tp.repeat, priority: tp.prio, notes: tp.note ? t(tp.note) : '', due }));
}
function download(name, txt, type) { const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([txt], { type })); a.download = name; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 1000); }
const A = {
  toggle: id => { const x = byId(id); setStatus(x, x.status === 'done' ? 'todo' : 'done'); },
  star: id => { const x = byId(id); x.important = !x.important; },
  start: id => startTimer(id), stop: () => stopTimer(),
  edit: id => { taskForm(byId(id), false); return 1; },
  new: (id, el) => { taskForm(newTask({ role: ui.role || 'office', due: el.dataset.date || '', myDay: ui.view === 'myday' }), true); return 1; },
  addto: (id, el) => { taskForm(newTask({ status: el.dataset.st, role: ui.role || 'office' }), true); return 1; },
  event: (id) => { const ev = id ? S.events.find(e => e.id === id) : { id: uid(), title: '', date: ui.calSel, time: '09:00', place: '' }; eventForm(ev, !id); return 1; },
  day: (id, el) => { ui.calSel = el.dataset.date; return 2; },
  calprev: () => { const [y, m] = ui.calMonth.split('-').map(Number); ui.calMonth = fmt(new Date(y, m - 2, 1)).slice(0, 7); return 2; },
  calnext: () => { const [y, m] = ui.calMonth.split('-').map(Number); ui.calMonth = fmt(new Date(y, m, 1)).slice(0, 7); return 2; },
  caltoday: () => { ui.calMonth = today().slice(0, 7); ui.calSel = today(); return 2; },
  clock: toggleClock, wprev: () => { ui.repWeek--; return 2; }, wnext: () => { ui.repWeek++; return 2; },
  tpl: (id, el) => { addTemplate(TEMPLATES[+el.dataset.i]); toast(t('أُضيفت للجدول')); },
  tplall: () => { TEMPLATES.forEach(addTemplate); toast(t('أُضيفت كل القوالب')); },
  addproj: () => { const v = $('#np').value.trim(); if (v && !S.projects.includes(v)) S.projects.push(v); },
  delproj: (id, el) => S.projects.splice(+el.dataset.i, 1),
  addperson: () => { const v = $('#npp').value.trim(); if (v && !S.people.includes(v)) S.people.push(v); },
  delperson: (id, el) => S.people.splice(+el.dataset.i, 1),
  notif: () => { if (!('Notification' in window)) return toast(t('المتصفح لا يدعم التنبيهات')); Notification.requestPermission().then(p => toast(p === 'granted' ? t('تم تفعيل التنبيهات') : t('لم يُمنح الإذن'))); return 1; },
  export: () => { download(`markaz-backup-${today()}.json`, JSON.stringify(S, null, 1), 'application/json'); return 1; },
  csv: () => { const h = ['العنوان', 'الحالة', 'الأولوية', 'المجال', 'الاستحقاق', 'الوقت', 'المشروع', 'المسؤول', 'التكرار'].map(v => t(v)), q = v => `"${String(v ?? '').replace(/"/g, '""')}"`; download(`tasks-${today()}.csv`, '﻿' + [h.map(q).join(',')].concat(S.tasks.map(x => [x.title, t(STATUS[x.status]), t(PRIO[x.priority]), t(ROLES[x.role]), x.due, x.time, x.project, x.assignee, t(REPEAT[x.repeat])].map(q).join(','))).join('\n'), 'text/csv'); return 1; },
  import: () => { $('#imp').click(); return 1; },
  reset: () => { if (confirm(t('سيتم مسح كل البيانات. هل أنت متأكد؟ (صدِّر نسخة أولًا)'))) { S = fix({}); } },
  logout: () => { document.getElementById('logoutForm').submit(); return 1; },
  lang: () => { api('POST', { locale: LANG === 'ar' ? 'en' : 'ar' }, '/preferences').then(() => location.reload()).catch(() => { }); return 1; },
};
document.addEventListener('click', e => {
  const nav = e.target.closest('[data-nav]'); if (nav) { ui.view = nav.dataset.nav; history.replaceState(null, '', '#' + ui.view); render(); return; }
  const el = e.target.closest('[data-act]'); if (!el || !S) return;
  const act = el.dataset.act; if (!A[act]) return;
  e.stopPropagation();
  const r = A[act](el.dataset.id, el);
  if (r === 2) render(); else if (!r) { save(); render(); }
});
document.addEventListener('input', e => { const k = e.target.dataset.f; if (k === 'q') { ui.q = e.target.value; render(); } });
document.addEventListener('change', e => {
  const k = e.target.dataset.f;
  if (k && k !== 'q') { ui[k] = e.target.value; render(); }
  if (e.target.id === 'imp' && e.target.files[0]) {
    const r = new FileReader(); r.onload = () => { try { const d = JSON.parse(r.result); if (!Array.isArray(d.tasks)) throw 0; S = fix(d); save(); render(); toast(t('تم الاستيراد')); } catch { toast(t('ملف غير صالح')); } };
    r.readAsText(e.target.files[0]);
  }
});
document.addEventListener('keydown', e => { if (e.target.id === 'qa' && e.key === 'Enter' && e.target.value.trim()) { const v = e.target.value; e.target.value = ''; quickAdd(v); } });
// السحب والإفلات (كانبان)
document.addEventListener('dragstart', e => { const c = e.target.closest?.('[data-drag]'); if (c) e.dataTransfer.setData('text/plain', c.dataset.drag); });
document.addEventListener('dragover', e => { const c = e.target.closest?.('[data-drop]'); if (c) { e.preventDefault(); c.classList.add('over'); } });
document.addEventListener('dragleave', e => e.target.closest?.('[data-drop]')?.classList.remove('over'));
document.addEventListener('drop', e => {
  const c = e.target.closest?.('[data-drop]'); if (!c) return; e.preventDefault();
  const x = byId(e.dataTransfer.getData('text/plain')); if (x) { setStatus(x, c.dataset.drop); save(); render(); }
});

// ---------- تنبيهات ومؤقّت ----------
function tick() {
  if (!S) return;
  const tm = $('#tmr'); if (tm && S.timer) tm.textContent = dur(Date.now() - S.timer.start);
  if (!('Notification' in window) || Notification.permission !== 'granted') return;
  const now = Date.now();
  open().forEach(x => {
    if (!x.due || !x.time) return;
    const at = new Date(`${x.due}T${x.time}`).getTime(), k = x.id + x.due + x.time;
    if (!S.notified[k] && now >= at - x.remind * 60000 && now < at + 3600000) { S.notified[k] = 1; new Notification(t('تذكير:') + ' ' + x.title, { body: `${dateLabel(x.due)} ${x.time}` }); save(); }
  });
}
setInterval(tick, 1000);
boot();
