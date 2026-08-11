// ═══════════════════════════════════
//  API HELPERS (reemplazan localStorage)
// ═══════════════════════════════════
const API = 'api/';

async function apiGet(endpoint) {
  const res = await fetch(API + endpoint);
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}

async function apiPost(endpoint, data) {
  const res = await fetch(API + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || 'Error en la petición');
  return json;
}

async function apiDelete(endpoint) {
  const res = await fetch(API + endpoint, { method: 'DELETE' });
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || 'Error en la petición');
  return json;
}

// ═══════════════════════════════════
//  CACHE LOCAL (se llena desde API)
// ═══════════════════════════════════
let _flota = [];
let _insps = [];
let _inspectors = [];

async function cargarDatos() {
  try {
    [_flota, _insps, _inspectors] = await Promise.all([
      apiGet('vehiculos.php'),
      apiGet('inspecciones.php'),
      apiGet('inspectores.php')
    ]);
  } catch (e) {
    console.error('Error cargando datos:', e);
    toast('Error al conectar con el servidor', 1);
  }
}

const getFlota = () => _flota;
const getInsps = () => _insps;
const getInspectors = () => _inspectors.map(i => i.nombre);

// Definición central de todos los tipos de inspección
const TIPOS_INSP = [
  { key: 'botiquin', label: 'Botiquín', icon: '🩹', cls: 'bot' },
  { key: 'carretilla', label: 'Carretilla', icon: '🛒', cls: 'car' },
  { key: 'extintor', label: 'Extintor', icon: '🧯', cls: 'ext' },
  { key: 'caja_fuerte', label: 'Caja Fuerte', icon: '🔐', cls: 'caj' },
  { key: 'boton_panico', label: 'Botón de Pánico', icon: '🚨', cls: 'btn-p' },
  { key: 'inspeccion_vehiculo', label: 'Inspección Vehículo', icon: '🚛', cls: 'veh' }
];
const TIPOS_KEYS = TIPOS_INSP.map(t => t.key);

let filtAct = 'todos', histFilt = 'todas', modalTipo = '';
let _fotosTemp = []; // fotos pendientes para la inspección actual
const MAX_FOTOS = 5;

// ═══════════════════════════════════
//  INIT
// ═══════════════════════════════════
document.getElementById('fechaHoy').textContent = new Date().toLocaleDateString('es-CO', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
document.getElementById('tabs').addEventListener('click', e => {
  if (e.target.tagName !== 'BUTTON') return;
  document.querySelectorAll('.topbar-tabs button').forEach(b => b.classList.remove('on'));
  e.target.classList.add('on');
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
  document.getElementById('tab-' + e.target.dataset.tab).classList.add('on');
  if (e.target.dataset.tab === 'dashboard') renderDash();
  if (e.target.dataset.tab === 'historial') renderHist();
  if (e.target.dataset.tab === 'config') renderConfig();
});

async function init() {
  await cargarDatos();
  renderGrid();
  renderKPIs();
  renderConfig();
}

// ═══════════════════════════════════
//  DROPDOWN
// ═══════════════════════════════════
function toggleDD() { document.getElementById('ddInsp').classList.toggle('open') }
document.addEventListener('click', e => { if (!e.target.closest('#ddInsp')) document.getElementById('ddInsp').classList.remove('open') });

// ═══════════════════════════════════
//  CONFIG
// ═══════════════════════════════════
async function addVeh() {
  const p = document.getElementById('inPlaca').value.trim().toUpperCase();
  const marca = document.getElementById('inMarca').value.trim();
  const linea = document.getElementById('inLinea').value.trim();
  const tipo = document.getElementById('inTipo').value;
  const anio = document.getElementById('inAño').value;
  const zona = document.getElementById('inZona').value.trim();
  if (!p) return toast('Ingresa la placa', 1);

  try {
    await apiPost('vehiculos.php', { placa: p, tipo: tipo || 'Sencillo', marca: marca || '', linea: linea || '', anio: anio ? parseInt(anio) : 0, zona: zona || '' });
    ['inPlaca', 'inMarca', 'inLinea', 'inAño', 'inZona'].forEach(id => document.getElementById(id).value = '');
    await init();
    toast('Vehículo agregado');
  } catch (e) {
    toast(e.message, 1);
  }
}

async function delVeh(p) {
  try {
    await apiDelete('vehiculos.php?placa=' + encodeURIComponent(p));
    await init();
    toast('Eliminado');
  } catch (e) {
    toast(e.message, 1);
  }
}

async function addInsp() {
  const n = document.getElementById('inInsp').value.trim();
  if (!n) return;
  try {
    await apiPost('inspectores.php', { nombre: n });
    document.getElementById('inInsp').value = '';
    await init();
    toast('Inspector agregado');
  } catch (e) {
    toast(e.message, 1);
  }
}

async function delInsp(id) {
  try {
    await apiDelete('inspectores.php?id=' + id);
    await init();
    toast('Inspector eliminado');
  } catch (e) {
    toast(e.message, 1);
  }
}

function renderConfig() {
  const f = getFlota();
  document.getElementById('listaFlota').innerHTML = f.length ? f.map(v => `
    <div class="vrow">
      <span class="placa">${v.placa}</span>
      <span style="flex:1;font-size:.78rem">${v.marca || ''} ${v.linea || ''} ${v.anio ? '(' + v.anio + ')' : ''}</span>
      <span style="font-size:.7rem;color:var(--muted)">${v.tipo || ''} · ${v.zona || ''}</span>
      <button class="btn-del" onclick="delVeh('${v.placa}')">✕</button>
    </div>
  `).join('') : '<p style="font-size:.8rem;color:var(--muted)">Sin vehículos.</p>';

  const ins = _inspectors;
  document.getElementById('listaInsp').innerHTML = ins.map(n => `
    <div class="vrow"><span style="flex:1;font-size:.8rem">${n.nombre}</span><button class="btn-del" onclick="delInsp(${n.id})">✕</button></div>
  `).join('');
}

// ═══════════════════════════════════
//  EVALUATION
// ═══════════════════════════════════
function getLastInsp(placa, tipo) {
  return getInsps().filter(i => i.placa === placa && i.tipo === tipo).sort((a, b) => b.fecha > a.fecha ? 1 : -1)[0] || null;
}

function evalInsp(insp) {
  if (!insp) return { estado: 'none', pct: 0 };
  const items = insp.items || {};
  const checks = Object.entries(items).filter(([k, v]) => typeof v === 'boolean');
  const dates = Object.entries(items).filter(([k, v]) => typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v));
  const total = checks.length;
  const ok = checks.filter(([, v]) => v).length;
  const pct = total ? Math.round(ok / total * 100) : 0;
  const hoy = new Date();
  let vencido = false, proximo = false;
  dates.forEach(([, f]) => {
    const d = new Date(f + 'T00:00:00');
    const dias = Math.ceil((d - hoy) / 864e5);
    if (dias < 0) vencido = true;
    else if (dias <= 30) proximo = true;
  });
  if (pct < 100 || vencido) return { estado: 'fail', pct };
  if (proximo) return { estado: 'warn', pct };
  return { estado: 'ok', pct };
}

function evalVehiculo(placa) {
  const tipos = TIPOS_KEYS;
  const evals = tipos.map(t => evalInsp(getLastInsp(placa, t)));
  const withData = evals.filter(e => e.estado !== 'none');
  if (!withData.length) return { estado: 'none', pct: 0, evals };
  const pct = Math.round(withData.reduce((s, e) => s + e.pct, 0) / withData.length);
  const hasFail = withData.some(e => e.estado === 'fail');
  const hasWarn = withData.some(e => e.estado === 'warn');
  return { estado: hasFail ? 'fail' : hasWarn ? 'warn' : 'ok', pct, evals };
}

// ═══════════════════════════════════
//  RENDER GRID
// ═══════════════════════════════════
function renderGrid() {
  const f = getFlota(), grid = document.getElementById('vgrid'), emp = document.getElementById('emptyV');
  if (!f.length) { grid.innerHTML = ''; emp.style.display = ''; return; }
  emp.style.display = 'none';
  grid.innerHTML = f.map(v => {
    const ev = evalVehiculo(v.placa);
    const evArr = ev.evals || TIPOS_INSP.map(() => evalInsp(null));
    return `<div class="vc" data-placa="${v.placa}" data-estado="${ev.estado}" data-zona="${v.zona || ''}" data-marca="${v.marca || ''}">
      <div class="vc-head" onclick="this.closest('.vc').classList.toggle('open')">
        <div class="sem-circle ${ev.estado}">${ev.estado === 'none' ? '—' : ev.pct + '%'}</div>
        <span class="placa">${v.placa}</span>
        <div class="vc-info">
          <div class="vc-sub">${v.marca || ''} ${v.linea || ''} ${v.anio ? '(' + v.anio + ')' : ''}</div>
          <div class="vc-sub" style="font-size:.68rem">${v.tipo || ''} · ${v.zona || ''}</div>
        </div>
        <span class="vc-chev">▼</span>
      </div>
      <div class="sem-strip">
        ${TIPOS_INSP.map((t, i) => `<div class="sem-i"><div class="sem-d ${evArr[i].estado}"></div><div class="sem-t">${t.label}</div></div>`).join('')}
      </div>
      <div class="vc-det">
        ${renderDetVeh(v.placa)}
      </div>
    </div>`;
  }).join('');
  filtrar();
}

function renderDetVeh(placa) {
  const tipos = TIPOS_INSP.map(t => ({ key: t.key, icon: t.icon, name: t.label }));
  let html = '';
  tipos.forEach(t => {
    const insp = getLastInsp(placa, t.key);
    const ev = evalInsp(insp);
    if (!insp) {
      html += `<div class="det-sec"><div class="det-h"><div class="sem-d none"></div>${t.icon} ${t.name}</div><p style="font-size:.76rem;color:var(--muted)">Sin inspección</p></div>`;
      return;
    }
    const items = insp.items || {};
    let rows = '';
    Object.entries(items).forEach(([k, v]) => {
      const label = k.replace(/_/g, ' ');
      if (typeof v === 'boolean') {
        rows += `<div class="det-row"><span class="${v ? 'ck' : 'cr'}">${v ? '✓' : '✕'}</span> ${label}</div>`;
      } else if (typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v)) {
        const d = new Date(v + 'T00:00:00'), hoy = new Date(), dias = Math.ceil((d - hoy) / 864e5);
        const cls = dias < 0 ? 'cr' : dias <= 30 ? 'wr' : 'ck';
        const txt = dias < 0 ? `Vencido (${Math.abs(dias)}d)` : dias <= 30 ? `${dias}d restantes` : 'Vigente';
        rows += `<div class="det-row"><span class="${cls}">${cls === 'cr' ? '✕' : cls === 'wr' ? '⚠' : '✓'}</span> ${label}: ${v} <span style="font-size:.7rem;color:var(--muted)">(${txt})</span></div>`;
      } else if (typeof v === 'string' && v) {
        rows += `<div class="det-row" style="color:var(--text2)">▸ ${label}: ${v}</div>`;
      }
    });
    const fotos = insp.fotos && insp.fotos.length ? `<div class="foto-gallery">${insp.fotos.map(src => `<img src="${src}" onclick="abrirLightbox('${src.replace(/'/g, "\\'")}')">`).join('')}</div>` : '';
    html += `<div class="det-sec">
      <div class="det-h"><div class="sem-d ${ev.estado}"></div>${t.icon} ${t.name} <span style="margin-left:auto;font-size:.68rem;color:var(--muted)">${ev.pct}% · ${insp.fecha}</span></div>
      <div class="det-grid">${rows}</div>
      ${insp.observaciones ? `<div class="det-foot">${insp.observaciones}</div>` : ''}
      ${fotos}
    </div>`;
  });
  html += `<div class="vc-acts">
    ${TIPOS_INSP.map(t => `<button class="btn btn-navy btn-sm" onclick="abrirModal('${t.key}','${placa}')">${t.icon} ${t.label}</button>`).join('')}
  </div>`;
  return html;
}

// ═══════════════════════════════════
//  FILTROS
// ═══════════════════════════════════
function setFilt(f, btn) { filtAct = f; document.querySelectorAll('#tab-vehiculos .chip').forEach(c => c.classList.remove('on')); btn.classList.add('on'); filtrar(); }
function filtrar() {
  const q = document.getElementById('searchBox').value.trim().toUpperCase();
  let vis = 0, tot = 0;
  document.querySelectorAll('.vc').forEach(c => {
    tot++;
    const p = c.dataset.placa, e = c.dataset.estado, z = (c.dataset.zona || '').toUpperCase(), m = (c.dataset.marca || '').toUpperCase();
    const show = (!q || p.includes(q) || z.includes(q) || m.includes(q)) && (filtAct === 'todos' || e === filtAct);
    c.style.display = show ? '' : 'none';
    if (show) vis++;
  });
  document.getElementById('fcount').textContent = `${vis} de ${tot}`;
}

// ═══════════════════════════════════
//  KPIs
// ═══════════════════════════════════
function renderKPIs() {
  const f = getFlota(), tot = f.length;
  let ok = 0, warn = 0, fail = 0, none = 0;
  f.forEach(v => { const e = evalVehiculo(v.placa); if (e.estado === 'ok') ok++; else if (e.estado === 'warn') warn++; else if (e.estado === 'fail') fail++; else none++ });
  const h = `
    <div class="kpi"><div class="kpi-l">Total flota</div><div class="kpi-v">${tot}</div><div class="kpi-s">equipos registrados</div></div>
    <div class="kpi"><div class="kpi-l">Cumplen</div><div class="kpi-v g">${ok}</div><div class="kpi-s">${tot ? Math.round(ok / tot * 100) : 0}% de la flota</div></div>
    <div class="kpi"><div class="kpi-l">Con alertas</div><div class="kpi-v a">${warn}</div><div class="kpi-s">próximos a vencer</div></div>
    <div class="kpi"><div class="kpi-l">Incumplen</div><div class="kpi-v r">${fail}</div><div class="kpi-s">requieren atención</div></div>
    <div class="kpi"><div class="kpi-l">Sin inspección</div><div class="kpi-v">${none}</div><div class="kpi-s">pendientes</div></div>
  `;
  document.getElementById('kpis1').innerHTML = h;
}

// ═══════════════════════════════════
//  MODAL — Forms
// ═══════════════════════════════════
function abrirModal(tipo, placaPre) {
  modalTipo = tipo;
  document.getElementById('ddInsp').classList.remove('open');
  const f = getFlota();
  if (!f.length) { toast('Primero agrega equipos en Configuración', 1); return; }

  const icons = { botiquin: '🩹', carretilla: '🛒', extintor: '🧯', caja_fuerte: '🔐', boton_panico: '🚨', inspeccion_vehiculo: '🚛' };
  const titles = { botiquin: 'Inspección de Botiquín', carretilla: 'Inspección de Carretilla', extintor: 'Verificación e Inspección de Extintor', caja_fuerte: 'Inspección Visual Externa Caja Fuerte', boton_panico: 'Inspección de Botón de Pánico', inspeccion_vehiculo: 'Inspección de Vehículo' };
  document.getElementById('mIcon').textContent = icons[tipo];
  document.getElementById('mTitle').textContent = titles[tipo];

  const inspectors = getInspectors();
  const selPlaca = `<div class="fg"><label class="fl">Placa *</label><select class="fs" id="f_placa">${f.map(v => `<option value="${v.placa}" ${v.placa === placaPre ? 'selected' : ''}>${v.placa} — ${v.tipo}</option>`).join('')}</select></div>`;
  const selInsp = `<div class="fg"><label class="fl">Inspector *</label><select class="fs" id="f_inspector"><option value="">Seleccionar…</option>${inspectors.map(n => `<option>${n}</option>`).join('')}</select></div>`;
  const fechaField = `<div class="fg"><label class="fl">Fecha del registro *</label><input type="date" class="fi" id="f_fecha" value="${new Date().toISOString().split('T')[0]}"></div>`;

  let body = fechaField + selPlaca + selInsp;

  if (tipo === 'botiquin') body += formBotiquin();
  else if (tipo === 'carretilla') body += formCarretilla();
  else if (tipo === 'extintor') body += formExtintor();
  else if (tipo === 'caja_fuerte') body += formCajaFuerte();
  else if (tipo === 'boton_panico') body += formBotonPanico();
  else if (tipo === 'inspeccion_vehiculo') body += formInspeccionVehiculo();

  body += `<div class="fg"><label class="fl">Observaciones *</label><textarea class="ft" id="f_obs" placeholder="Notas adicionales…"></textarea></div>`;

  body += `<div class="fg"><label class="fl">📷 Evidencia fotográfica (máx. ${MAX_FOTOS})</label>
    <div class="foto-upload" id="fotoUpload" onclick="document.getElementById('fotoInput').click()">
      <input type="file" id="fotoInput" accept="image/*" multiple onchange="agregarFotos(this.files)">
      <div class="fu-icon">📷</div>
      <div class="fu-text">Click para agregar fotos</div>
      <div class="fu-count" id="fotoCount">0 de ${MAX_FOTOS}</div>
    </div>
    <div class="foto-preview" id="fotoPreview"></div>
  </div>`;

  _fotosTemp = [];
  document.getElementById('mBody').innerHTML = body;
  document.getElementById('overlay').classList.add('vis');
}

// ═══════════════════════════════════
//  FOTOS — Redimensionar y previsualizar
// ═══════════════════════════════════
function redimensionarImagen(file, maxW = 800) {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        let w = img.width, h = img.height;
        if (w > maxW) { h = Math.round(h * maxW / w); w = maxW; }
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        resolve(canvas.toDataURL('image/jpeg', 0.6));
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });
}

async function agregarFotos(files) {
  const restantes = MAX_FOTOS - _fotosTemp.length;
  if (restantes <= 0) { toast(`Máximo ${MAX_FOTOS} fotos`, 1); return; }

  const archivos = Array.from(files).slice(0, restantes);
  for (const f of archivos) {
    const base64 = await redimensionarImagen(f);
    _fotosTemp.push(base64);
  }
  renderFotoPreview();
  document.getElementById('fotoInput').value = '';
}

function eliminarFoto(idx) {
  _fotosTemp.splice(idx, 1);
  renderFotoPreview();
}

function renderFotoPreview() {
  const cont = document.getElementById('fotoPreview');
  cont.innerHTML = _fotosTemp.map((src, i) =>
    `<div class="foto-thumb"><img src="${src}"><button class="foto-del" onclick="eliminarFoto(${i})">✕</button></div>`
  ).join('');
  document.getElementById('fotoCount').textContent = `${_fotosTemp.length} de ${MAX_FOTOS}`;
  const upload = document.getElementById('fotoUpload');
  if (_fotosTemp.length >= MAX_FOTOS) upload.style.opacity = '0.5';
  else upload.style.opacity = '1';
}

function formBotiquin() {
  const items = [
    { id: 'gasas', label: 'Gasas limpias', type: 'date' },
    { id: 'esparadrapo', label: 'Esparadrapo de tela rollo de 4"', type: 'date' },
    { id: 'bajalenguas', label: 'Baja lenguas', type: 'date' },
    { id: 'guantes_latex', label: 'Guantes de latex', type: 'date' },
    { id: 'venda_2x5', label: 'Venda Elástica 2x5 yardas', type: 'date' },
    { id: 'venda_3x5', label: 'Venda Elástica 3x5 yardas', type: 'date' },
    { id: 'venda_5x5', label: 'Venda Elástica 5x5 yardas', type: 'date' },
    { id: 'yodopovidona', label: 'Yodopovidona (jabón Quirúrgico)', type: 'date' },
    { id: 'solucion_salina', label: 'Solución Salina', type: 'date' },
    { id: 'termometro', label: 'Termómetro Digital', type: 'radio' },
    { id: 'alcohol', label: 'Alcohol Antiséptico', type: 'date' },
    { id: 'botella_agua', label: 'Botella de agua', type: 'date' },
    { id: 'bandas_adhesivas', label: 'Bandas adhesivas', type: 'date' },
    { id: 'tijeras', label: 'Tijeras punta roma 3"', type: 'radio' },
    { id: 'pito', label: 'Pito de emergencia', type: 'radio' },
  ];
  return `<div class="clsec"><div class="clsec-h">🩹 Elementos del botiquín</div>
    ${items.map(it => {
      if (it.type === 'date') return `<div class="cl-row"><label>${it.label} *</label><input type="date" id="b_${it.id}" class="fi" style="width:auto;flex:0"></div>`;
      return `<div class="cl-row"><label>${it.label} *</label><div class="radio-group"><label><input type="radio" name="b_${it.id}" value="cumple"> Cumple</label><label><input type="radio" name="b_${it.id}" value="no_cumple"> No cumple</label></div></div>`;
    }).join('')}
  </div>`;
}

function formCarretilla() {
  const items = [
    { id: 'num_carretilla', label: '# de Carretilla *', type: 'select', opts: ['1', '2'] },
    { id: 'manijas', label: '¿Las manijas se encuentran en buen estado?' },
    { id: 'partes_sueltas', label: '¿Se encuentra suelta alguna parte de la carretilla?', invert: true },
    { id: 'llantas_estado', label: '¿Las llantas están en buen estado?' },
    { id: 'llantas_giran', label: '¿Las llantas giran con normalidad (sin fricción)?' },
    { id: 'rodamientos', label: '¿Los rodamientos se encuentran en buen estado?' },
    { id: 'pintura', label: '¿Se encuentra la pintura en buen estado?' },
    { id: 'sticker_placa', label: '¿La carretilla cuenta con su sticker de marcación (placa)?' },
    { id: 'sticker_capacidad', label: '¿La carretilla cuenta con su sticker de marcación (capacidad de carga)?' },
  ];
  return `<div class="clsec"><div class="clsec-h">🛒 Estado de la carretilla</div>
    ${items.map(it => {
      if (it.type === 'select') return `<div class="cl-row"><label>${it.label}</label><select id="c_${it.id}" class="fs" style="width:auto;flex:0">${it.opts.map(o => `<option>${o}</option>`).join('')}<option>Otro</option></select></div>`;
      return `<div class="cl-row"><label>${it.label} *</label><div class="radio-group"><label><input type="radio" name="c_${it.id}" value="si"> Sí</label><label><input type="radio" name="c_${it.id}" value="no"> No</label></div></div>`;
    }).join('')}
  </div>`;
}

function formExtintor() {
  return `<div class="clsec"><div class="clsec-h">🧯 Datos del extintor</div>
    <div class="cl-row"><label>Agente extintor *</label><select id="e_agente" class="fs" style="width:auto;flex:0"><option value="">Seleccionar…</option><option>ABC Multipropósito</option><option>CO2</option></select></div>
    <div class="cl-row"><label>Capacidad del extintor *</label><select id="e_capacidad" class="fs" style="width:auto;flex:0"><option>5 lb</option><option>10 lb</option><option>20 lb</option><option>30 lb</option></select></div>
    <div class="cl-row"><label>Fecha vencimiento recarga *</label><input type="date" id="e_vence_recarga" class="fi" style="width:auto;flex:0"></div>
  </div>
  <div class="clsec"><div class="clsec-h">🔍 Estado de componentes</div>
    <div class="cl-row"><label>Pasador de seguridad *</label><div class="radio-group"><label><input type="radio" name="e_pasador" value="bueno"> Bueno</label><label><input type="radio" name="e_pasador" value="malo"> Malo</label></div></div>
    <div class="cl-row"><label>Boquilla / manguera *</label><div class="radio-group"><label><input type="radio" name="e_boquilla" value="bueno"> Bueno</label><label><input type="radio" name="e_boquilla" value="malo"> Malo</label></div></div>
    <div class="cl-row"><label>Manija *</label><div class="radio-group"><label><input type="radio" name="e_manija" value="bueno"> Bueno</label><label><input type="radio" name="e_manija" value="malo"> Malo</label></div></div>
    <div class="cl-row"><label>Cilindro *</label><div class="radio-group"><label><input type="radio" name="e_cilindro" value="bueno"> Bueno</label><label><input type="radio" name="e_cilindro" value="malo"> Malo</label></div></div>
    <div class="cl-row"><label>Pintura *</label><div class="radio-group"><label><input type="radio" name="e_pintura" value="bueno"> Bueno</label><label><input type="radio" name="e_pintura" value="malo"> Malo</label></div></div>
    <div class="cl-row"><label>Señalización, etiqueta, ficha inspección *</label><input type="date" id="e_senalizacion_fecha" class="fi" style="width:auto;flex:0"></div>
    <div class="cl-row"><label>¿El manómetro indica presión adecuada? *</label><div class="radio-group"><label><input type="radio" name="e_manometro" value="si"> Sí</label><label><input type="radio" name="e_manometro" value="no"> No</label></div></div>
    <div class="cl-row"><label>¿El equipo presenta golpes, corrosión, fugas o daños visibles? *</label><div class="radio-group"><label><input type="radio" name="e_danos" value="si"> Sí</label><label><input type="radio" name="e_danos" value="no"> No</label></div></div>
  </div>`;
}

function formCajaFuerte() {
  return `<div class="clsec"><div class="clsec-h">🔐 Inspección Visual Externa Caja Fuerte</div>
    <div class="cl-row"><label>¿La caja fuerte se encuentra en buen estado físico y sin daños visibles? *</label><div class="radio-group"><label><input type="radio" name="cf_buen_estado" value="si"> Sí</label><label><input type="radio" name="cf_buen_estado" value="no"> No</label></div></div>
    <div class="cl-row"><label>¿La caja fuerte se encuentra correctamente ubicada y anclada? *</label><div class="radio-group"><label><input type="radio" name="cf_ubicada_anclada" value="si"> Sí</label><label><input type="radio" name="cf_ubicada_anclada" value="no"> No</label></div></div>
    <div class="cl-row"><label>¿La etiqueta de prevención de la violencia se encuentra instalada? *</label><div class="radio-group"><label><input type="radio" name="cf_etiqueta_prevencion" value="si"> Sí</label><label><input type="radio" name="cf_etiqueta_prevencion" value="no"> No</label></div></div>
    <div class="cl-row"><label>¿La etiqueta de inspección mensual se encuentra instalada y visible? *</label><div class="radio-group"><label><input type="radio" name="cf_etiqueta_inspeccion" value="si"> Sí</label><label><input type="radio" name="cf_etiqueta_inspeccion" value="no"> No</label></div></div>
  </div>`;
}

function formBotonPanico() {
  return `<div class="clsec"><div class="clsec-h">🚨 Inspección de Botón de Pánico</div>
    <div class="cl-row"><label>Fecha de obturación del botón *</label><input type="date" id="bp_obturacion" class="fi" style="width:auto;flex:0"></div>
  </div>`;
}

function formInspeccionVehiculo() {
  return `<div class="clsec"><div class="clsec-h">🚛 Inspección de Vehículo</div>
    <div class="cl-row"><label>¿El vehículo cuenta con tacos de seguridad? *</label><div class="radio-group"><label><input type="radio" name="iv_tacos_seguridad" value="si"> Sí</label><label><input type="radio" name="iv_tacos_seguridad" value="no"> No</label></div></div>
    <div class="cl-row"><label>¿El vehículo cuenta con conos de seguridad en buen estado y en la cantidad requerida? *</label><div class="radio-group"><label><input type="radio" name="iv_conos_seguridad" value="si"> Sí</label><label><input type="radio" name="iv_conos_seguridad" value="no"> No</label></div></div>
    <div class="cl-row"><label>¿El vehículo se encuentra en condiciones adecuadas de orden, limpieza y organización (5S)? *</label><div class="radio-group"><label><input type="radio" name="iv_orden_limpieza" value="si"> Sí</label><label><input type="radio" name="iv_orden_limpieza" value="no"> No</label></div></div>
    <div class="cl-row"><label>¿La silletería del vehículo se encuentra en buen estado, limpia y sin daños? *</label><div class="radio-group"><label><input type="radio" name="iv_silleteria" value="si"> Sí</label><label><input type="radio" name="iv_silleteria" value="no"> No</label></div></div>
  </div>`;
}

function cerrarModal() { document.getElementById('overlay').classList.remove('vis'); _fotosTemp = []; }

function abrirLightbox(src) {
  const lb = document.createElement('div');
  lb.className = 'foto-lightbox';
  lb.innerHTML = `<img src="${src}">`;
  lb.onclick = () => lb.remove();
  document.body.appendChild(lb);
}

// ═══════════════════════════════════
//  GUARDAR
// ═══════════════════════════════════
async function guardar() {
  const placa = document.getElementById('f_placa').value;
  const inspector = document.getElementById('f_inspector').value;
  const fecha = document.getElementById('f_fecha').value;
  const obs = document.getElementById('f_obs').value.trim();
  if (!inspector) return toast('Selecciona un inspector', 1);
  if (!fecha) return toast('Selecciona la fecha', 1);

  let items = {};

  if (modalTipo === 'botiquin') {
    const dateFields = ['gasas', 'esparadrapo', 'bajalenguas', 'guantes_latex', 'venda_2x5', 'venda_3x5', 'venda_5x5', 'yodopovidona', 'solucion_salina', 'alcohol', 'botella_agua', 'bandas_adhesivas'];
    const radioFields = ['termometro', 'tijeras', 'pito'];
    dateFields.forEach(f => { const v = document.getElementById('b_' + f).value; items[f.replace(/_/g, ' ')] = v || ''; });
    radioFields.forEach(f => { const r = document.querySelector(`input[name="b_${f}"]:checked`); items[f] = r ? r.value === 'cumple' : false; });
  }
  else if (modalTipo === 'carretilla') {
    items['Número carretilla'] = document.getElementById('c_num_carretilla').value;
    const radioFields = ['manijas', 'partes_sueltas', 'llantas_estado', 'llantas_giran', 'rodamientos', 'pintura', 'sticker_placa', 'sticker_capacidad'];
    const invertFields = ['partes_sueltas'];
    radioFields.forEach(f => {
      const r = document.querySelector(`input[name="c_${f}"]:checked`);
      if (!r) { items[f.replace(/_/g, ' ')] = false; return; }
      const val = r.value === 'si';
      items[f.replace(/_/g, ' ')] = invertFields.includes(f) ? !val : val;
    });
  }
  else if (modalTipo === 'extintor') {
    items['Agente extintor'] = document.getElementById('e_agente').value;
    items['Capacidad'] = document.getElementById('e_capacidad').value;
    const vr = document.getElementById('e_vence_recarga').value;
    if (vr) items['Vencimiento recarga'] = vr;
    const sf = document.getElementById('e_senalizacion_fecha').value;
    if (sf) items['Señalización fecha'] = sf;

    const buenos = ['pasador', 'boquilla', 'manija', 'cilindro', 'pintura'];
    buenos.forEach(f => { const r = document.querySelector(`input[name="e_${f}"]:checked`); items[f] = r ? r.value === 'bueno' : false; });

    const r1 = document.querySelector('input[name="e_manometro"]:checked');
    items['Manómetro presión adecuada'] = r1 ? r1.value === 'si' : false;
    const r2 = document.querySelector('input[name="e_danos"]:checked');
    items['Sin daños visibles'] = r2 ? r2.value === 'no' : false;
  }
  else if (modalTipo === 'caja_fuerte') {
    const campos = ['buen_estado', 'ubicada_anclada', 'etiqueta_prevencion', 'etiqueta_inspeccion'];
    campos.forEach(f => {
      const r = document.querySelector(`input[name="cf_${f}"]:checked`);
      items[f.replace(/_/g, ' ')] = r ? r.value === 'si' : false;
    });
  }
  else if (modalTipo === 'boton_panico') {
    const ob = document.getElementById('bp_obturacion').value;
    items['obturacion boton'] = ob || '';
  }
  else if (modalTipo === 'inspeccion_vehiculo') {
    const campos = ['tacos_seguridad', 'conos_seguridad', 'orden_limpieza', 'silleteria'];
    campos.forEach(f => {
      const r = document.querySelector(`input[name="iv_${f}"]:checked`);
      items[f.replace(/_/g, ' ')] = r ? r.value === 'si' : false;
    });
  }

  const now = new Date();
  const hora = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

  try {
    await apiPost('inspecciones.php', {
      tipo: modalTipo,
      placa,
      inspector,
      fecha,
      hora,
      observaciones: obs,
      items,
      fotos: _fotosTemp
    });
    cerrarModal();
    await init();
    toast('Inspección guardada ✓');
  } catch (e) {
    toast(e.message, 1);
  }
}

// ═══════════════════════════════════
//  DASHBOARD
// ═══════════════════════════════════
function renderDash() {
  const f = getFlota(), tot = f.length, insps = getInsps();

  const tipos = TIPOS_INSP;

  const resumen = {};

  tipos.forEach(tipo => {
    let cumplen = 0, noCumplen = 0, sinInspeccion = 0;
    const placasNoCumplen = [];

    f.forEach(v => {
      const ev = evalInsp(getLastInsp(v.placa, tipo.key));
      if (ev.estado === 'ok') cumplen++;
      else if (ev.estado === 'fail') {
        noCumplen++;
        placasNoCumplen.push({ placa: v.placa, porcentaje: ev.pct, zona: v.zona || '', marca: v.marca || '' });
      } else sinInspeccion++;
    });

    resumen[tipo.key] = { cumplen, noCumplen, sinInspeccion, placasNoCumplen };
  });

  let vehiculosCumplenTodo = 0, vehiculosConIncumplimiento = 0, vehiculosSinInspeccion = 0;

  f.forEach(v => {
    const evaluaciones = tipos.map(t => evalInsp(getLastInsp(v.placa, t.key)));
    const tieneIncumplimiento = evaluaciones.some(e => e.estado === 'fail');
    const cumpleTodo = evaluaciones.every(e => e.estado === 'ok');

    if (cumpleTodo) vehiculosCumplenTodo++;
    else if (tieneIncumplimiento) vehiculosConIncumplimiento++;
    else vehiculosSinInspeccion++;
  });

  document.getElementById('kpis2').innerHTML = `
    <div class="kpi"><div class="kpi-l">Total vehículos</div><div class="kpi-v">${tot}</div><div class="kpi-s">vehículos registrados</div></div>
    <div class="kpi"><div class="kpi-l">Cumplen todo</div><div class="kpi-v g">${vehiculosCumplenTodo}</div><div class="kpi-s">${tot ? Math.round(vehiculosCumplenTodo / tot * 100) : 0}% de la flota</div></div>
    <div class="kpi"><div class="kpi-l">Con incumplimientos</div><div class="kpi-v r">${vehiculosConIncumplimiento}</div><div class="kpi-s">requieren atención</div></div>
    <div class="kpi"><div class="kpi-l">Sin inspección</div><div class="kpi-v a">${vehiculosSinInspeccion}</div><div class="kpi-s">pendientes</div></div>
    <div class="kpi"><div class="kpi-l">Inspecciones realizadas</div><div class="kpi-v">${insps.length}</div><div class="kpi-s">registros históricos</div></div>`;

  document.getElementById('dashTipos').innerHTML = tipos.map(tipo => {
    const r = resumen[tipo.key];
    const pct = tot ? Math.round(r.cumplen / tot * 100) : 0;
    return `
      <div class="inspection-card">
        <div class="inspection-card-head">
          <div class="inspection-icon ${tipo.cls}">${tipo.icon}</div>
          <div><div class="inspection-name">${tipo.label}</div><div class="inspection-total">${tot} vehículos</div></div>
        </div>
        <div style="font-size:1.5rem;font-weight:800;color:var(--green);margin-bottom:12px">${pct}% <span style="font-size:.7rem;font-weight:600;color:var(--muted)">cumplimiento</span></div>
        <div class="inspection-stats">
          <div class="inspection-stat ok clickable" onclick="mostrarEstado('${tipo.key}','ok')" title="Ver vehículos que cumplen">
            <div class="num">${r.cumplen}</div>
            <div class="lbl">Cumplen</div>
          </div>
          <div class="inspection-stat fail clickable" onclick="mostrarEstado('${tipo.key}','fail')" title="Ver vehículos que no cumplen">
            <div class="num">${r.noCumplen}</div>
            <div class="lbl">No cumplen</div>
          </div>
          <div class="inspection-stat none clickable" onclick="mostrarEstado('${tipo.key}','none')" title="Ver vehículos sin inspección">
            <div class="num">${r.sinInspeccion}</div>
            <div class="lbl">Sin inspección</div>
          </div>
        </div>
      </div>`;
  }).join('');

  document.getElementById('dashNoCumplen').innerHTML = `<div class="no-cumplen-empty">Selecciona <b>"No cumplen"</b> en una de las inspecciones para ver las placas correspondientes.</div>`;

  const venc = [], hoy = new Date();
  f.forEach(v => {
    tipos.forEach(tipo => {
      const insp = getLastInsp(v.placa, tipo.key);
      if (!insp) return;
      Object.entries(insp.items || {}).forEach(([k, val]) => {
        if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
          const d = new Date(val + 'T00:00:00'), dias = Math.ceil((d - hoy) / 864e5);
          if (dias <= 30) venc.push({ placa: v.placa, elem: `${tipo.label}: ${k}`, fecha: val, dias, estado: dias < 0 ? 'fail' : dias <= 15 ? 'warn' : 'ok' });
        }
      });
    });
  });

  venc.sort((a, b) => a.dias - b.dias);
  document.getElementById('dashVenc').innerHTML = venc.length ? venc.map(v => `<tr><td class="placa-cell">${v.placa}</td><td>${v.elem}</td><td>${v.fecha}</td><td style="font-weight:700">${v.dias < 0 ? v.dias + ' (vencido)' : v.dias}</td><td><span class="badge ${v.estado}">${v.dias < 0 ? 'Vencido' : v.dias <= 15 ? 'Próximo' : 'Vigente'}</span></td></tr>`).join('') : '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:18px">Sin vencimientos próximos</td></tr>';
}

function mostrarEstado(tipo, estado) {
  const nombres = {};
  TIPOS_INSP.forEach(t => { nombres[t.key] = t.icon + ' ' + t.label; });

  const estadoInfo = {
    ok: { titulo: 'Cumplen', color: 'var(--green)', icono: '✓' },
    fail: { titulo: 'No cumplen', color: 'var(--red)', icono: '✕' },
    none: { titulo: 'Sin inspección', color: 'var(--muted)', icono: '—' }
  };

  const info = estadoInfo[estado];
  const flota = getFlota();

  const vehiculos = flota.filter(v => {
    const insp = getLastInsp(v.placa, tipo);
    const ev = evalInsp(insp);
    return ev.estado === estado;
  });

  const contenedor = document.getElementById('dashNoCumplen');

  if (!vehiculos.length) {
    contenedor.innerHTML = `
      <div class="no-cumplen-panel" style="border-color:${info.color}33;background:var(--card-alt)">
        <div class="no-cumplen-title" style="color:${info.color}">${nombres[tipo]} · ${info.titulo}</div>
        <div class="no-cumplen-empty" style="color:${info.color}">No hay vehículos en esta categoría.</div>
      </div>`;
    return;
  }

  contenedor.innerHTML = `
    <div class="no-cumplen-panel" style="border-color:${info.color}33;background:var(--card-alt)">
      <div class="no-cumplen-title" style="color:${info.color}">${nombres[tipo]} · ${info.titulo} — ${vehiculos.length} vehículo(s)</div>
      <div class="no-cumplen-list">
        ${vehiculos.map(v => {
          const insp = getLastInsp(v.placa, tipo);
          const ev = evalInsp(insp);
          let detalle = estado === 'none' ? 'Pendiente' : `${ev.pct}%`;
          return `
            <div class="no-cumplen-placa" title="${v.marca || ''} ${v.zona || ''}">
              <span style="color:${info.color};font-weight:800">${info.icono}</span>
              <span class="placa-mini">${v.placa}</span>
              <span class="motivo" style="color:${info.color}">${detalle}</span>
            </div>`;
        }).join('')}
      </div>
    </div>`;
}

// ═══════════════════════════════════
//  HISTORIAL
// ═══════════════════════════════════
function setHistFilt(f, btn) { histFilt = f; document.querySelectorAll('#tab-historial .chip').forEach(c => c.classList.remove('on')); btn.classList.add('on'); renderHist(); }

function renderHist() {
  const insps = getInsps().filter(i => histFilt === 'todas' || i.tipo === histFilt).slice().reverse();
  const tb = document.getElementById('tbHist');
  if (!insps.length) { tb.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:18px">Sin inspecciones</td></tr>'; return; }
  const tipoLabels = {}; const tipoCls = {};
  TIPOS_INSP.forEach(t => { tipoLabels[t.key] = t.icon + ' ' + t.label; tipoCls[t.key] = t.cls; });
  tb.innerHTML = insps.map(i => {
    const ev = evalInsp(i);
    return `<tr>
      <td>${i.fecha} ${i.hora || ''}</td>
      <td><span class="tbadge ${tipoCls[i.tipo]}">${tipoLabels[i.tipo]}</span></td>
      <td class="placa-cell">${i.placa}</td>
      <td>${i.inspector}</td>
      <td><span class="badge ${ev.estado}">${ev.pct}%</span></td>
      <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(i.observaciones || '')}">${i.observaciones || '—'}</td>
    </tr>`;
  }).join('');
}

// ═══════════════════════════════════
//  EXPORTAR EXCEL
// ═══════════════════════════════════
function descargarExcel() {
  let url = 'api/exportar.php?';
  const params = [];
  const desde = document.getElementById('expDesde').value;
  const hasta = document.getElementById('expHasta').value;
  if (desde) params.push('desde=' + desde);
  if (hasta) params.push('hasta=' + hasta);
  if (histFilt !== 'todas') params.push('tipo=' + histFilt);
  url += params.join('&');
  window.open(url, '_blank');
}

// ═══════════════════════════════════
//  CANVAS BAR CHART
// ═══════════════════════════════════
function drawBarChart(canvasId, data) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const dpr = window.devicePixelRatio || 1;
  const rect = canvas.getBoundingClientRect();
  canvas.width = rect.width * dpr;
  canvas.height = rect.height * dpr;
  const ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);
  const W = rect.width, H = rect.height;

  const maxVal = Math.max(...data.map(d => d.value), 1);
  const padL = 10, padR = 10, padT = 30, padB = 50;
  const chartW = W - padL - padR;
  const chartH = H - padT - padB;
  const barCount = data.length;
  const gap = 16;
  const barW = Math.min(70, (chartW - gap * (barCount + 1)) / barCount);
  const totalBarsW = barCount * barW + (barCount - 1) * gap;
  const startX = padL + (chartW - totalBarsW) / 2;

  const cs = getComputedStyle(document.documentElement);
  const textCol = cs.getPropertyValue('--muted').trim() || '#8891A2';
  const lineCol = cs.getPropertyValue('--border-lt').trim() || '#E9E6E1';
  const mainText = cs.getPropertyValue('--text').trim() || '#2C3342';

  const gridLines = 5;
  ctx.strokeStyle = lineCol;
  ctx.lineWidth = 1;
  ctx.font = `600 11px "Segoe UI", system-ui, sans-serif`;
  ctx.fillStyle = textCol;
  ctx.textAlign = 'right';
  for (let i = 0; i <= gridLines; i++) {
    const y = padT + chartH - (chartH / gridLines) * i;
    ctx.beginPath();
    ctx.setLineDash([3, 3]);
    ctx.moveTo(padL, y);
    ctx.lineTo(W - padR, y);
    ctx.stroke();
    ctx.setLineDash([]);
  }

  data.forEach((d, i) => {
    const x = startX + i * (barW + gap);
    const barH = (d.value / maxVal) * chartH;
    const y = padT + chartH - barH;

    ctx.fillStyle = 'rgba(0,0,0,0.04)';
    ctx.beginPath();
    ctx.roundRect(x + 2, y + 2, barW, barH, [5, 5, 0, 0]);
    ctx.fill();

    ctx.fillStyle = d.color;
    ctx.beginPath();
    ctx.roundRect(x, y, barW, barH, [5, 5, 0, 0]);
    ctx.fill();

    ctx.fillStyle = mainText;
    ctx.font = `800 14px "Segoe UI", system-ui, sans-serif`;
    ctx.textAlign = 'center';
    ctx.fillText(d.value, x + barW / 2, y - 8);

    ctx.fillStyle = textCol;
    ctx.font = `600 10px "Segoe UI", system-ui, sans-serif`;
    const lines = d.label.split(' ');
    lines.forEach((line, li) => {
      ctx.fillText(line, x + barW / 2, padT + chartH + 16 + li * 13);
    });
  });
}

// ═══════════════════════════════════
//  UTILS
// ═══════════════════════════════════
function esc(s) { return s.replace(/'/g, "\\'").replace(/"/g, '&quot;') }

function toast(msg, err) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.style.background = err ? 'var(--red)' : 'var(--green)';
  t.classList.add('show'); setTimeout(() => t.classList.remove('show'), 2500);
}

// ═══════════════════════════════════
//  ARRANCAR
// ═══════════════════════════════════
init();
