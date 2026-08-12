<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Control de Inspecciones — CONALCA</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="sidebar">
  <div class="sb-brand sb-brand-click" onclick="switchTab('home', document.querySelector('.sb-item[data-tab=home]'))">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 18h10M9 21h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M8 9h3M8 12h5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity=".5"/><circle cx="17" cy="9" r="2" stroke="currentColor" stroke-width="1.2"/></svg>
    <span>CONALCA</span>
  </div>

  <nav class="sb-nav">
    <div class="sb-section">PRINCIPAL</div>
    <button class="sb-item on" data-tab="home" onclick="switchTab('home',this)">
      <span class="sb-icon">🏠</span> Inicio
    </button>
    <button class="sb-item" data-tab="vehiculos" onclick="switchTab('vehiculos',this)">
      <span class="sb-icon">🚛</span> Vehículos
      <span class="sb-arrow">▾</span>
    </button>
    <div class="sb-sub" id="sbSubVeh">
      <button class="sb-subitem on" onclick="setZonaFilt('todos',this)">Todos</button>
      <button class="sb-subitem" onclick="setZonaFilt('Siberia',this)">📍 Siberia</button>
      <button class="sb-subitem" onclick="setZonaFilt('Chapinero',this)">📍 Chapinero</button>
      <button class="sb-subitem" onclick="setZonaFilt('Engativa',this)">📍 Engativá</button>
    </div>
    <button class="sb-item" data-tab="dashboard" onclick="switchTab('dashboard',this)">
      <span class="sb-icon">📊</span> Dashboard
    </button>
    <button class="sb-item" data-tab="historial" onclick="switchTab('historial',this)">
      <span class="sb-icon">📋</span> Historial
    </button>
    <button class="sb-item" data-tab="config" onclick="switchTab('config',this)">
      <span class="sb-icon">⚙️</span> Configuración
    </button>
  </nav>

  <div class="sb-footer">
    <button class="btn-theme" id="btnTheme" onclick="toggleTheme()" title="Cambiar tema">🌙</button>
    <span class="sb-date" id="fechaHoy"></span>
  </div>
</aside>

<!-- ═══ HEADER MOBILE ═══ -->
<header class="topbar-mobile" id="topbarMobile">
  <button class="sb-toggle" onclick="toggleSidebar()">☰</button>
  <span class="topbar-brand-m">CONALCA</span>
  <div class="topbar-right-m">
    <button class="btn-theme" id="btnThemeMobile" onclick="toggleTheme()" title="Cambiar tema">🌙</button>
  </div>
</header>

<!-- ═══ OVERLAY MOBILE (cierra sidebar) ═══ -->
<div class="sb-overlay" id="sbOverlay" onclick="toggleSidebar()"></div>

<!-- ═══ CONTENIDO PRINCIPAL ═══ -->
<div class="main-wrap">
  <div class="main-header">
    <div class="dropdown" id="ddInsp">
      <button class="btn btn-amber" onclick="toggleDD()">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a1 1 0 011 1v5h5a1 1 0 110 2H9v5a1 1 0 11-2 0V9H2a1 1 0 010-2h5V2a1 1 0 011-1z"/></svg>
        Nueva inspección
      </button>
      <div class="dropdown-menu">
        <button class="dropdown-item" onclick="abrirModal('botiquin')"><span class="icon">🩹</span> Inspección de Botiquín</button>
        <button class="dropdown-item" onclick="abrirModal('carretilla')"><span class="icon">🛒</span> Inspección de Carretilla</button>
        <button class="dropdown-item" onclick="abrirModal('extintor')"><span class="icon">🧯</span> Inspección de Extintor</button>
        <button class="dropdown-item" onclick="abrirModal('caja_fuerte')"><span class="icon">🔐</span> Inspección de Caja Fuerte</button>
        <button class="dropdown-item" onclick="abrirModal('boton_panico')"><span class="icon">🚨</span> Inspección de Botón de Pánico</button>
        <button class="dropdown-item" onclick="abrirModal('inspeccion_vehiculo')"><span class="icon">🚛</span> Inspección de Vehículo</button>
      </div>
    </div>
  </div>

  <div class="main">

  <!-- ═══ TAB: HOME ═══ -->
  <div class="tab on" id="tab-home">
    <h2 class="home-title">Panel de Control — CONALCA</h2>
    <p class="home-subtitle" id="homeDate"></p>

    <h3 class="home-zone-title">📍 Siberia</h3>
    <div class="home-charts">
      <div class="home-card"><h3>Inspecciones realizadas</h3><canvas id="pieSib_Insp" width="220" height="220"></canvas><div class="home-legend" id="legSib_Insp"></div></div>
      <div class="home-card"><h3>Vehículos con alertas</h3><canvas id="pieSib_Warn" width="220" height="220"></canvas><div class="home-legend" id="legSib_Warn"></div></div>
      <div class="home-card"><h3>Vehículos con vencimientos</h3><canvas id="pieSib_Fail" width="220" height="220"></canvas><div class="home-legend" id="legSib_Fail"></div></div>
    </div>

    <h3 class="home-zone-title">📍 Chapinero</h3>
    <div class="home-charts">
      <div class="home-card"><h3>Inspecciones realizadas</h3><canvas id="pieChap_Insp" width="220" height="220"></canvas><div class="home-legend" id="legChap_Insp"></div></div>
      <div class="home-card"><h3>Vehículos con alertas</h3><canvas id="pieChap_Warn" width="220" height="220"></canvas><div class="home-legend" id="legChap_Warn"></div></div>
      <div class="home-card"><h3>Vehículos con vencimientos</h3><canvas id="pieChap_Fail" width="220" height="220"></canvas><div class="home-legend" id="legChap_Fail"></div></div>
    </div>

    <h3 class="home-zone-title">📍 Engativá</h3>
    <div class="home-charts">
      <div class="home-card"><h3>Inspecciones realizadas</h3><canvas id="pieEng_Insp" width="220" height="220"></canvas><div class="home-legend" id="legEng_Insp"></div></div>
      <div class="home-card"><h3>Vehículos con alertas</h3><canvas id="pieEng_Warn" width="220" height="220"></canvas><div class="home-legend" id="legEng_Warn"></div></div>
      <div class="home-card"><h3>Vehículos con vencimientos</h3><canvas id="pieEng_Fail" width="220" height="220"></canvas><div class="home-legend" id="legEng_Fail"></div></div>
    </div>
  </div>

  <!-- ═══ TAB: VEHÍCULOS ═══ -->
  <div class="tab" id="tab-vehiculos">
    <div class="kpis" id="kpis1"></div>
    <div class="fbar">
      <input type="search" class="search" placeholder="Buscar placa, marca o zona…" id="searchBox" oninput="filtrar()">
      <button class="chip on" onclick="setFilt('todos',this)">Todos</button>
      <button class="chip" onclick="setFilt('ok',this)">✓ Cumple</button>
      <button class="chip" onclick="setFilt('warn',this)">⚠ Alerta</button>
      <button class="chip" onclick="setFilt('fail',this)">✕ Incumple</button>
      <span class="fbar-count" id="fcount"></span>
    </div>
    <div class="vgrid" id="vgrid"></div>
    <div class="empty" id="emptyV" style="display:none">
      <h3>Sin vehículos</h3>
      <p>Ve a Configuración para agregar placas y comenzar las inspecciones.</p>
    </div>
  </div>

  <!-- ═══ TAB: DASHBOARD ═══ -->
  <div class="tab" id="tab-dashboard">
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
      <button class="btn btn-navy btn-sm" onclick="descargarDashExcel()">📥 Descargar informe por tipo</button>
    </div>
    <div class="kpis" id="kpis2"></div>
    <div class="dcard" style="margin-bottom:14px">
      <h3>Cumplimiento por tipo de inspección</h3>
      <div id="dashTipos" class="inspection-dashboard"></div>
    </div>
    <div class="dcard" style="margin-bottom:14px">
      <h3>Vehículos que no cumplen</h3>
      <div id="dashNoCumplen"></div>
    </div>
    <div class="dcard">
      <h3>Vencimientos próximos (30 días)</h3>
      <div class="tw" style="max-height:280px;overflow-y:auto">
        <table class="tbl">
          <thead><tr><th>Placa</th><th>Elemento</th><th>Vcto.</th><th>Días</th><th></th></tr></thead>
          <tbody id="dashVenc"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══ TAB: HISTORIAL ═══ -->
  <div class="tab" id="tab-historial">
    <div class="fbar" style="margin-bottom:12px;flex-wrap:wrap;gap:8px">
      <button class="chip on" onclick="setHistFilt('todas',this)">Todas</button>
      <button class="chip" onclick="setHistFilt('botiquin',this)">🩹 Botiquines</button>
      <button class="chip" onclick="setHistFilt('carretilla',this)">🛒 Carretillas</button>
      <button class="chip" onclick="setHistFilt('extintor',this)">🧯 Extintores</button>
      <button class="chip" onclick="setHistFilt('caja_fuerte',this)">🔐 Caja Fuerte</button>
      <button class="chip" onclick="setHistFilt('boton_panico',this)">🚨 Botón Pánico</button>
      <button class="chip" onclick="setHistFilt('inspeccion_vehiculo',this)">🚛 Vehículo</button>
    </div>
    <div class="fbar" style="margin-bottom:12px;flex-wrap:wrap;gap:8px;align-items:center">
      <label style="font-size:.78rem;font-weight:600;color:var(--text2)">Desde:</label>
      <input type="date" id="expDesde" class="fi" style="width:auto;padding:4px 8px;font-size:.78rem">
      <label style="font-size:.78rem;font-weight:600;color:var(--text2)">Hasta:</label>
      <input type="date" id="expHasta" class="fi" style="width:auto;padding:4px 8px;font-size:.78rem">
      <button class="btn btn-navy btn-sm" onclick="descargarExcel()" style="margin-left:auto">📥 Descargar Excel</button>
    </div>
    <div class="csec">
      <div class="tw"><table class="tbl" id="tblHist">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Placa</th><th>Inspector</th><th>Resultado</th><th>Observaciones</th></tr></thead>
        <tbody id="tbHist"></tbody>
      </table></div>
    </div>
  </div>

  <!-- ═══ TAB: CONFIG ═══ -->
  <div class="tab" id="tab-config">
    <div class="csec">
      <h3>Agregar vehículo / equipo</h3>
      <div class="crow">
        <input type="text" class="pi" placeholder="PLACA" id="inPlaca" maxlength="12">
        <input type="text" class="ni" placeholder="Marca (ej: INTERNATIONAL)" id="inMarca">
        <input type="text" class="ni" placeholder="Línea (ej: MV607 SBA 4X2)" id="inLinea">
      </div>
      <div class="crow">
        <select class="fi" id="inTipo" style="width:auto"><option>Sencillo</option><option>Camioneta</option><option>Doble troque</option></select>
        <input type="number" class="fi" placeholder="Año" id="inAño" style="width:80px" min="2000" max="2030">
        <input type="text" class="ni" placeholder="Zona de trabajo" id="inZona">
        <button class="btn btn-navy btn-sm" onclick="addVeh()">Agregar</button>
      </div>
    </div>
    <div class="csec">
      <h3>Inspectores</h3>
      <div class="crow">
        <input type="text" class="ni" placeholder="Nombre del inspector" id="inInsp">
        <button class="btn btn-navy btn-sm" onclick="addInsp()">Agregar</button>
      </div>
      <div id="listaInsp"></div>
    </div>
    <div class="csec">
      <h3>Flota registrada</h3>
      <div id="listaFlota"></div>
    </div>
  </div>
  </div>
</div>

<!-- ═══ MODAL ═══ -->
<div class="overlay" id="overlay" onclick="if(event.target===this)cerrarModal()">
  <div class="mpanel">
    <div class="m-head">
      <span id="mIcon" style="font-size:1.2rem"></span>
      <h2 id="mTitle"></h2>
      <button class="m-close" onclick="cerrarModal()">✕</button>
    </div>
    <div class="m-body" id="mBody"></div>
    <div class="m-foot">
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="btn btn-green" onclick="guardar()">Guardar inspección</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="js/app.js"></script>
</body>
</html>
