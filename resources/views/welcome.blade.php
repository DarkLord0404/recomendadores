<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MediRecomienda — Encuentra tu médico ideal</title>
    <link rel="icon" type="image/png" href="/recomienda.png">
    <link rel="apple-touch-icon" href="/recomienda.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

        /* ── Fondo médico claro ── */
        body {
            background: linear-gradient(160deg, #f0fdff 0%, #f8fafc 50%, #ecfdf5 100%);
            min-height: 100vh;
            color: #1e293b;
        }

        /* ── Cards ── */
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(8,145,178,0.06);
        }
        .card-inner {
            background: #f8fdff;
            border: 1px solid #cffafe;
            border-radius: 12px;
        }

        /* ── Chips ── */
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

        /* ── Gender cards ── */
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
        .gender-card .g-sub   { font-size: 10.5px; opacity: .6; display: block; }

        /* ── Inputs ── */
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

        /* ── Button ── */
        .btn-primary {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white; border: none; border-radius: 11px;
            padding: 12px 28px; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all .15s ease;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(8,145,178,0.28);
            letter-spacing: -0.01em;
        }
        .btn-primary:hover   { transform: translateY(-1px); box-shadow: 0 6px 22px rgba(8,145,178,0.38); filter: brightness(1.06); }
        .btn-primary:active  { transform: translateY(0) scale(.98); }
        .btn-primary:disabled{ background: #94a3b8; box-shadow: none; cursor: not-allowed; transform: none; filter: none; }

        /* ── Section label ── */
        .section-label {
            display: block; font-size: 10px; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase; color: #0891b2;
        }

        /* ── Divider ── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #cffafe, transparent);
        }

        /* ── Textarea ── */
        #sintomas:focus { outline: none; border-color: #0891b2; background: #f0fdff; box-shadow: 0 0 0 3px rgba(8,145,178,0.12); }

        /* ── Animations ── */
        @keyframes spin   { to { transform: rotate(360deg); } }
        @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        @keyframes pulse  { 0%,100%{opacity:1;} 50%{opacity:.45;} }
        .spin    { animation: spin .8s linear infinite; }
        .fade-up { animation: fadeUp .3s ease forwards; }
        .pulse   { animation: pulse 2s ease infinite; }

        /* ── Result cards ── */
        .result-card {
            background: #fff; border: 1.5px solid #e2e8f0;
            border-radius: 16px; padding: 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: all .2s ease;
        }
        .result-card:hover { border-color: #a5f3fc; box-shadow: 0 4px 18px rgba(8,145,178,0.1); }

        /* ── Doc avatar ── */
        .doc-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #ecfeff, #f0fdf4);
            border: 1.5px solid #a5f3fc;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* ── Badge ── */
        .badge-1 { background:#fef9c3; color:#a16207; border:1px solid #fde047; }
        .badge-2 { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
        .badge-3 { background:#fff7ed; color:#c2410c; border:1px solid #fdba74; }

        /* ── Chips scroll container mobile ── */
        .chips-scroll {
            display: flex; flex-wrap: wrap; gap: 6px;
        }
        @media (max-width: 640px) {
            .chips-scroll {
                flex-wrap: nowrap; overflow-x: auto;
                padding-bottom: 4px; -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .chips-scroll::-webkit-scrollbar { display: none; }
        }

        /* ── Mobile tweaks ── */
        @media (max-width: 640px) {
            .hero-title { font-size: 28px !important; line-height: 1.2; }
            .form-grid  { grid-template-columns: 1fr !important; }
            .btn-primary { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- ══════════ HEADER ══════════ -->
    <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90" style="backdrop-filter:blur(16px);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <img src="/recomienda.png" alt="MediRecomienda" class="w-9 h-9 rounded-xl object-cover flex-shrink-0" style="box-shadow:0 2px 8px rgba(8,145,178,0.2);">
                <div>
                    <span class="text-base font-extrabold text-slate-800 tracking-tight leading-none">MediRecomienda</span>
                    <span class="hidden sm:flex items-center gap-1 text-[10px] font-semibold text-cyan-600 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 pulse inline-block"></span>
                        IA Médica activa
                    </span>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-1.5 bg-cyan-50 border border-cyan-200 px-3 py-1.5 rounded-full">
                <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
                <span class="text-xs font-medium text-cyan-700">GPT-4o-mini</span>
            </div>
        </div>
    </header>

    <!-- ══════════ CONTENIDO ══════════ -->
    <main class="max-w-2xl mx-auto px-4 sm:px-6 pt-6 pb-16">

        <!-- Hero compacto -->
        <div class="text-center mb-6">
            <h1 class="hero-title text-3xl sm:text-4xl font-extrabold text-slate-800 leading-tight mb-2 tracking-tight">
                ¿Cómo te sientes
                <span style="background:linear-gradient(135deg,#0891b2,#059669); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;"> hoy?</span>
            </h1>
            <p class="text-slate-500 text-sm max-w-md mx-auto">
                Describe tus síntomas y la IA te recomienda el especialista ideal.
            </p>
        </div>

        <!-- ══ FORMULARIO ══ -->
        <div class="card p-4 sm:p-6 mb-5">

            <!-- Fila 1: Sexo + Edad juntos -->
            <div class="mb-4">
                <span class="section-label mb-2">Sexo biológico y edad <span class="normal-case font-normal text-slate-400">(opcional)</span></span>
                <div class="flex gap-2 mt-2">
                    <button type="button" class="gender-card" id="genderMale" onclick="seleccionarGenero('masculino')">
                        <span class="g-icon">♂</span>
                        <span><span class="g-label">Hombre</span><span class="g-sub">Masculino</span></span>
                    </button>
                    <button type="button" class="gender-card" id="genderFemale" onclick="seleccionarGenero('femenino')">
                        <span class="g-icon">♀</span>
                        <span><span class="g-label">Mujer</span><span class="g-sub">Femenino</span></span>
                    </button>
                    <input id="edad" type="number" min="0" max="120" placeholder="Edad" class="field" style="max-width:90px; flex-shrink:0;">
                </div>
                <input type="hidden" id="sexo" value="">
            </div>

            <div class="divider mb-4"></div>

            <!-- Síntomas -->
            <div class="mb-3">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="section-label">Síntomas</span>
                    <span id="contador" class="text-xs text-slate-400">0 / 500</span>
                </div>
                <textarea
                    id="sintomas"
                    rows="4"
                    maxlength="500"
                    placeholder="Ej: Llevo 3 días con dolor de cabeza, fiebre de 38°C y tos seca..."
                    class="field resize-none"
                ></textarea>
            </div>

            <!-- Chips síntomas (scroll horizontal en móvil) -->
            <div class="mb-4">
                <span class="section-label mb-2">Síntomas frecuentes</span>
                <div id="chips" class="chips-scroll mt-2">
                    @foreach ([
                        ['🤒','Fiebre'],['🤕','Dolor de cabeza'],['🤧','Tos'],
                        ['😮‍💨','Falta de aire'],['🤢','Náuseas'],['💢','Dolor de pecho'],
                        ['🦴','Dolor muscular'],['😴','Cansancio'],['🫃','Dolor abdominal'],
                        ['💊','Mareos'],['🌡️','Escalofríos'],['😔','Tristeza / ansiedad'],
                    ] as [$icon, $label])
                    <button type="button" class="chip" data-sintoma="{{ $label }}">{{ $icon }} {{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="divider mb-4"></div>

            <!-- Antecedentes -->
            <div class="mb-4">
                <span class="section-label mb-1.5">Antecedentes médicos <span class="normal-case font-normal text-slate-400">(opcional)</span></span>
                <input id="enfermedades" type="text" placeholder="Ej: Diabetes, hipertensión, asma..." class="field mt-1.5">
            </div>

            <!-- Botón -->
            <div class="flex flex-col sm:flex-row items-center gap-2">
                <button id="btnBuscar" onclick="buscarMedico()" class="btn-primary w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    Buscar mi médico ideal
                </button>
                <button type="button" onclick="limpiar()" class="text-sm text-slate-400 hover:text-slate-600 transition-colors px-2 py-2 self-center">
                    Limpiar
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

        <!-- ══ CARGANDO ══ -->
        <div id="estadoCarga" class="hidden text-center py-12">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-cyan-50 border border-cyan-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-cyan-500 spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </div>
            <p class="text-slate-700 font-semibold mb-1">Consultando con la IA médica...</p>
            <p class="text-slate-400 text-sm">Esto puede tardar unos segundos</p>
        </div>

        <!-- ══ RESULTADOS ══ -->
        <div id="resultados" class="hidden">
            <div class="flex items-center gap-2.5 mb-4">
                <div class="w-1 h-6 rounded-full flex-shrink-0 bg-cyan-500"></div>
                <h2 class="text-base font-bold text-slate-800">Especialistas recomendados</h2>
            </div>
            <div id="listaResultados" class="space-y-3"></div>
            <p class="text-center text-xs text-slate-400 mt-6 leading-relaxed">
                Recomendaciones orientativas generadas por IA. Consulta siempre a un profesional.
            </p>
        </div>

    </main>

    <!-- ══════════ FOOTER ══════════ -->
    <footer class="border-t border-slate-200 bg-white/60" style="backdrop-filter:blur(8px);">
        <div class="max-w-5xl mx-auto px-5 py-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">

                <!-- Marca -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <img src="/recomienda.png" alt="logo" class="w-7 h-7 rounded-lg object-cover">
                        <span class="font-bold text-slate-700 text-sm">MediRecomienda</span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Sistema inteligente de recomendación médica con IA. Conectamos pacientes con los especialistas que necesitan.
                    </p>
                </div>

                <!-- Aviso -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Aviso importante</p>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Este servicio <strong class="text-slate-500">no reemplaza</strong> la consulta médica presencial. Las recomendaciones son generadas por IA y tienen carácter orientativo.
                    </p>
                </div>

                <!-- Tech -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Tecnología</p>
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 flex-shrink-0"></span>
                            GPT-4o-mini — extracción de síntomas
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                            Scoring por keywords médicas
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500 flex-shrink-0"></span>
                            Laravel 11 + API REST
                        </div>
                    </div>
                </div>
            </div>

            <div class="divider mb-4"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="text-slate-400 text-xs">© {{ date('Y') }} MediRecomienda — Sistema de Recomendación Médica con IA.</p>
                <div class="flex items-center gap-1.5 text-xs text-slate-400">
                    <svg class="w-3 h-3 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    Hecho con IA para el cuidado de tu salud
                </div>
            </div>
        </div>
    </footer>

    <!-- ══════════ SCRIPTS ══════════ -->
    <script>

    let sexoSeleccionado = null;

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
                ta.value = ta.value.split(',').map(x => x.trim())
                    .filter(x => x.toLowerCase() !== s.toLowerCase()).join(', ');
            }
            actualizarContador();
        });
    });

    const ta = document.getElementById('sintomas');
    ta.addEventListener('input', actualizarContador);
    function actualizarContador() {
        document.getElementById('contador').textContent = `${ta.value.length} / 500`;
    }

    function limpiar() {
        ['sintomas','enfermedades','edad','sexo'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('contador').textContent = '0 / 500';
        sexoSeleccionado = null;
        document.getElementById('genderMale').classList.remove('selected-male','selected-female');
        document.getElementById('genderFemale').classList.remove('selected-male','selected-female');
        document.querySelectorAll('.chip.active').forEach(c => c.classList.remove('active'));
        ocultarResultados(); ocultarError();
    }

    async function buscarMedico() {
        const sintomas = document.getElementById('sintomas').value.trim();
        if (!sintomas || sintomas.length < 5) {
            mostrarError('Por favor describe tus síntomas con un poco más de detalle.');
            return;
        }
        ocultarError(); ocultarResultados(); mostrarCarga(true); bloquearBtn(true);
        try {
            const resp = await fetch('/api/analizar', {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    sintomas,
                    enfermedades_previas: document.getElementById('enfermedades').value.trim() || null,
                    edad: parseInt(document.getElementById('edad').value) || null,
                    sexo: document.getElementById('sexo').value || null,
                }),
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

    function renderizarResultados(data) {
        const lista = document.getElementById('listaResultados');
        lista.innerHTML = '';

        if (data.nivel_urgencia === 'critico') {
            lista.innerHTML += `<div class="rounded-xl px-4 py-3 flex gap-3 items-start mb-2 fade-up bg-red-50 border border-red-200">
                <span class="text-xl flex-shrink-0">🚨</span>
                <div><p class="font-bold text-red-600 text-sm">Señales de alerta detectadas</p>
                <p class="text-xs text-red-500 mt-0.5">Los síntomas pueden requerir atención urgente. Considera acudir a urgencias.</p></div></div>`;
        } else if (data.nivel_urgencia === 'urgente') {
            lista.innerHTML += `<div class="rounded-xl px-4 py-3 flex gap-3 items-start mb-2 fade-up bg-amber-50 border border-amber-200">
                <span class="text-xl flex-shrink-0">⚠️</span>
                <div><p class="font-bold text-amber-600 text-sm">Atención pronta recomendada</p>
                <p class="text-xs text-amber-500 mt-0.5">Consulta con un especialista lo antes posible.</p></div></div>`;
        }

        if (data.resumen_ia) {
            lista.innerHTML += `<div class="rounded-xl px-4 py-3 mb-3 flex gap-3 items-start fade-up bg-cyan-50 border border-cyan-200">
                <span class="text-lg flex-shrink-0">🩺</span>
                <p class="text-sm text-slate-600 leading-relaxed"><em>${data.resumen_ia}</em></p></div>`;
        }

        if (data.terminos_extraidos && data.terminos_extraidos.length > 0) {
            const chips = data.terminos_extraidos.map(t =>
                `<span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-700 border border-cyan-200">${t}</span>`
            ).join(' ');
            lista.innerHTML += `<div class="flex flex-wrap gap-1.5 mb-4 fade-up">
                <span class="text-xs text-slate-400 self-center mr-1">Detectado:</span>${chips}</div>`;
        }

        const especialidades = data.especialidades ?? [];
        if (especialidades.length === 0) {
            lista.innerHTML += `<div class="text-center py-10 fade-up">
                <div class="text-3xl mb-2">🔍</div>
                <p class="font-semibold text-slate-700 mb-1">Sin resultados claros</p>
                <p class="text-sm text-slate-400">Intenta describir con más detalle.</p></div>`;
            document.getElementById('resultados').classList.remove('hidden');
            return;
        }

        especialidades.forEach((esp, i) => {
            const badgeClass = i === 0 ? 'badge-1' : i === 1 ? 'badge-2' : 'badge-3';
            const posLabel   = i === 0 ? '⭐ Mejor match' : `#${i + 1}`;

            let medicosHtml = '';
            const medicos = esp.medicos?.data ?? esp.medicos ?? [];
            if (medicos.length > 0) {
                medicosHtml = `<div class="mt-3 pt-3 border-t border-slate-100">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Médicos disponibles</p>
                    <div class="space-y-1.5">`;
                medicos.forEach(m => {
                    const rate = m.calificacion_promedio
                        ? `<div class="flex items-center gap-1 mt-0.5">
                            <svg class="w-3 h-3 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-xs text-slate-400">${parseFloat(m.calificacion_promedio).toFixed(1)}</span></div>` : '';
                    medicosHtml += `<div class="flex items-center gap-2.5 rounded-xl px-3 py-2 bg-slate-50 border border-slate-100">
                        <div class="doc-avatar"><svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.125a7.5 7.5 0 0114.998 0"/>
                        </svg></div>
                        <div><p class="font-semibold text-slate-700 text-sm">Dr. ${m.nombre ?? 'Médico'}</p>${rate}</div></div>`;
                });
                medicosHtml += '</div></div>';
            } else {
                medicosHtml = `<div class="mt-3 pt-3 border-t border-slate-100">
                    <p class="text-xs italic text-slate-400">No hay médicos registrados en esta especialidad aún.</p></div>`;
            }

            const pct = Math.min(Math.round((esp.puntuacion / 60) * 100), 100);
            const barColor = esp.urgencia === 'urgente' ? '#ef4444' : i === 0 ? '#0891b2' : '#94a3b8';

            lista.innerHTML += `<div class="result-card fade-up">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div><h3 class="font-bold text-slate-800 text-base leading-tight">${esp.especialidad}</h3>
                    ${esp.red_flags && esp.red_flags.length > 0 ? `<p class="text-red-500 text-xs font-medium mt-0.5">🚨 ${esp.red_flags.join(' · ')}</p>` : ''}</div>
                    <span class="${badgeClass} inline-block px-2 py-0.5 rounded-full text-xs font-bold flex-shrink-0">${posLabel}</span>
                </div>
                ${esp.justificacion ? `<p class="text-xs text-slate-500 leading-relaxed mb-3 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2">${esp.justificacion}</p>` : ''}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">Afinidad</span>
                    <div class="flex-1 rounded-full h-1.5 bg-slate-100">
                        <div style="background:${barColor}; height:6px; border-radius:999px; width:${pct}%; transition:width .6s ease;"></div>
                    </div>
                    <span class="text-xs font-bold text-slate-500">${esp.puntuacion} pts</span>
                </div>
                ${medicosHtml}</div>`;
        });

        const res = document.getElementById('resultados');
        res.classList.remove('hidden');
        res.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function mostrarCarga(v)     { document.getElementById('estadoCarga').classList.toggle('hidden', !v); }
    function ocultarResultados() { document.getElementById('resultados').classList.add('hidden'); document.getElementById('listaResultados').innerHTML = ''; }
    function mostrarError(msg)   { document.getElementById('msgErrorText').textContent = msg; document.getElementById('msgError').classList.remove('hidden'); }
    function ocultarError()      { document.getElementById('msgError').classList.add('hidden'); }
    function bloquearBtn(v) {
        const btn = document.getElementById('btnBuscar');
        btn.disabled = v;
        btn.innerHTML = v
            ? `<svg class="w-5 h-5 spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg> Analizando...`
            : `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg> Buscar mi médico ideal`;
    }

    document.getElementById('sintomas').addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') buscarMedico();
    });
    </script>
</body>
</html>
