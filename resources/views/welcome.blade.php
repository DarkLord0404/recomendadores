<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MediRecomienda — Orientación médica por síntomas</title>
    <link rel="icon" type="image/png" href="/recomienda.png">
    <link rel="apple-touch-icon" href="/recomienda.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

        body {
            background: linear-gradient(160deg, #f0fdff 0%, #f8fafc 50%, #ecfdf5 100%);
            min-height: 100vh;
            color: #1e293b;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(8,145,178,0.06);
        }

        /* Chips sintomas */
        .chip {
            display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;
            padding: 5px 11px; border-radius: 9999px; font-size: 12.5px;
            border: 1.5px solid #e2e8f0; cursor: pointer;
            transition: all .15s ease;
            background: #fff; color: #475569;
            user-select: none; flex-shrink: 0;
        }
        .chip:hover  { border-color: #0891b2; color: #0e7490; background: #f0fdff; }
        .chip.active { border-color: #0891b2; color: #0e7490; background: #ecfeff; }

        /* Chips antecedentes */
        .chip-antec {
            display: inline-flex; align-items: center; white-space: nowrap;
            padding: 3px 10px; border-radius: 9999px; font-size: 11.5px;
            border: 1.5px solid #e2e8f0; cursor: pointer;
            transition: all .15s ease;
            background: #f8fafc; color: #64748b;
            user-select: none;
        }
        .chip-antec:hover  { border-color: #059669; color: #047857; background: #f0fdf4; }
        .chip-antec.active { border-color: #059669; color: #047857; background: #dcfce7; }

        /* Gender cards */
        .gender-card {
            flex: 1; padding: 10px 14px; border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            cursor: pointer; transition: all .18s ease;
            display: flex; align-items: center; gap: 9px;
            color: #64748b; text-align: left; min-width: 0;
        }
        .gender-card:hover { border-color: #0891b2; background: #f0fdff; color: #0e7490; }
        .gender-card.selected-male   { border-color: #0891b2; background: #ecfeff; color: #0e7490; }
        .gender-card.selected-female { border-color: #db2777; background: #fdf2f8; color: #be185d; }
        .gender-card .g-icon  { font-size: 20px; line-height: 1; flex-shrink: 0; }
        .gender-card .g-label { font-weight: 700; font-size: 13px; display: block; }

        /* Inputs */
        .field {
            width: 100%; border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            padding: 10px 14px; font-size: 14px; color: #1e293b;
            transition: all .15s ease;
        }
        .field::placeholder { color: #94a3b8; }
        .field:focus {
            outline: none;
            border-color: #0891b2;
            background: #f0fdff;
            box-shadow: 0 0 0 3px rgba(8,145,178,0.12);
        }
        select.field {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            padding-right: 32px;
        }

        /* Button */
        .btn-primary {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white; border: none; border-radius: 11px;
            padding: 13px 28px; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all .15s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(8,145,178,0.28);
            letter-spacing: -0.01em;
        }
        .btn-primary:hover   { transform: translateY(-1px); box-shadow: 0 6px 22px rgba(8,145,178,0.38); filter: brightness(1.06); }
        .btn-primary:active  { transform: translateY(0) scale(.98); }
        .btn-primary:disabled{ background: #94a3b8; box-shadow: none; cursor: not-allowed; transform: none; filter: none; }

        /* Section label */
        .section-label {
            display: block; font-size: 10px; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase; color: #0891b2;
        }

        /* Divider */
        .divider { height: 1px; background: linear-gradient(90deg, transparent, #cffafe, transparent); }

        /* Textarea */
        #sintomas { font-size: 15px; min-height: 110px; }
        #sintomas:focus { outline: none; border-color: #0891b2; background: #f0fdff; box-shadow: 0 0 0 3px rgba(8,145,178,0.12); }

        /* Animations */
        @keyframes spin   { to { transform: rotate(360deg); } }
        @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        @keyframes pulse  { 0%,100%{opacity:1;} 50%{opacity:.45;} }
        .spin    { animation: spin .8s linear infinite; }
        .fade-up { animation: fadeUp .3s ease forwards; }
        .pulse   { animation: pulse 2s ease infinite; }

        /* Alerta emergencia pre-envio */
        .alerta-emergencia {
            background: #fff7ed;
            border: 1.5px solid #fed7aa;
            border-radius: 14px;
            padding: 14px 16px;
        }

        /* Result cards */
        .result-card {
            background: #fff; border: 1.5px solid #e2e8f0;
            border-radius: 16px; padding: 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: all .2s ease;
        }
        .result-card:hover { border-color: #a5f3fc; box-shadow: 0 4px 18px rgba(8,145,178,0.1); }

        /* Doc avatar */
        .doc-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #ecfeff, #f0fdf4);
            border: 1.5px solid #a5f3fc;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* Badge posicion */
        .badge-1 { background:#fef9c3; color:#a16207; border:1px solid #fde047; }
        .badge-2 { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
        .badge-3 { background:#fff7ed; color:#c2410c; border:1px solid #fdba74; }

        /* Urgencia badges resultado */
        .urgencia-normal  { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .urgencia-urgente { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
        .urgencia-critico { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

        /* Chips scroll mobile */
        .chips-scroll { display: flex; flex-wrap: wrap; gap: 6px; }
        @media (max-width: 640px) {
            .chips-scroll { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
            .chips-scroll::-webkit-scrollbar { display: none; }
        }

        /* Mobile tweaks */
        @media (max-width: 640px) {
            .hero-title { font-size: 26px !important; line-height: 1.2; }
            .btn-primary { width: 100%; }
            #sintomas { min-height: 120px; }
        }

        /* Leaflet */
        #mapaResultados { z-index: 0; }
        .leaflet-popup-content { font-size: 13px; line-height: 1.5; }

        /* Bloque resumen resultado */
        .resultado-resumen {
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            padding: 20px 22px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        .mapa-wrapper-label {
            font-size: 11px; font-weight: 600; color: #64748b;
            text-align: center; margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: .06em;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90" style="backdrop-filter:blur(16px);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <img src="/recomienda.png" alt="MediRecomienda" class="w-9 h-9 rounded-xl object-cover flex-shrink-0" style="box-shadow:0 2px 8px rgba(8,145,178,0.2);">
                <div>
                    <span class="text-base font-extrabold text-slate-800 tracking-tight leading-none">MediRecomienda</span>
                    <span class="hidden sm:block text-[10px] font-medium text-slate-400 mt-0.5">Orientacion medica por sintomas</span>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-full">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
                <span class="text-xs font-medium text-slate-500">Orientacion, no diagnostico</span>
            </div>
        </div>
    </header>

    <!-- CONTENIDO -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 pt-6 pb-16">

        <!-- Hero -->
        <div class="text-center mb-5">
            <h1 class="hero-title text-3xl sm:text-4xl font-extrabold text-slate-800 leading-tight mb-2 tracking-tight">
                Describe tus sintomas y<br>
                <span style="background:linear-gradient(135deg,#0891b2,#059669); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">te orientamos sobre que especialista consultar</span>
            </h1>
            <p class="text-slate-500 text-sm max-w-lg mx-auto leading-relaxed">
                Analizamos lo que describes, sugerimos las especialidades mas probables y mostramos medicos cercanos de cada una.
                <strong class="text-slate-600 font-semibold">No reemplaza una valoracion medica.</strong>
            </p>
        </div>

        <!-- FORMULARIO -->
        <div class="card p-5 sm:p-6 mb-5">

            <!-- 1. Campo principal de sintomas -->
            <div class="mb-4">
                <label for="sintomas" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Describe los sintomas o el motivo de consulta
                    <span class="font-normal text-slate-400 text-xs ml-1">*obligatorio</span>
                </label>
                <textarea
                    id="sintomas"
                    rows="4"
                    maxlength="500"
                    placeholder="Ejemplo: tengo dolor de pecho desde ayer, palpitaciones y me cuesta respirar al caminar"
                    class="field resize-none"
                ></textarea>
                <div class="flex items-center justify-between mt-1.5">
                    <span class="text-xs text-slate-400">Cuanto mas detallado, mejor sera la orientacion</span>
                    <span id="contador" class="text-xs text-slate-400">0 / 500</span>
                </div>
            </div>

            <!-- Chips sintomas frecuentes -->
            <div class="mb-4">
                <span class="section-label mb-2">Sintomas frecuentes — toca para agregar al campo de texto</span>
                <div id="chips" class="chips-scroll mt-1.5">
                    @foreach ([
                        ['🤒','Fiebre'],['🤕','Dolor de cabeza'],['🤧','Tos'],
                        ['😮‍💨','Falta de aire'],['🤢','Nauseas'],['💢','Dolor de pecho'],
                        ['🦴','Dolor muscular'],['😴','Cansancio'],['🫃','Dolor abdominal'],
                        ['💊','Mareos'],['🌡️','Escalofrios'],['😔','Tristeza / ansiedad'],
                    ] as [$icon, $label])
                    <button type="button" class="chip" data-sintoma="{{ $label }}">{{ $icon }} {{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="divider mb-4"></div>

            <!-- 2. Datos clinicos complementarios -->
            <div class="mb-4">
                <span class="section-label mb-3">Datos complementarios <span class="normal-case font-normal text-slate-400">(opcionales, mejoran la orientacion)</span></span>

                <div class="grid grid-cols-1 sm:grid-cols-[auto_auto_1fr] gap-3 mt-2 items-start">

                    <!-- Sexo -->
                    <div class="flex gap-2">
                        <button type="button" class="gender-card" id="genderMale" onclick="seleccionarGenero('masculino')">
                            <span class="g-icon">&#9794;</span>
                            <span><span class="g-label">Hombre</span></span>
                        </button>
                        <button type="button" class="gender-card" id="genderFemale" onclick="seleccionarGenero('femenino')">
                            <span class="g-icon">&#9792;</span>
                            <span><span class="g-label">Mujer</span></span>
                        </button>
                        <input type="hidden" id="sexo" value="">
                    </div>

                    <!-- Edad -->
                    <input id="edad" type="number" min="0" max="120" placeholder="Edad" class="field" style="max-width:90px;">

                    <!-- Tiempo de evolucion -->
                    <div>
                        <label for="tiempoEvolucion" class="block text-xs font-semibold text-slate-500 mb-1">Hace cuanto comenzaron los sintomas?</label>
                        <select id="tiempoEvolucion" class="field">
                            <option value="">Selecciona una opcion</option>
                            <option value="hoy">Hoy</option>
                            <option value="1-3 dias">Hace 1 a 3 dias</option>
                            <option value="varios dias">Hace varios dias</option>
                            <option value="semanas o mas">Hace semanas o mas</option>
                            <option value="no se">No estoy seguro/a</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Antecedentes -->
            <div class="mb-4">
                <label for="enfermedades" class="block text-xs font-semibold text-slate-500 mb-1.5">Antecedentes medicos <span class="font-normal text-slate-400">(opcional)</span></label>
                <input id="enfermedades" type="text" placeholder="Ej: Diabetes, hipertension..." class="field mb-2">
                <div id="chipsAntec" class="flex flex-wrap gap-1.5">
                    @foreach ([
                        'Diabetes','Hipertension','Asma','Artritis',
                        'Hipotiroidismo','Gastritis','Migrana','Depresion',
                        'Ansiedad','Colesterol alto','Obesidad','EPOC',
                    ] as $ant)
                    <button type="button" class="chip-antec" data-antec="{{ $ant }}">{{ $ant }}</button>
                    @endforeach
                </div>
            </div>

            <div class="divider mb-4"></div>

            <!-- 3. Bloque de seguridad clinica -->
            <div class="alerta-emergencia mb-4">
                <div class="flex items-start gap-3">
                    <span class="text-xl flex-shrink-0 mt-0.5">&#128680;</span>
                    <div>
                        <p class="text-sm font-bold text-orange-700 mb-1">Si presentas alguna de estas senales, ve a urgencias ahora</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-0.5">
                            @foreach ([
                                'Dolor en el pecho o dificultad para respirar',
                                'Debilidad repentina en un lado del cuerpo',
                                'Dificultad para hablar o entender',
                                'Perdida de conciencia o desmayo',
                                'Convulsiones',
                                'Sangrado abundante',
                                'Fiebre alta con deterioro rapido',
                            ] as $senal)
                            <p class="text-xs text-orange-600 flex items-center gap-1"><span class="text-orange-400">></span> {{ $senal }}</p>
                            @endforeach
                        </div>
                        <p class="text-xs text-orange-500 mt-2">Esta herramienta brinda orientacion inicial y no es un sustituto de atencion medica de emergencia.</p>
                    </div>
                </div>
            </div>

            <!-- 4. CTA principal -->
            <div class="flex flex-col gap-2">
                <button id="btnBuscar" onclick="buscarMedico()" class="btn-primary w-full sm:w-auto sm:self-start px-8 py-3.5 text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    Obtener orientacion
                </button>
                <button type="button" onclick="limpiar()" class="text-xs text-slate-400 hover:text-slate-600 transition-colors py-1 text-left sm:text-center">
                    Limpiar formulario
                </button>
            </div>

            <!-- Error -->
            <div id="msgError" class="hidden mt-3 rounded-xl px-4 py-3 text-sm text-red-600 flex items-center gap-2.5 bg-red-50 border border-red-200">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span id="msgErrorText"></span>
            </div>
        </div>

        <!-- CARGANDO -->
        <div id="estadoCarga" class="hidden text-center py-12">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-cyan-50 border border-cyan-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-cyan-500 spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </div>
            <p class="text-slate-700 font-semibold mb-1">Analizando los sintomas descritos...</p>
            <p class="text-slate-400 text-sm">Esto puede tardar unos segundos</p>
        </div>

        <!-- RESULTADOS -->
        <div id="resultados" class="hidden mt-6 space-y-5">

            <!-- A. Bloque de resumen superior -->
            <div id="bloqueResumen" class="resultado-resumen fade-up"></div>

            <!-- Encabezado especialidades -->
            <div id="encabezadoEspecialidades" class="hidden">
                <h2 class="text-base font-bold text-slate-700 mb-0.5">Especialidades sugeridas segun los sintomas descritos</h2>
                <p class="text-xs text-slate-400">Estas especialidades son las mas probables segun el analisis. No constituyen un diagnostico. Se muestran medicos cercanos para cada una.</p>
            </div>

            <!-- B. Grid acordeon + mapa -->
            <div class="flex flex-col lg:flex-row gap-5 items-start">

                <!-- Columna izquierda: acordeon -->
                <div id="listaResultados" class="flex-1 min-w-0 space-y-2"></div>

                <!-- Columna derecha: mapa sticky -->
                <div id="mapaCol" class="hidden w-full lg:w-[420px] flex-shrink-0 lg:sticky lg:top-20">
                    <p class="mapa-wrapper-label">Medicos cercanos de la especialidad seleccionada</p>
                    <div class="rounded-2xl overflow-hidden border border-slate-200" style="height:420px;">
                        <div id="mapaResultados" style="width:100%;height:100%;"></div>
                    </div>
                    <p id="mapaLabel" class="text-center text-xs text-slate-400 mt-2"></p>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 mt-2 leading-relaxed">
                Orientacion generada por analisis de sintomas con IA. Consulta siempre con un profesional de salud antes de tomar decisiones medicas.
            </p>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="border-t border-slate-200 bg-white/60" style="backdrop-filter:blur(8px);">
        <div class="max-w-5xl mx-auto px-5 py-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">

                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <img src="/recomienda.png" alt="logo" class="w-7 h-7 rounded-lg object-cover">
                        <span class="font-bold text-slate-700 text-sm">MediRecomienda</span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Herramienta de orientacion medica por sintomas. Sugiere especialidades probables y muestra medicos cercanos.
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Aviso importante</p>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Este servicio <strong class="text-slate-500">no reemplaza</strong> la consulta medica presencial ni emite diagnosticos. Las sugerencias son orientativas y deben ser valoradas por un profesional.
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Como funciona</p>
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 flex-shrink-0"></span>
                            Extraccion de terminos medicos con IA
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                            Puntuacion por keywords medicas ponderadas
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500 flex-shrink-0"></span>
                            Medicos ordenados por distancia GPS
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent mb-4"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="text-slate-400 text-xs">© {{ date('Y') }} MediRecomienda — Herramienta de orientacion medica por sintomas.</p>
                <p class="text-xs text-slate-400">Datos medicos: REPS — datos.gov.co · Medicos en Cali, Colombia</p>
            </div>
        </div>
    </footer>

    <!-- SCRIPTS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <script>

    let sexoSeleccionado = null;
    let userLocation     = null;
    let _leafletMap      = null;
    let _doctorLayerGroup = null;

    function getLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) { resolve(null); return; }
            navigator.geolocation.getCurrentPosition(
                pos => resolve({ lat: pos.coords.latitude, lon: pos.coords.longitude }),
                ()  => resolve(null),
                { timeout: 8000, maximumAge: 300000 }
            );
        });
    }

    function initMap(location) {
        if (!location) return;

        if (_leafletMap) {
            if (_doctorLayerGroup) { _doctorLayerGroup.clearLayers(); }
            _leafletMap.invalidateSize();
            _leafletMap.setView([location.lat, location.lon], 13);
            return;
        }

        const caliBounds = L.latLngBounds(L.latLng(3.28, -76.60), L.latLng(3.52, -76.44));

        _leafletMap = L.map('mapaResultados', {
            center: [location.lat, location.lon], zoom: 13,
            maxBounds: caliBounds, maxBoundsViscosity: 1.0,
            zoomControl: true, attributionControl: true,
        });

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com">CARTO</a> &copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
            subdomains: 'abcd', maxZoom: 18, bounds: caliBounds,
        }).addTo(_leafletMap);

        _doctorLayerGroup = L.layerGroup().addTo(_leafletMap);

        const userIcon = L.divIcon({
            className: '',
            html: '<div style="width:16px;height:16px;background:#ef4444;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.45)"></div>',
            iconSize: [16, 16], iconAnchor: [8, 8]
        });
        L.marker([location.lat, location.lon], { icon: userIcon }).addTo(_leafletMap).bindPopup('<b>Tu ubicacion</b>');

        requestAnimationFrame(() => _leafletMap && _leafletMap.invalidateSize());
    }

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
        const hasCoordsDoc = medicos.some(m => m.latitud && m.longitud);
        if (!hasCoordsDoc) return;

        const bounds = [[userLocation.lat, userLocation.lon]];
        medicos.forEach((m, i) => {
            if (!m.latitud || !m.longitud) return;
            const color   = i === 0 ? '#16a34a' : '#0891b2';
            const docIcon = L.divIcon({
                className: '',
                html: '<div style="width:13px;height:13px;background:' + color + ';border:2px solid white;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>',
                iconSize: [13, 13], iconAnchor: [6, 6]
            });
            const distLabel    = m.distancia_km ? parseFloat(m.distancia_km).toFixed(1) + ' km' : '';
            const closestBadge = i === 0 ? '<br><span style="color:#16a34a;font-weight:700">Mas cercano</span>' : '';
            L.marker([m.latitud, m.longitud], { icon: docIcon })
                .addTo(_doctorLayerGroup)
                .bindPopup('<b>' + (m.nombre || 'Medico') + '</b>' + closestBadge + '<br>' + (m.direccion || '') + '<br>' + distLabel);
            bounds.push([m.latitud, m.longitud]);
        });

        if (bounds.length > 1) {
            _leafletMap.fitBounds(bounds, { padding: [32, 32], maxZoom: 15 });
        }
    }

    function seleccionarGenero(sexo) {
        sexoSeleccionado = sexo;
        document.getElementById('sexo').value = sexo;
        const male   = document.getElementById('genderMale');
        const female = document.getElementById('genderFemale');
        male.classList.remove('selected-male','selected-female');
        female.classList.remove('selected-male','selected-female');
        if (sexo === 'masculino') male.classList.add('selected-male');
        else female.classList.add('selected-female');
    }

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
            const current = input.value.split(',').map(x => x.trim()).filter(Boolean);
            if (chip.classList.contains('active')) {
                if (!current.includes(val)) current.push(val);
            } else {
                const idx = current.indexOf(val);
                if (idx > -1) current.splice(idx, 1);
            }
            input.value = current.join(', ');
        });
    });

    const ta = document.getElementById('sintomas');
    ta.addEventListener('input', actualizarContador);
    function actualizarContador() {
        document.getElementById('contador').textContent = ta.value.length + ' / 500';
    }

    function limpiar() {
        ['sintomas','enfermedades','edad','sexo'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('tiempoEvolucion').value = '';
        document.getElementById('contador').textContent = '0 / 500';
        sexoSeleccionado = null;
        document.getElementById('genderMale').classList.remove('selected-male','selected-female');
        document.getElementById('genderFemale').classList.remove('selected-male','selected-female');
        document.querySelectorAll('.chip.active, .chip-antec.active').forEach(c => c.classList.remove('active'));
        ocultarResultados(); ocultarError();
    }

    async function buscarMedico() {
        const sintomas = document.getElementById('sintomas').value.trim();
        if (!sintomas || sintomas.length < 5) {
            mostrarError('Por favor describe tus sintomas con un poco mas de detalle.');
            return;
        }
        ocultarError(); ocultarResultados(); mostrarCarga(true); bloquearBtn(true);

        userLocation = await getLocation();

        try {
            const tiempoEvolucion = document.getElementById('tiempoEvolucion').value || null;
            const body = {
                sintomas,
                enfermedades_previas: document.getElementById('enfermedades').value.trim() || null,
                edad: parseInt(document.getElementById('edad').value) || null,
                sexo: document.getElementById('sexo').value || null,
                tiempo_evolucion: tiempoEvolucion,
            };
            if (userLocation) {
                body.latitud  = userLocation.lat;
                body.longitud = userLocation.lon;
            }
            const resp = await fetch('/api/analizar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(body),
            });
            const data = await resp.json();
            if (!resp.ok) { mostrarError(data.message || 'Error al procesar la solicitud.'); return; }
            renderizarResultados(data);
        } catch(e) {
            mostrarError('Error de conexion. Verifica tu internet e intenta de nuevo.');
        } finally {
            mostrarCarga(false); bloquearBtn(false);
        }
    }

    const URGENCIA_CONFIG = {
        normal: {
            badge:   'urgencia-normal',
            label:   'Prioridad orientativa: baja',
            icon:    '🟢',
            mensaje: 'Puedes agendar una cita con el especialista sugerido en los proximos dias.',
            alertBg: 'bg-green-50 border-green-200',
            alertTitle: 'Sin senales de alerta inmediata',
            alertTitleColor: 'text-green-700',
            alertMsgColor: 'text-green-600',
            alertIcon: '&#10003;',
        },
        urgente: {
            badge:   'urgencia-urgente',
            label:   'Prioridad orientativa: atencion pronta',
            icon:    '🟡',
            mensaje: 'Se sugiere buscar consulta medica lo antes posible, preferiblemente en las proximas horas.',
            alertBg: 'bg-amber-50 border-amber-200',
            alertTitle: 'Atencion pronta recomendada',
            alertTitleColor: 'text-amber-700',
            alertMsgColor: 'text-amber-600',
            alertIcon: '&#9888;',
        },
        critico: {
            badge:   'urgencia-critico',
            label:   'Prioridad orientativa: atencion inmediata',
            icon:    '🔴',
            mensaje: 'Los sintomas descritos pueden requerir atencion urgente. Acude a urgencias o llama a servicios de emergencia.',
            alertBg: 'bg-red-50 border-red-200',
            alertTitle: 'Senales de alerta detectadas en los sintomas',
            alertTitleColor: 'text-red-600',
            alertMsgColor: 'text-red-500',
            alertIcon: '&#128680;',
        },
    };

    let _allEspecialidades = [];

    function renderizarResultados(data) {
        _allEspecialidades = data.especialidades || [];

        const urg    = data.nivel_urgencia || 'normal';
        const urgCfg = URGENCIA_CONFIG[urg] || URGENCIA_CONFIG.normal;

        let bloqueHtml = '';

        bloqueHtml += '<div class="flex flex-wrap items-center gap-2 mb-3">'
            + '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold ' + urgCfg.badge + '">'
            + urgCfg.icon + ' ' + urgCfg.label
            + '</span></div>';

        bloqueHtml += '<div class="rounded-xl px-4 py-3 flex gap-3 items-start mb-3 ' + urgCfg.alertBg + ' border">'
            + '<span class="text-xl flex-shrink-0">' + urgCfg.alertIcon + '</span>'
            + '<div><p class="font-bold ' + urgCfg.alertTitleColor + ' text-sm">' + urgCfg.alertTitle + '</p>'
            + '<p class="text-xs ' + urgCfg.alertMsgColor + ' mt-0.5">' + urgCfg.mensaje + '</p></div></div>';

        if (data.resumen_ia) {
            bloqueHtml += '<div class="rounded-xl px-4 py-3 flex gap-3 items-start mb-3 bg-cyan-50 border border-cyan-200">'
                + '<span class="text-lg flex-shrink-0">&#128314;</span>'
                + '<div><p class="text-xs font-semibold text-cyan-700 mb-0.5 uppercase tracking-wider">Resumen clinico orientativo</p>'
                + '<p class="text-sm text-slate-600 leading-relaxed">' + data.resumen_ia + '</p></div></div>';
        }

        if (data.terminos_extraidos && data.terminos_extraidos.length > 0) {
            const chips = data.terminos_extraidos.map(t =>
                '<span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-700 border border-cyan-200">' + t + '</span>'
            ).join(' ');
            bloqueHtml += '<div class="flex flex-wrap gap-1.5">'
                + '<span class="text-xs text-slate-400 self-center mr-1">Sintomas identificados:</span>' + chips + '</div>';
        }

        bloqueHtml += '<p class="text-xs text-slate-400 mt-3 pt-3 border-t border-slate-100">Esta orientacion no constituye un diagnostico medico. Consulta siempre con un profesional de salud.</p>';

        document.getElementById('bloqueResumen').innerHTML = bloqueHtml;

        const lista = document.getElementById('listaResultados');
        lista.innerHTML = '';

        if (_allEspecialidades.length === 0) {
            lista.innerHTML = '<div class="text-center py-10 fade-up"><div class="text-3xl mb-2">&#128269;</div>'
                + '<p class="font-semibold text-slate-700 mb-1">Sin resultados claros</p>'
                + '<p class="text-sm text-slate-400">Intenta describir con mas detalle los sintomas.</p></div>';
            document.getElementById('resultados').classList.remove('hidden');
            document.getElementById('encabezadoEspecialidades').classList.add('hidden');
            return;
        }

        document.getElementById('encabezadoEspecialidades').classList.remove('hidden');
        document.getElementById('resultados').classList.remove('hidden');

        _allEspecialidades.forEach((esp, i) => renderEspCard(esp, i, i === 0));

        if (userLocation) {
            document.getElementById('mapaCol').classList.remove('hidden');
            initMap(userLocation);
            const topMedicos = (_allEspecialidades[0].medicos && _allEspecialidades[0].medicos.data)
                ? _allEspecialidades[0].medicos.data
                : (_allEspecialidades[0].medicos || []);
            addDoctorMarkers(topMedicos, _allEspecialidades[0].especialidad);
        }

        document.getElementById('resultados').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderEspCard(esp, i, expanded) {
        const lista = document.getElementById('listaResultados');
        const badgeClass = i === 0 ? 'badge-1' : i === 1 ? 'badge-2' : 'badge-3';
        const posLabel   = i === 0 ? 'Mayor probabilidad' : 'Opcion ' + (i + 1);
        const pct        = Math.min(Math.round((esp.puntuacion / 60) * 100), 100);
        const barColor   = esp.urgencia === 'urgente' ? '#ef4444' : i === 0 ? '#0891b2' : '#94a3b8';

        let termChips = '';
        if (esp.terminos_matched && esp.terminos_matched.length > 0) {
            termChips = esp.terminos_matched.slice(0, 5).map(t =>
                '<span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">' + t + '</span>'
            ).join('');
        }

        const medicos = ((esp.medicos && esp.medicos.data) ? esp.medicos.data : (esp.medicos || [])).slice(0, 3);
        let medicosHtml = '';

        if (medicos.length > 0) {
            medicosHtml = '<div class="mt-3 pt-3 border-t border-slate-100 space-y-2">'
                + '<p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Medicos cercanos de esta especialidad</p>';
            medicos.forEach((m, mi) => {
                const rate = m.calificacion_promedio
                    ? '<div class="flex items-center gap-1 mt-0.5">'
                        + '<svg class="w-3 h-3 text-yellow-400" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>'
                        + '<span class="text-xs text-slate-400">' + parseFloat(m.calificacion_promedio).toFixed(1) + '</span></div>'
                    : '';
                const distBadge = m.distancia_km
                    ? '<span class="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0 '
                        + (mi === 0 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-cyan-50 text-cyan-700 border border-cyan-200')
                        + '">' + (mi === 0 ? '&#11088; ' : '') + parseFloat(m.distancia_km).toFixed(1) + ' km</span>'
                    : '<span class="ml-auto text-xs text-slate-400 flex-shrink-0">Distancia no disponible</span>';
                medicosHtml += '<div class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 bg-slate-50 border border-slate-100">'
                    + '<div class="doc-avatar"><svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.125a7.5 7.5 0 0114.998 0"/>'
                    + '</svg></div>'
                    + '<div class="flex-1 min-w-0">'
                    + '<p class="font-semibold text-slate-700 text-sm truncate">' + (m.nombre || 'Medico') + '</p>'
                    + '<p class="text-xs text-slate-400 truncate">' + esp.especialidad + '</p>'
                    + rate + '</div>' + distBadge + '</div>';
            });
            medicosHtml += '</div>';
        } else {
            medicosHtml = '<div class="mt-3 pt-3 border-t border-slate-100"><p class="text-xs italic text-slate-400">No hay medicos registrados para esta especialidad.</p></div>';
        }

        if (!userLocation) {
            medicosHtml += '<p class="text-xs text-slate-400 mt-2">La ubicacion GPS no esta disponible. El ordenamiento por cercania puede no estar activo.</p>';
        }

        const chevronOpen   = '<svg class="w-4 h-4 text-slate-400 rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
        const chevronClosed = '<svg class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';

        const card = document.createElement('div');
        card.className = 'result-card fade-up overflow-hidden';
        card.dataset.idx = i;

        const redFlagHtml = (esp.red_flags && esp.red_flags.length > 0) ? '<span class="text-red-500 text-xs font-medium flex-shrink-0">&#128680;</span>' : '';
        const justHtml    = esp.justificacion
            ? '<div class="mt-3 rounded-xl px-3 py-2.5 bg-slate-50 border border-slate-100">'
                + '<p class="text-xs font-semibold text-slate-500 mb-1">Por que se sugiere esta especialidad?</p>'
                + '<p class="text-xs text-slate-500 leading-relaxed">' + esp.justificacion + '</p>'
                + (termChips ? '<div class="flex flex-wrap gap-1 mt-2">' + termChips + '</div>' : '')
                + '</div>'
            : '';

        card.innerHTML =
            '<button type="button" class="esp-header w-full text-left" data-idx="' + i + '">'
            + '<div class="flex items-center justify-between gap-2">'
            + '<div class="flex items-center gap-2 min-w-0">'
            + '<span class="' + badgeClass + ' inline-block px-2 py-0.5 rounded-full text-xs font-bold flex-shrink-0">' + posLabel + '</span>'
            + '<h3 class="font-bold text-slate-800 text-sm leading-tight truncate">' + esp.especialidad + '</h3>'
            + redFlagHtml + '</div>'
            + '<span class="chevron flex-shrink-0">' + (expanded ? chevronOpen : chevronClosed) + '</span></div>'
            + '<div class="flex items-center gap-2 mt-2">'
            + '<span class="text-xs text-slate-400 flex-shrink-0">Relevancia</span>'
            + '<div class="flex-1 rounded-full h-1 bg-slate-100"><div style="background:' + barColor + '; height:4px; border-radius:999px; width:' + pct + '%; transition:width .6s ease;"></div></div>'
            + '<span class="text-xs text-slate-400">' + pct + '%</span></div></button>'
            + '<div class="esp-body ' + (expanded ? '' : 'hidden') + '">' + justHtml + medicosHtml + '</div>';

        lista.appendChild(card);
    }

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

        if (userLocation && _allEspecialidades[idx]) {
            const medicos = (_allEspecialidades[idx].medicos && _allEspecialidades[idx].medicos.data)
                ? _allEspecialidades[idx].medicos.data
                : (_allEspecialidades[idx].medicos || []);
            addDoctorMarkers(medicos, _allEspecialidades[idx].especialidad);
        }
    });

    function mostrarCarga(v)     { document.getElementById('estadoCarga').classList.toggle('hidden', !v); }
    function ocultarResultados() {
        document.getElementById('resultados').classList.add('hidden');
        document.getElementById('listaResultados').innerHTML = '';
        document.getElementById('bloqueResumen').innerHTML = '';
        document.getElementById('encabezadoEspecialidades').classList.add('hidden');
        document.getElementById('mapaCol').classList.add('hidden');
        if (_doctorLayerGroup) { _doctorLayerGroup.clearLayers(); }
        if (_leafletMap) { _leafletMap.remove(); _leafletMap = null; _doctorLayerGroup = null; }
        _allEspecialidades = [];
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
            ? '<svg class="w-5 h-5 spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg> Analizando...'
            : '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg> Obtener orientacion';
    }

    document.getElementById('sintomas').addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') buscarMedico();
    });
    </script>
</body>
</html>
