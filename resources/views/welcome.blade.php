<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MediRecomienda — Encuentra el profesional médico adecuado</title>
    <link rel="icon" type="image/png" href="/recomienda.png">
    <link rel="apple-touch-icon" href="/recomienda.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

        body { background: #f1f5f9; min-height: 100vh; color: #1e293b; }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 20px rgba(0,0,0,0.04);
        }

        /* ── Fields ── */
        .field {
            width: 100%; border-radius: 8px;
            border: 1px solid #e2e8f0; background: #fff;
            padding: 9px 12px; font-size: 13.5px; color: #1e293b;
            transition: border-color .15s, box-shadow .15s;
        }
        .field::placeholder { color: #94a3b8; }
        .field:focus { outline: none; border-color: #0891b2; box-shadow: 0 0 0 3px rgba(8,145,178,0.1); }
        select.field {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 14px;
            padding-right: 30px;
        }

        #sintomas { font-size: 14px; min-height: 150px; resize: vertical; line-height: 1.65; }
        #sintomas:focus { outline: none; border-color: #0891b2; box-shadow: 0 0 0 3px rgba(8,145,178,0.1); }

        /* ── Labels ── */
        .flabel { display: block; font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 5px; }

        /* ── Chips síntomas ── */
        .chip {
            display: inline-flex; align-items: center; white-space: nowrap;
            padding: 4px 10px; border-radius: 9999px;
            font-size: 12px; font-weight: 500;
            border: 1px solid #e2e8f0; cursor: pointer;
            transition: all .13s; background: #f8fafc; color: #64748b;
            user-select: none; flex-shrink: 0;
        }
        .chip:hover  { border-color: #0891b2; color: #0e7490; background: #f0fdff; }
        .chip.active { border-color: #0891b2; color: #0e7490; background: #ecfeff; }

        /* ── Chips antecedentes ── */
        .chip-antec {
            display: inline-flex; align-items: center; white-space: nowrap;
            padding: 3px 9px; border-radius: 9999px;
            font-size: 11.5px; font-weight: 500;
            border: 1px solid #e2e8f0; cursor: pointer;
            transition: all .13s; background: #f8fafc; color: #64748b;
            user-select: none;
        }
        .chip-antec:hover  { border-color: #059669; color: #047857; background: #f0fdf4; }
        .chip-antec.active { border-color: #059669; color: #047857; background: #dcfce7; }

        /* ── Chips scroll mobile ── */
        .chips-scroll { display: flex; flex-wrap: wrap; gap: 5px; }
        @media (max-width: 640px) {
            .chips-scroll { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px;
                -webkit-overflow-scrolling: touch; scrollbar-width: none; }
            .chips-scroll::-webkit-scrollbar { display: none; }
        }

        /* ── Gender cards ── */
        .gender-card {
            flex: 1; padding: 8px 10px; border-radius: 9px;
            border: 1px solid #e2e8f0; background: #f8fafc;
            cursor: pointer; transition: all .13s;
            display: flex; align-items: center; gap: 6px;
            color: #64748b; font-size: 13px; font-weight: 600;
        }
        .gender-card:hover { border-color: #0891b2; background: #f0fdff; color: #0e7490; }
        .gender-card.sel-m { border-color: #0891b2; background: #ecfeff; color: #0e7490; }
        .gender-card.sel-f { border-color: #db2777; background: #fdf2f8; color: #be185d; }

        /* ── Divider ── */
        .divider { height: 1px; background: #f1f5f9; }

        /* ── Button ── */
        .btn-primary {
            background: #0891b2; color: #fff;
            border: none; border-radius: 9px;
            padding: 11px 20px; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: background .13s, transform .1s;
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            width: 100%;
        }
        .btn-primary:hover    { background: #0e7490; }
        .btn-primary:active   { transform: scale(.98); }
        .btn-primary:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }

        /* ── Animations ── */
        @keyframes spin   { to { transform: rotate(360deg); } }
        @keyframes fadeUp { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
        @keyframes pulse  { 0%,100%{opacity:1;} 50%{opacity:.45;} }
        .spin    { animation: spin .8s linear infinite; }
        .fade-up { animation: fadeUp .25s ease forwards; }
        .pulse   { animation: pulse 2s ease infinite; }

        /* ── Result card ── */
        .result-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03); transition: border-color .18s;
        }
        .result-card:hover { border-color: #bae6fd; }

        /* ── Doc avatar ── */
        .doc-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: #f0f9ff; border: 1px solid #bae6fd;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* ── Urgencia badges ── */
        .urg-normal     { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .urg-urgente    { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
        .urg-critico    { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .urg-emergencia { background:#7f1d1d; color:#fef2f2; border:1px solid #dc2626; }

        /* ── Emergency banner ── */
        @keyframes emergPulse { 0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(220,38,38,.4);} 50%{opacity:.92;box-shadow:0 0 0 12px rgba(220,38,38,0);} }
        .emergency-banner { border-radius:16px; background:linear-gradient(135deg,#7f1d1d 0%,#991b1b 100%); color:#fff; padding:28px 24px; text-align:center; animation:emergPulse 2s ease infinite; }

        /* ── Position badges ── */
        .badge-1 { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
        .badge-2 { background:#f8fafc; color:#475569; border:1px solid #e2e8f0;  }
        .badge-3 { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;  }

        /* ── Result summary block ── */
        .resumen-block {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px;
        }

        /* ── Mapa ── */
        #mapaResultados { z-index: 0; }
        .leaflet-popup-content { font-size: 13px; line-height: 1.5; }
        .mapa-lbl { font-size: 11px; font-weight: 600; color: #94a3b8;
            text-align: center; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .08em; }

        /* ── Mobile ── */
        @media (max-width: 640px) {
            .hero-h1 { font-size: 24px !important; line-height: 1.25; }
            #sintomas { min-height: 120px; }
        }
    </style>
</head>
<body>

    <!-- ═══ HEADER ═══ -->
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95" style="backdrop-filter:blur(12px);">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <img src="/recomienda.png" alt="MediRecomienda" class="w-8 h-8 rounded-xl object-cover flex-shrink-0">
                <div>
                    <span class="text-sm font-bold text-slate-800 tracking-tight leading-none">MediRecomienda</span>
                    <span class="hidden sm:block text-[10px] font-medium text-slate-400 leading-none mt-0.5">Orientación médica por síntomas</span>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-full">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
                <span class="text-xs font-medium text-slate-500">Orientación, no diagnóstico</span>
            </div>
        </div>
    </header>

    <!-- ═══ MAIN ═══ -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 pt-8 pb-16">

        <!-- Hero -->
        <div class="text-center mb-6">
            <h1 class="hero-h1 text-[32px] sm:text-[36px] font-extrabold text-slate-800 leading-tight tracking-tight mb-2">
                Encuentra el profesional médico<br>
                <span style="background:linear-gradient(135deg,#0891b2,#0d9488);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                    adecuado para tus síntomas
                </span>
            </h1>
            <p class="text-slate-500 text-sm max-w-lg mx-auto leading-relaxed">
                Describe lo que sientes. Analizamos los síntomas y mostramos opciones médicas cercanas —
                especialistas y médicos generales.
            </p>
        </div>

        <!-- ═══ FORMULARIO ═══ -->
        <div class="card p-5 sm:p-6 mb-5">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">

                <!-- Columna izquierda — síntomas -->
                <div class="flex-1 min-w-0 flex flex-col">
                    <label for="sintomas" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Describe los síntomas o motivo de consulta
                        <span class="font-normal text-slate-400 ml-1" style="font-size:11px;">*obligatorio</span>
                    </label>
                    <textarea
                        id="sintomas" rows="5" maxlength="500"
                        placeholder="Ejemplo: llevo 2 días con dolor de cabeza intenso, fiebre y cansancio general..."
                        class="field flex-1"
                    ></textarea>
                    <div class="flex items-center justify-between mt-1.5 mb-3">
                        <span class="text-xs text-slate-400">Cuanto más detallado, mejor la orientación</span>
                        <span id="contador" class="text-xs text-slate-400 tabular-nums">0 / 500</span>
                    </div>

                    <!-- Chips síntomas frecuentes — sin emoticonos -->
                    <div>
                        <span class="flabel mb-1.5">Síntomas frecuentes — toca para añadir al campo</span>
                        <div id="chips" class="chips-scroll">
                            @foreach ([
                                'Fiebre','Dolor de cabeza','Tos','Falta de aire',
                                'Náuseas','Dolor de pecho','Dolor muscular','Cansancio',
                                'Dolor abdominal','Mareos','Escalofríos','Tristeza o ansiedad',
                            ] as $label)
                            <button type="button" class="chip" data-sintoma="{{ $label }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Divisor vertical — solo desktop -->
                <div class="hidden lg:block w-px bg-slate-100 flex-shrink-0 self-stretch"></div>

                <!-- Columna derecha — datos + CTA -->
                <div class="lg:w-56 xl:w-60 flex flex-col gap-3 flex-shrink-0">

                    <!-- Sexo biológico -->
                    <div>
                        <span class="flabel">Sexo biológico</span>
                        <div class="flex gap-2">
                            <button type="button" class="gender-card" id="genderMale" onclick="seleccionarGenero('masculino')">
                                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="10" cy="14" r="5"/><path d="M19 5l-5 5M19 5h-4M19 5v4"/>
                                </svg>Hombre
                            </button>
                            <button type="button" class="gender-card" id="genderFemale" onclick="seleccionarGenero('femenino')">
                                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="10" r="5"/><path d="M12 15v6M9 18h6"/>
                                </svg>Mujer
                            </button>
                            <input type="hidden" id="sexo" value="">
                        </div>
                    </div>

                    <!-- Edad -->
                    <div>
                        <label for="edad" class="flabel">Edad</label>
                        <input id="edad" type="number" min="0" max="120" placeholder="Años" class="field">
                    </div>

                    <!-- Tiempo de evolución -->
                    <div>
                        <label for="tiempoEvolucion" class="flabel">¿Hace cuánto comenzaron?</label>
                        <select id="tiempoEvolucion" class="field">
                            <option value="">No especificado</option>
                            <option value="hoy">Hoy</option>
                            <option value="1-3 dias">1 a 3 días</option>
                            <option value="varios dias">Varios días</option>
                            <option value="semanas o mas">Semanas o más</option>
                            <option value="no se">No estoy seguro/a</option>
                        </select>
                    </div>

                    <!-- Antecedentes -->
                    <div>
                        <label for="enfermedades" class="flabel">Antecedentes <span class="font-normal text-slate-400">(opcional)</span></label>
                        <input id="enfermedades" type="text" placeholder="Ej: diabetes, hipertensión..." class="field mb-1.5">
                        <div id="chipsAntec" class="flex flex-wrap gap-1">
                            @foreach ([
                                'Diabetes','Hipertensión','Asma','Artritis',
                                'Hipotiroidismo','Gastritis','Migraña','Depresión',
                                'Ansiedad','Colesterol alto',
                            ] as $ant)
                            <button type="button" class="chip-antec" data-antec="{{ $ant }}">{{ $ant }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex-1"></div>

                    <!-- CTA -->
                    <div class="pt-1">
                        <button id="btnBuscar" onclick="buscarMedico()" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                            </svg>
                            Ver opciones médicas
                        </button>
                        <button type="button" onclick="limpiar()" class="w-full text-center text-xs text-slate-400 hover:text-slate-500 transition-colors py-1.5 mt-1">
                            Limpiar formulario
                        </button>
                    </div>

                </div>
            </div><!-- /flex-row -->

            <!-- Nota de emergencia compacta -->
            <div class="divider mt-4 mb-3"></div>
            <div class="flex items-start gap-2">
                <svg class="w-3.5 h-3.5 flex-shrink-0 text-amber-500 mt-px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p class="text-xs text-slate-400 leading-relaxed">
                    <strong class="font-semibold text-slate-500">Emergencia:</strong>
                    si tienes dolor de pecho intenso, dificultad grave para respirar, debilidad súbita en un lado del cuerpo, pérdida de conciencia o convulsiones, ve a urgencias de inmediato.
                </p>
            </div>

            <!-- Error -->
            <div id="msgError" class="hidden mt-3 rounded-xl px-4 py-3 text-sm text-red-600 flex items-center gap-2.5 bg-red-50 border border-red-200">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span id="msgErrorText"></span>
            </div>
        </div>

        <!-- ═══ CARGANDO ═══ -->
        <div id="estadoCarga" class="hidden text-center py-10">
            <div class="w-12 h-12 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-cyan-50 border border-cyan-200">
                <svg class="w-6 h-6 text-cyan-500 spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </div>
            <p class="text-slate-700 font-semibold text-sm mb-1">Analizando los síntomas descritos...</p>
            <p class="text-slate-400 text-xs">Puede tardar unos segundos</p>
        </div>

        <!-- ═══ RESULTADOS ═══ -->
        <div id="resultados" class="hidden mt-5 space-y-4">

            <!-- Resumen superior -->
            <div id="bloqueResumen" class="resumen-block fade-up"></div>

            <!-- Encabezado sección opciones -->
            <div id="encabezadoEspecialidades" class="hidden">
                <h2 class="text-sm font-bold text-slate-700 mb-0.5">Opciones médicas sugeridas</h2>
                <p class="text-xs text-slate-400">Basadas en los síntomas descritos. No constituyen un diagnóstico. Se muestran profesionales cercanos para cada opción.</p>
            </div>

            <!-- Acordeón + Mapa -->
            <div class="flex flex-col lg:flex-row gap-4 items-start">
                <div id="listaResultados" class="flex-1 min-w-0 space-y-2"></div>

                <div id="mapaCol" class="hidden w-full lg:w-[400px] flex-shrink-0 lg:sticky lg:top-20">
                    <p class="mapa-lbl">Profesionales de la especialidad seleccionada</p>
                    <div class="rounded-xl overflow-hidden border border-slate-200" style="height:400px;">
                        <div id="mapaResultados" style="width:100%;height:100%;"></div>
                    </div>
                    <p id="mapaLabel" class="text-center text-xs text-slate-400 mt-1.5"></p>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 mt-1 leading-relaxed">
                Orientación generada por análisis de síntomas. Consulta siempre con un profesional de salud.
            </p>
        </div>

    </main>

    <!-- ═══ FOOTER ═══ -->
    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-6xl mx-auto px-5 py-7">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-5">

                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <img src="/recomienda.png" alt="logo" class="w-6 h-6 rounded-lg object-cover">
                        <span class="font-bold text-slate-700 text-sm">MediRecomienda</span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Orientación médica por síntomas. Sugiere opciones médicas probables y muestra profesionales cercanos.
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Aviso importante</p>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Este servicio <strong class="text-slate-500">no reemplaza</strong> la consulta médica ni emite diagnósticos. Las sugerencias son orientativas.
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Cómo funciona</p>
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 flex-shrink-0"></span>
                            Extracción de términos médicos con IA
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                            Puntuación por keywords médicas ponderadas
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 flex-shrink-0"></span>
                            Profesionales ordenados por distancia GPS
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-slate-100 mb-4"></div>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="text-slate-400 text-xs">© {{ date('Y') }} MediRecomienda</p>
                <p class="text-xs text-slate-400">Datos: REPS — datos.gov.co · Médicos en Cali, Colombia</p>
            </div>
        </div>
    </footer>

    <!-- ═══ SCRIPTS ═══ -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>

    let sexoSeleccionado  = null;
    let userLocation      = null;
    let _leafletMap       = null;
    let _doctorLayerGroup = null;

    // ─── Geolocation ───────────────────────────────────────────────────
    function getLocation() {
        return new Promise(resolve => {
            if (!navigator.geolocation) { resolve(null); return; }
            navigator.geolocation.getCurrentPosition(
                pos => resolve({ lat: pos.coords.latitude, lon: pos.coords.longitude }),
                ()  => resolve(null),
                { timeout: 8000, maximumAge: 300000 }
            );
        });
    }

    // ─── Map init ──────────────────────────────────────────────────────
    function initMap(location) {
        if (!location) return;
        if (_leafletMap) {
            if (_doctorLayerGroup) _doctorLayerGroup.clearLayers();
            _leafletMap.invalidateSize();
            _leafletMap.setView([location.lat, location.lon], 13);
            return;
        }
        const caliBounds = L.latLngBounds(L.latLng(3.28, -76.60), L.latLng(3.52, -76.44));
        _leafletMap = L.map('mapaResultados', {
            center: [location.lat, location.lon], zoom: 13,
            maxBounds: caliBounds, maxBoundsViscosity: 1.0,
        });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com">CARTO</a> &copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
            subdomains: 'abcd', maxZoom: 18, bounds: caliBounds,
        }).addTo(_leafletMap);
        _doctorLayerGroup = L.layerGroup().addTo(_leafletMap);
        const userIcon = L.divIcon({
            className: '',
            html: '<div style="width:14px;height:14px;background:#ef4444;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.4)"></div>',
            iconSize: [14, 14], iconAnchor: [7, 7],
        });
        L.marker([location.lat, location.lon], { icon: userIcon })
            .addTo(_leafletMap).bindPopup('<b>Tu ubicación</b>');
        requestAnimationFrame(() => _leafletMap && _leafletMap.invalidateSize());
    }

    // ─── Coordinate normalizer ─────────────────────────────────────────
    // Resolves lat/lng robustly regardless of string vs number types
    function coordsOf(m) {
        const lat = parseFloat(m.latitud  ?? m.lat  ?? m.latitude  ?? '');
        const lng = parseFloat(m.longitud ?? m.lng  ?? m.longitude ?? m.lon ?? '');
        if (isNaN(lat) || isNaN(lng)) return null;
        return { lat, lng };
    }

    // ─── Doctor markers ────────────────────────────────────────────────
    function addDoctorMarkers(medicos, especialidadLabel) {
        const lbl = document.getElementById('mapaLabel');
        if (lbl && especialidadLabel) lbl.textContent = especialidadLabel;
        if (!_leafletMap || !userLocation) return;

        if (!_doctorLayerGroup) {
            _doctorLayerGroup = L.layerGroup().addTo(_leafletMap);
        } else {
            _doctorLayerGroup.clearLayers();
        }
        _leafletMap.invalidateSize();

        const conCoords = medicos.filter(m => coordsOf(m) !== null);
        if (conCoords.length === 0) return;

        const bounds = [[userLocation.lat, userLocation.lon]];
        conCoords.forEach((m, i) => {
            const c = coordsOf(m);
            const color   = i === 0 ? '#16a34a' : '#0891b2';
            const docIcon = L.divIcon({
                className: '',
                html: '<div style="width:12px;height:12px;background:' + color + ';border:2px solid white;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>',
                iconSize: [12, 12], iconAnchor: [6, 6],
            });
            const distTxt  = m.distancia_km != null ? parseFloat(m.distancia_km).toFixed(1) + ' km' : '';
            const dirTxt   = (m.direccion  && m.direccion.trim())  ? '<br><small>' + m.direccion + '</small>' : '';
            const closest  = i === 0 ? '<br><span style="color:#16a34a;font-weight:700">Más cercano</span>' : '';
            L.marker([c.lat, c.lng], { icon: docIcon })
                .addTo(_doctorLayerGroup)
                .bindPopup('<b>' + (m.nombre || 'Médico') + '</b>' + closest + dirTxt + (distTxt ? '<br>' + distTxt : ''));
            bounds.push([c.lat, c.lng]);
        });
        if (bounds.length > 1) {
            _leafletMap.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });
        }
    }

    // ─── Normaliza la lista de médicos de una especialidad ─────────────
    function medicosDe(esp) {
        return ((esp.medicos && esp.medicos.data) ? esp.medicos.data : (esp.medicos || [])).slice(0, 3);
    }

    // ─── Gender selector ───────────────────────────────────────────────
    function seleccionarGenero(sexo) {
        sexoSeleccionado = sexo;
        document.getElementById('sexo').value = sexo;
        const m = document.getElementById('genderMale');
        const f = document.getElementById('genderFemale');
        m.classList.remove('sel-m', 'sel-f');
        f.classList.remove('sel-m', 'sel-f');
        if (sexo === 'masculino') m.classList.add('sel-m');
        else f.classList.add('sel-f');
    }

    // ─── Chip interaction ──────────────────────────────────────────────
    document.querySelectorAll('.chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const ta = document.getElementById('sintomas');
            const s  = chip.dataset.sintoma;
            chip.classList.toggle('active');
            if (chip.classList.contains('active')) {
                ta.value = ta.value ? ta.value.trimEnd() + ', ' + s : s;
            } else {
                ta.value = ta.value.split(',').map(x => x.trim()).filter(x => x.toLowerCase() !== s.toLowerCase()).join(', ');
            }
            actualizarContador();
        });
    });

    document.querySelectorAll('.chip-antec').forEach(chip => {
        chip.addEventListener('click', () => {
            const input = document.getElementById('enfermedades');
            const val   = chip.dataset.antec;
            chip.classList.toggle('active');
            const cur = input.value.split(',').map(x => x.trim()).filter(Boolean);
            if (chip.classList.contains('active')) { if (!cur.includes(val)) cur.push(val); }
            else { const i = cur.indexOf(val); if (i > -1) cur.splice(i, 1); }
            input.value = cur.join(', ');
        });
    });

    const ta = document.getElementById('sintomas');
    ta.addEventListener('input', actualizarContador);
    function actualizarContador() {
        document.getElementById('contador').textContent = ta.value.length + ' / 500';
    }

    // ─── Limpiar ───────────────────────────────────────────────────────
    function limpiar() {
        ['sintomas','enfermedades','edad','sexo'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('tiempoEvolucion').value = '';
        document.getElementById('contador').textContent  = '0 / 500';
        sexoSeleccionado = null;
        document.getElementById('genderMale').classList.remove('sel-m','sel-f');
        document.getElementById('genderFemale').classList.remove('sel-m','sel-f');
        document.querySelectorAll('.chip.active,.chip-antec.active').forEach(c => c.classList.remove('active'));
        ocultarResultados(); ocultarError();
    }

    // ─── Buscar médico ─────────────────────────────────────────────────
    async function buscarMedico() {
        const sintomas = document.getElementById('sintomas').value.trim();
        if (!sintomas || sintomas.length < 5) {
            mostrarError('Por favor describe tus síntomas con un poco más de detalle.');
            return;
        }
        ocultarError(); ocultarResultados(); mostrarCarga(true); bloquearBtn(true);

        userLocation = await getLocation();

        try {
            const body = {
                sintomas,
                enfermedades_previas: document.getElementById('enfermedades').value.trim() || null,
                edad:  parseInt(document.getElementById('edad').value)  || null,
                sexo:  document.getElementById('sexo').value            || null,
                tiempo_evolucion: document.getElementById('tiempoEvolucion').value || null,
            };
            if (userLocation) { body.latitud = userLocation.lat; body.longitud = userLocation.lon; }

            const resp = await fetch('/api/analizar', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(body),
            });
            const data = await resp.json();
            if (!resp.ok) { mostrarError(data.message || 'Error al procesar la solicitud.'); return; }
            renderizarResultados(data);
        } catch(e) {
            mostrarError('Error de conexión. Verifica tu internet e intenta de nuevo.');
        } finally {
            mostrarCarga(false); bloquearBtn(false);
        }
    }

    // ─── Urgencia config ───────────────────────────────────────────────
    const URGENCIA = {
        normal: {
            badge: 'urg-normal', label: 'Prioridad orientativa: baja',
            dotColor: '#16a34a',
            alertBg: 'bg-green-50 border-green-200', alertTitleCls: 'text-green-700', alertMsgCls: 'text-green-600',
            alertTitle: 'Sin señales de alerta inmediata',
            alertMsg:   'Puedes agendar una cita en los próximos días.',
            alertIcon:  '<svg class="w-4 h-4 text-green-600 flex-shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>',
        },
        urgente: {
            badge: 'urg-urgente', label: 'Prioridad orientativa: atención pronta',
            dotColor: '#d97706',
            alertBg: 'bg-amber-50 border-amber-200', alertTitleCls: 'text-amber-700', alertMsgCls: 'text-amber-600',
            alertTitle: 'Atención pronta recomendada',
            alertMsg:   'Busca consulta médica lo antes posible, preferiblemente en las próximas horas.',
            alertIcon:  '<svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>',
        },
        critico: {
            badge: 'urg-critico', label: 'Prioridad orientativa: atención inmediata',
            dotColor: '#dc2626',
            alertBg: 'bg-red-50 border-red-200', alertTitleCls: 'text-red-700', alertMsgCls: 'text-red-600',
            alertTitle: 'Señales de alerta detectadas',
            alertMsg:   'Los síntomas pueden requerir atención urgente. Acude a urgencias o llama a servicios de emergencia.',
            alertIcon:  '<svg class="w-4 h-4 text-red-600 flex-shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>',
        },
    };

    let _allEsp = [];

    // ─── Render resultados ─────────────────────────────────────────────
    function renderizarResultados(data) {
        _allEsp = data.especialidades || [];
        const urg = data.nivel_urgencia || 'normal';

        // ── EMERGENCIA: banner a pantalla completa, sin listado de médicos ──
        if (urg === 'emergencia') {
            const iconSirena = '<svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-4 mx-auto opacity-90" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5"/></svg>';
            document.getElementById('bloqueResumen').innerHTML =
                '<div class="emergency-banner fade-up">'
                + iconSirena
                + '<p class="text-xs font-bold uppercase tracking-widest mb-2 opacity-75">Alerta de emergencia médica</p>'
                + '<h2 class="text-xl font-extrabold mb-3 leading-snug">' + (data.condicion_detectada || 'Emergencia detectada') + '</h2>'
                + '<p class="text-sm opacity-90 mb-5 leading-relaxed max-w-md mx-auto">' + (data.mensaje_emergencia || '') + '</p>'
                + '<div class="flex flex-col sm:flex-row gap-3 justify-center">'
                + '<a href="tel:123" class="inline-flex items-center justify-center gap-2 bg-white text-red-700 font-bold text-base px-6 py-3 rounded-xl shadow-lg hover:bg-red-50 transition-colors">'
                + '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>'
                + 'Llamar al 123'
                + '</a>'
                + '<a href="https://maps.google.com/?q=urgencias+hospital+cercano" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-red-800 text-white font-semibold text-sm px-5 py-3 rounded-xl hover:bg-red-900 transition-colors">'
                + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>'
                + 'Ver urgencias cercanas'
                + '</a>'
                + '</div>'
                + '<p class="text-xs opacity-60 mt-5">No esperes. Ve a urgencias o llama al número de emergencias.</p>'
                + '</div>';
            document.getElementById('encabezadoEspecialidades').classList.add('hidden');
            document.getElementById('listaResultados').innerHTML = '';
            document.getElementById('mapaCol').classList.add('hidden');
            document.getElementById('resultados').classList.remove('hidden');
            document.getElementById('resultados').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        const U   = URGENCIA[urg] || URGENCIA.normal;

        let html = '';

        // Badge de urgencia
        html += '<div class="flex flex-wrap items-center gap-2 mb-3">'
            + '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ' + U.badge + '">'
            + '<span style="width:7px;height:7px;border-radius:50%;background:' + U.dotColor + ';display:inline-block;"></span>'
            + U.label + '</span></div>';

        // Mensaje de acción
        html += '<div class="rounded-xl px-4 py-3 flex gap-2.5 items-start mb-3 ' + U.alertBg + ' border">'
            + U.alertIcon
            + '<div><p class="font-semibold ' + U.alertTitleCls + ' text-sm leading-tight">' + U.alertTitle + '</p>'
            + '<p class="text-xs ' + U.alertMsgCls + ' mt-0.5">' + U.alertMsg + '</p></div></div>';

        // Resumen IA
        if (data.resumen_ia) {
            html += '<div class="rounded-xl px-4 py-3 flex gap-2.5 items-start mb-3 bg-slate-50 border border-slate-200">'
                + '<svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>'
                + '<div><p class="text-xs font-semibold text-slate-500 mb-0.5 uppercase tracking-wider">Resumen orientativo</p>'
                + '<p class="text-sm text-slate-600 leading-relaxed">' + data.resumen_ia + '</p></div></div>';
        }

        // Términos detectados
        if (data.terminos_extraidos && data.terminos_extraidos.length > 0) {
            const chips = data.terminos_extraidos.map(t =>
                '<span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-100">' + t + '</span>'
            ).join(' ');
            html += '<div class="flex flex-wrap gap-1.5 items-center">'
                + '<span class="text-xs text-slate-400 mr-0.5">Síntomas identificados:</span>' + chips + '</div>';
        }

        html += '<p class="text-xs text-slate-400 mt-3 pt-3 border-t border-slate-100">Esta orientación no es un diagnóstico médico. Consulta con un profesional de salud.</p>';

        document.getElementById('bloqueResumen').innerHTML = html;

        const lista = document.getElementById('listaResultados');
        lista.innerHTML = '';

        if (_allEsp.length === 0) {
            lista.innerHTML = '<div class="text-center py-10 fade-up">'
                + '<p class="font-semibold text-slate-700 mb-1">Sin resultados claros</p>'
                + '<p class="text-sm text-slate-400">Intenta describir con más detalle los síntomas.</p></div>';
            document.getElementById('resultados').classList.remove('hidden');
            document.getElementById('encabezadoEspecialidades').classList.add('hidden');
            return;
        }

        document.getElementById('encabezadoEspecialidades').classList.remove('hidden');
        document.getElementById('resultados').classList.remove('hidden');

        _allEsp.forEach((esp, i) => renderEspCard(esp, i, i === 0));

        if (userLocation) {
            document.getElementById('mapaCol').classList.remove('hidden');
            initMap(userLocation);
            addDoctorMarkers(medicosDe(_allEsp[0]), _allEsp[0].especialidad);
        }

        document.getElementById('resultados').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ─── Render card especialidad ──────────────────────────────────────
    function renderEspCard(esp, i, expanded) {
        const lista      = document.getElementById('listaResultados');
        const badgeCls   = i === 0 ? 'badge-1' : i === 1 ? 'badge-2' : 'badge-3';
        const posLabel   = i === 0 ? 'Mayor probabilidad' : 'Opción ' + (i + 1);
        const pct        = Math.min(Math.round((esp.puntuacion / 60) * 100), 100);
        const barColor   = (esp.red_flags && esp.red_flags.length > 0) ? '#ef4444'
                         : i === 0 ? '#0891b2' : '#94a3b8';

        // Chips de términos
        let termChips = '';
        if (esp.terminos_matched && esp.terminos_matched.length > 0) {
            termChips = esp.terminos_matched.slice(0, 5).map(t =>
                '<span class="text-xs px-2 py-0.5 rounded-full bg-slate-50 text-slate-500 border border-slate-200">' + t + '</span>'
            ).join('');
        }

        // ─── Médicos ───────────────────────────────────────────────────
        const meds = medicosDe(esp);
        let medHtml = '';

        if (meds.length > 0) {
            medHtml = '<div class="mt-3 pt-3 border-t border-slate-100 space-y-2">'
                + '<p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Profesionales cercanos</p>';

            meds.forEach((m, mi) => {
                // Rating
                const rateHtml = m.calificacion_promedio
                    ? '<div class="flex items-center gap-1 mt-0.5">'
                        + '<svg class="w-3 h-3 text-yellow-400" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>'
                        + '<span class="text-xs text-slate-400">' + parseFloat(m.calificacion_promedio).toFixed(1) + '</span></div>'
                    : '';

                // Distancia
                const distHtml = (m.distancia_km != null)
                    ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0 '
                        + (mi === 0 ? 'bg-green-50 text-green-700 border border-green-200'
                                    : 'bg-slate-100 text-slate-600 border border-slate-200')
                        + '">' + parseFloat(m.distancia_km).toFixed(1) + ' km</span>'
                    : '';

                // Dirección — campo clave recuperado del recurso API
                const dirHtml = (m.direccion && m.direccion.trim())
                    ? '<p class="text-xs text-slate-400 mt-0.5 flex items-start gap-1" title="' + m.direccion + '">'
                        + '<svg class="w-2.5 h-2.5 text-slate-300 flex-shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>'
                        + '<span class="truncate">' + m.direccion + '</span></p>'
                    : '';

                medHtml += '<div class="flex items-start gap-2.5 rounded-lg px-3 py-2.5 bg-slate-50 border border-slate-100">'
                    + '<div class="doc-avatar mt-0.5">'
                    + '<svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.125a7.5 7.5 0 0114.998 0"/>'
                    + '</svg></div>'
                    + '<div class="flex-1 min-w-0">'
                    + '<p class="font-semibold text-slate-700 text-sm leading-tight">' + (m.nombre || 'Médico') + '</p>'
                    + '<p class="text-xs text-slate-500 truncate">' + esp.especialidad + '</p>'
                    + dirHtml + rateHtml
                    + '</div>'
                    + (distHtml ? '<div class="flex-shrink-0 self-center ml-1">' + distHtml + '</div>' : '')
                    + '</div>';
            });
            medHtml += '</div>';
        } else {
            medHtml = '<div class="mt-3 pt-3 border-t border-slate-100">'
                + '<p class="text-xs text-slate-400 italic">No hay profesionales registrados para esta opción.</p></div>';
        }

        if (!userLocation) {
            medHtml += '<p class="text-xs text-slate-400 mt-2">Activa la ubicación GPS para ver distancias y profesionales en el mapa.</p>';
        }

        // ─── Justificación ─────────────────────────────────────────────
        const justHtml = esp.justificacion
            ? '<div class="mt-3 rounded-lg px-3 py-2.5 bg-slate-50 border border-slate-100">'
                + '<p class="text-xs font-semibold text-slate-500 mb-1">¿Por qué esta opción?</p>'
                + '<p class="text-xs text-slate-500 leading-relaxed">' + esp.justificacion + '</p>'
                + (termChips ? '<div class="flex flex-wrap gap-1 mt-2">' + termChips + '</div>' : '')
                + '</div>'
            : '';

        const redFlagIco = (esp.red_flags && esp.red_flags.length > 0)
            ? '<svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>'
            : '';

        const chevUp   = '<svg class="w-4 h-4 text-slate-400 rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
        const chevDown = '<svg class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';

        const card = document.createElement('div');
        card.className = 'result-card fade-up overflow-hidden';
        card.dataset.idx = i;

        card.innerHTML =
            '<button type="button" class="esp-header w-full text-left" data-idx="' + i + '">'
            + '<div class="flex items-center justify-between gap-2">'
            + '<div class="flex items-center gap-2 min-w-0">'
            + '<span class="' + badgeCls + ' inline-block px-2 py-0.5 rounded-full text-xs font-bold flex-shrink-0">' + posLabel + '</span>'
            + '<h3 class="font-bold text-slate-800 text-sm leading-tight">' + esp.especialidad + '</h3>'
            + redFlagIco + '</div>'
            + '<span class="chevron flex-shrink-0">' + (expanded ? chevUp : chevDown) + '</span></div>'
            + '<div class="flex items-center gap-2 mt-2">'
            + '<span class="text-xs text-slate-400 flex-shrink-0">Relevancia</span>'
            + '<div class="flex-1 rounded-full h-1 bg-slate-100"><div style="background:' + barColor + ';height:4px;border-radius:999px;width:' + pct + '%;transition:width .5s ease;"></div></div>'
            + '<span class="text-xs text-slate-400 tabular-nums">' + pct + '%</span></div></button>'
            + '<div class="esp-body ' + (expanded ? '' : 'hidden') + '">' + justHtml + medHtml + '</div>';

        lista.appendChild(card);
    }

    // ─── Acordeón click ────────────────────────────────────────────────
    document.getElementById('listaResultados').addEventListener('click', e => {
        const btn = e.target.closest('.esp-header');
        if (!btn) return;
        const idx  = parseInt(btn.dataset.idx);
        const card = btn.closest('.result-card');
        const body = card.querySelector('.esp-body');
        if (!body.classList.contains('hidden')) return;

        document.querySelectorAll('.result-card').forEach(c => {
            c.querySelector('.esp-body').classList.add('hidden');
            c.querySelector('.chevron').innerHTML = '<svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
        });
        body.classList.remove('hidden');
        btn.querySelector('.chevron').innerHTML = '<svg class="w-4 h-4 text-slate-400 rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';

        if (userLocation && _allEsp[idx]) {
            addDoctorMarkers(medicosDe(_allEsp[idx]), _allEsp[idx].especialidad);
        }
    });

    // ─── UI helpers ────────────────────────────────────────────────────
    function mostrarCarga(v)     { document.getElementById('estadoCarga').classList.toggle('hidden', !v); }
    function ocultarResultados() {
        document.getElementById('resultados').classList.add('hidden');
        document.getElementById('listaResultados').innerHTML = '';
        document.getElementById('bloqueResumen').innerHTML   = '';
        document.getElementById('encabezadoEspecialidades').classList.add('hidden');
        document.getElementById('mapaCol').classList.add('hidden');
        if (_doctorLayerGroup) _doctorLayerGroup.clearLayers();
        if (_leafletMap) { _leafletMap.remove(); _leafletMap = null; _doctorLayerGroup = null; }
        _allEsp = [];
    }
    function mostrarError(msg) {
        document.getElementById('msgErrorText').textContent = msg;
        document.getElementById('msgError').classList.remove('hidden');
    }
    function ocultarError() { document.getElementById('msgError').classList.add('hidden'); }
    function bloquearBtn(v) {
        const btn = document.getElementById('btnBuscar');
        btn.disabled = v;
        btn.innerHTML = v
            ? '<svg class="w-4 h-4 spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg> Analizando...'
            : '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg> Ver opciones médicas';
    }

    </script>
</body>
</html>