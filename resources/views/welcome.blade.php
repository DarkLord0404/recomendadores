<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MediRecomienda — Encuentra tu médico ideal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>

    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #0f172a 100%);
            min-height: 100vh;
        }

        /* Glass cards */
        .glass {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        /* Chips */
        .chip {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px; border-radius: 9999px; font-size: 13px;
            border: 1px solid rgba(255,255,255,0.13); cursor: pointer;
            transition: all .15s ease;
            background: rgba(255,255,255,0.04); color: #94a3b8;
            user-select: none;
        }
        .chip:hover  { border-color: #6366f1; color: #a5b4fc; background: rgba(99,102,241,0.14); }
        .chip.active { border-color: #6366f1; color: #a5b4fc; background: rgba(99,102,241,0.18); }

        /* Gender cards */
        .gender-card {
            flex: 1; padding: 14px 18px; border-radius: 14px;
            border: 1.5px solid rgba(255,255,255,0.10);
            background: rgba(255,255,255,0.04);
            cursor: pointer; transition: all .2s ease;
            display: flex; align-items: center; gap: 10px; color: #94a3b8;
            text-align: left;
        }
        .gender-card:hover { border-color: rgba(99,102,241,0.5); background: rgba(99,102,241,0.09); color: #c7d2fe; }
        .gender-card.selected-male   { border-color: #3b82f6; background: rgba(59,130,246,0.13); color: #93c5fd; }
        .gender-card.selected-female { border-color: #ec4899; background: rgba(236,72,153,0.13); color: #f9a8d4; }
        .gender-card .g-icon  { font-size: 24px; line-height: 1; flex-shrink: 0; }
        .gender-card .g-label { font-weight: 700; font-size: 14px; display: block; }
        .gender-card .g-sub   { font-size: 11px; opacity: .6; display: block; margin-top: 1px; }

        /* Form inputs */
        .field {
            width: 100%; border-radius: 11px;
            border: 1px solid rgba(255,255,255,0.11);
            background: rgba(255,255,255,0.05);
            padding: 11px 15px; font-size: 14px; color: #e2e8f0;
            transition: all .15s ease;
        }
        .field::placeholder { color: #3f4f6a; }
        .field:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(99,102,241,0.08);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.14);
        }

        /* Button */
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white; border: none; border-radius: 12px;
            padding: 13px 30px; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all .15s ease;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 18px rgba(99,102,241,0.33);
            letter-spacing: -0.01em;
        }
        .btn-primary:hover   { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(99,102,241,0.43); filter: brightness(1.08); }
        .btn-primary:active  { transform: translateY(0) scale(.98); }
        .btn-primary:disabled{ background: linear-gradient(135deg, #3a3c6e 0%, #393473 100%); box-shadow: none; cursor: not-allowed; transform: none; filter: none; }

        /* Textarea */
        #sintomas:focus { outline: none; border-color: #6366f1; background: rgba(99,102,241,0.08); box-shadow: 0 0 0 3px rgba(99,102,241,0.14); }

        /* Animations */
        @keyframes spin    { to { transform: rotate(360deg); } }
        @keyframes fadeUp  { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }
        @keyframes pulse   { 0%,100% { opacity:1; } 50% { opacity:.5; } }
        .spin    { animation: spin .8s linear infinite; }
        .fade-up { animation: fadeUp .32s ease forwards; }
        .pulse   { animation: pulse 2s ease infinite; }

        /* Result cards */
        .result-card {
            border-radius: 16px; padding: 20px;
            background: rgba(255,255,255,0.035);
            border: 1px solid rgba(255,255,255,0.09);
            transition: all .2s ease;
        }
        .result-card:hover { background: rgba(255,255,255,0.06); border-color: rgba(99,102,241,0.28); }

        /* Section label */
        .section-label {
            display: block;
            font-size: 10.5px; font-weight: 700; letter-spacing: .09em;
            text-transform: uppercase; color: #6366f1;
        }

        /* Gradient divider */
        .grad-line {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(99,102,241,0.35), transparent);
        }

        /* Doc avatar */
        .doc-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(99,102,241,0.25), rgba(45,212,191,0.25));
            border: 1.5px solid rgba(99,102,241,0.35);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* Badge colours */
        .badge-1 { background:rgba(251,191,36,0.13); color:#fbbf24; border:1px solid rgba(251,191,36,0.28); }
        .badge-2 { background:rgba(148,163,184,0.10); color:#94a3b8; border:1px solid rgba(148,163,184,0.2);  }
        .badge-3 { background:rgba(251,146,60,0.11);  color:#fb923c; border:1px solid rgba(251,146,60,0.24); }
    </style>
</head>
<body>

    <!-- ══════════ HEADER ══════════ -->
    <header class="sticky top-0 z-20 border-b" style="border-color:rgba(255,255,255,0.07); background:rgba(15,23,42,0.88); backdrop-filter:blur(20px);">
        <div class="max-w-5xl mx-auto px-5 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#6366f1,#2dd4bf);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <span class="text-lg font-extrabold text-white tracking-tight">MediRecomienda</span>
                    <span class="hidden sm:inline ml-2 text-xs font-semibold px-2 py-0.5 rounded-full" style="background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.28); color:#a5b4fc;">IA Médica</span>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-1.5 text-xs text-slate-500">
                <svg class="w-3.5 h-3.5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
                GPT-4o-mini
            </div>
        </div>
    </header>

    <!-- ══════════ HERO ══════════ -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-12 pb-24">

        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 text-xs font-bold px-3 py-1.5 rounded-full mb-5 uppercase tracking-widest" style="background:rgba(99,102,241,0.13); border:1px solid rgba(99,102,241,0.28); color:#a5b4fc;">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 pulse"></span>
                Sistema inteligente de recomendación médica
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-white leading-tight mb-4 tracking-tight">
                ¿Cómo te sientes
                <span style="background:linear-gradient(135deg,#818cf8,#2dd4bf); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;"> hoy?</span>
            </h1>
            <p class="text-slate-400 text-base max-w-lg mx-auto leading-relaxed">
                Describe tus síntomas y te conectaremos con el especialista médico más adecuado para ti.
            </p>
        </div>

        <!-- ══ FORMULARIO ══ -->
        <div class="glass rounded-2xl p-6 sm:p-8 mb-6 shadow-2xl">

            <!-- Sexo biológico -->
            <div class="mb-6">
                <span class="section-label mb-3">Sexo biológico <span class="normal-case font-normal text-slate-600">(opcional)</span></span>
                <div class="flex gap-3 mt-3">
                    <button type="button" class="gender-card" id="genderMale" onclick="seleccionarGenero('masculino')">
                        <span class="g-icon">♂</span>
                        <span>
                            <span class="g-label">Hombre</span>
                            <span class="g-sub">Sexo masculino</span>
                        </span>
                    </button>
                    <button type="button" class="gender-card" id="genderFemale" onclick="seleccionarGenero('femenino')">
                        <span class="g-icon">♀</span>
                        <span>
                            <span class="g-label">Mujer</span>
                            <span class="g-sub">Sexo femenino</span>
                        </span>
                    </button>
                </div>
                <input type="hidden" id="sexo" value="">
            </div>

            <div class="grad-line mb-6"></div>

            <!-- Síntomas -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="section-label">Describe tus síntomas</span>
                    <span id="contador" class="text-xs text-slate-600">0 / 500</span>
                </div>
                <textarea
                    id="sintomas"
                    rows="5"
                    maxlength="500"
                    placeholder="Ej: Llevo 3 días con dolor de cabeza intenso, fiebre de 38°C, cansancio y tos seca..."
                    class="field resize-none"
                ></textarea>
            </div>

            <!-- Chips síntomas frecuentes -->
            <div class="mt-5">
                <span class="section-label mb-3">Toca para añadir síntomas frecuentes</span>
                <div id="chips" class="flex flex-wrap gap-2 mt-3">
                    @foreach ([
                        ['🤒','Fiebre'],
                        ['🤕','Dolor de cabeza'],
                        ['🤧','Tos'],
                        ['😮‍💨','Falta de aire'],
                        ['🤢','Náuseas'],
                        ['💢','Dolor de pecho'],
                        ['🦴','Dolor muscular'],
                        ['😴','Cansancio extremo'],
                        ['🫃','Dolor abdominal'],
                        ['💊','Mareos'],
                        ['🌡️','Escalofríos'],
                        ['😔','Tristeza / ansiedad'],
                    ] as [$icon, $label])
                    <button type="button" class="chip" data-sintoma="{{ $label }}">
                        {{ $icon }} {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="grad-line mt-6 mb-6"></div>

            <!-- Datos personales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="section-label mb-2">Edad <span class="normal-case font-normal text-slate-600">(opcional)</span></span>
                    <input id="edad" type="number" min="0" max="120" placeholder="Ej: 34 años" class="field mt-2">
                </div>
                <div>
                    <span class="section-label mb-2">Antecedentes médicos <span class="normal-case font-normal text-slate-600">(opcional)</span></span>
                    <input id="enfermedades" type="text" placeholder="Ej: Diabetes, hipertensión..." class="field mt-2">
                </div>
            </div>

            <!-- Botón enviar -->
            <div class="mt-7 flex flex-col sm:flex-row items-center gap-3">
                <button id="btnBuscar" onclick="buscarMedico()" class="btn-primary w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    Buscar mi médico ideal
                </button>
                <button type="button" onclick="limpiar()" class="text-sm text-slate-500 hover:text-slate-300 transition-colors px-3 py-2">
                    Limpiar todo
                </button>
            </div>

            <!-- Error -->
            <div id="msgError" class="hidden mt-4 rounded-xl px-4 py-3 text-sm text-red-300 flex items-center gap-2.5" style="background:rgba(239,68,68,0.09); border:1px solid rgba(239,68,68,0.24);">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span id="msgErrorText"></span>
            </div>
        </div>

        <!-- ══ RESULTADOS ══ -->
        <div id="resultados" class="hidden">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-1 h-7 rounded-full flex-shrink-0" style="background:linear-gradient(180deg,#6366f1,#2dd4bf);"></div>
                <h2 class="text-lg font-bold text-white">Especialistas recomendados para ti</h2>
            </div>
            <div id="listaResultados" class="space-y-4"></div>
            <p class="text-center text-xs text-slate-600 mt-8 leading-relaxed">
                Recomendaciones generadas por IA con carácter orientativo.<br>
                Consulta siempre a un profesional de la salud para un diagnóstico definitivo.
            </p>
        </div>

        <!-- ══ CARGANDO ══ -->
        <div id="estadoCarga" class="hidden text-center py-16">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center" style="background:rgba(99,102,241,0.13); border:1px solid rgba(99,102,241,0.25);">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-indigo-400 spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </div>
            <p class="text-white font-semibold text-lg mb-1">Analizando con IA médica...</p>
            <p class="text-slate-500 text-sm">Esto puede tardar unos segundos</p>
        </div>

    </main>

    <!-- ══════════ FOOTER ══════════ -->
    <footer class="border-t" style="border-color:rgba(255,255,255,0.07); background:rgba(10,16,34,0.7);">
        <div class="max-w-5xl mx-auto px-5 py-10">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 mb-8">

                <!-- Marca -->
                <div>
                    <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#6366f1,#2dd4bf);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="font-bold text-white text-sm">MediRecomienda</span>
                    </div>
                    <p class="text-slate-500 text-xs leading-relaxed">
                        Sistema inteligente de recomendación médica impulsado por inteligencia artificial. Conectamos pacientes con los especialistas que necesitan.
                    </p>
                </div>

                <!-- Aviso legal -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">Aviso importante</p>
                    <p class="text-slate-600 text-xs leading-relaxed">
                        Este servicio <strong class="text-slate-500">no reemplaza</strong> la consulta médica presencial. Las recomendaciones tienen carácter orientativo y son generadas por inteligencia artificial.
                    </p>
                </div>

                <!-- Stack tecnológico -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">Tecnología</p>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 flex-shrink-0"></span>
                            GPT-4o-mini — extracción de síntomas
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500 flex-shrink-0"></span>
                            Scoring por keywords médicas
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-violet-500 flex-shrink-0"></span>
                            Laravel 11 + API REST
                        </div>
                    </div>
                </div>
            </div>

            <div class="grad-line mb-5"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-slate-600 text-xs">© {{ date('Y') }} MediRecomienda — Sistema de Recomendación Médica con IA.</p>
                <div class="flex items-center gap-1.5 text-xs text-slate-700">
                    <svg class="w-3 h-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    Hecho con IA para el cuidado de tu salud
                </div>
            </div>
        </div>
    </footer>

    <!-- ══════════ SCRIPTS ══════════ -->
    <script>

    // ─── Estado género ────────────────────────────────────────────────
    let sexoSeleccionado = null;

    function seleccionarGenero(sexo) {
        sexoSeleccionado = sexo;
        document.getElementById('sexo').value = sexo;

        const male   = document.getElementById('genderMale');
        const female = document.getElementById('genderFemale');
        male.classList.remove('selected-male', 'selected-female');
        female.classList.remove('selected-male', 'selected-female');

        if (sexo === 'masculino') {
            male.classList.add('selected-male');
        } else {
            female.classList.add('selected-female');
        }
    }

    // ─── Chips ────────────────────────────────────────────────────────
    document.querySelectorAll('.chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const ta      = document.getElementById('sintomas');
            const sintoma = chip.dataset.sintoma;
            chip.classList.toggle('active');

            if (chip.classList.contains('active')) {
                ta.value = ta.value ? ta.value.trimEnd() + ', ' + sintoma : sintoma;
            } else {
                ta.value = ta.value
                    .split(',')
                    .map(s => s.trim())
                    .filter(s => s.toLowerCase() !== sintoma.toLowerCase())
                    .join(', ');
            }
            actualizarContador();
        });
    });

    // ─── Contador ─────────────────────────────────────────────────────
    const ta = document.getElementById('sintomas');
    ta.addEventListener('input', actualizarContador);
    function actualizarContador() {
        document.getElementById('contador').textContent = `${ta.value.length} / 500`;
    }

    // ─── Limpiar ──────────────────────────────────────────────────────
    function limpiar() {
        document.getElementById('sintomas').value      = '';
        document.getElementById('enfermedades').value  = '';
        document.getElementById('edad').value          = '';
        document.getElementById('sexo').value          = '';
        document.getElementById('contador').textContent = '0 / 500';

        sexoSeleccionado = null;
        document.getElementById('genderMale').classList.remove('selected-male', 'selected-female');
        document.getElementById('genderFemale').classList.remove('selected-male', 'selected-female');
        document.querySelectorAll('.chip.active').forEach(c => c.classList.remove('active'));

        ocultarResultados();
        ocultarError();
    }

    // ─── Buscar ───────────────────────────────────────────────────────
    async function buscarMedico() {
        const sintomas = document.getElementById('sintomas').value.trim();
        if (!sintomas || sintomas.length < 5) {
            mostrarError('Por favor describe tus síntomas con un poco más de detalle.');
            return;
        }

        ocultarError();
        ocultarResultados();
        mostrarCarga(true);
        bloquearBtn(true);

        try {
            const edad  = parseInt(document.getElementById('edad').value) || null;
            const sexo  = document.getElementById('sexo').value || null;
            const previas = document.getElementById('enfermedades').value.trim() || null;

            const resp = await fetch('/api/analizar', {
                method : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    sintomas,
                    enfermedades_previas: previas,
                    edad,
                    sexo,
                }),
            });

            const data = await resp.json();

            if (!resp.ok) {
                mostrarError(data.message || 'Ocurrió un error al procesar tu solicitud.');
                return;
            }

            renderizarResultados(data);

        } catch (e) {
            mostrarError('Error de conexión. Verifica tu internet e intenta de nuevo.');
        } finally {
            mostrarCarga(false);
            bloquearBtn(false);
        }
    }

    // ─── Renderizar resultados ────────────────────────────────────────
    function renderizarResultados(data) {
        const lista = document.getElementById('listaResultados');
        lista.innerHTML = '';

        // Banner urgencia
        if (data.nivel_urgencia === 'critico') {
            lista.innerHTML += `
            <div class="rounded-xl px-5 py-4 flex gap-3 items-start mb-2 fade-up" style="background:rgba(239,68,68,0.09);border:1.5px solid rgba(239,68,68,0.38);">
                <span class="text-2xl flex-shrink-0">🚨</span>
                <div>
                    <p class="font-bold text-red-400">Señales de alerta detectadas</p>
                    <p class="text-sm mt-0.5" style="color:rgba(248,113,113,0.8);">Los síntomas descritos pueden requerir atención médica urgente. Considera acudir a urgencias.</p>
                </div>
            </div>`;
        } else if (data.nivel_urgencia === 'urgente') {
            lista.innerHTML += `
            <div class="rounded-xl px-5 py-4 flex gap-3 items-start mb-2 fade-up" style="background:rgba(251,191,36,0.07);border:1.5px solid rgba(251,191,36,0.3);">
                <span class="text-2xl flex-shrink-0">⚠️</span>
                <div>
                    <p class="font-bold text-amber-400">Atención pronta recomendada</p>
                    <p class="text-sm mt-0.5" style="color:rgba(251,191,36,0.75);">Te recomendamos consultar con un especialista lo antes posible.</p>
                </div>
            </div>`;
        }

        // Resumen IA
        if (data.resumen_ia) {
            lista.innerHTML += `
            <div class="rounded-xl px-4 py-3 mb-4 flex gap-3 items-start fade-up" style="background:rgba(99,102,241,0.07);border:1px solid rgba(99,102,241,0.18);">
                <span class="text-lg flex-shrink-0">🩺</span>
                <p class="text-sm text-slate-300 leading-relaxed"><em>${data.resumen_ia}</em></p>
            </div>`;
        }

        // Términos detectados
        if (data.terminos_extraidos && data.terminos_extraidos.length > 0) {
            const chips = data.terminos_extraidos.map(t =>
                `<span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full" style="background:rgba(99,102,241,0.13);color:#a5b4fc;border:1px solid rgba(99,102,241,0.23);">${t}</span>`
            ).join(' ');
            lista.innerHTML += `
            <div class="flex flex-wrap gap-1.5 mb-5 fade-up">
                <span class="text-xs text-slate-600 self-center mr-1">Términos detectados:</span>
                ${chips}
            </div>`;
        }

        const especialidades = data.especialidades ?? [];

        if (especialidades.length === 0) {
            lista.innerHTML += `
            <div class="text-center py-12 fade-up">
                <div class="text-4xl mb-3">🔍</div>
                <p class="text-lg font-semibold text-white mb-1">Sin resultados claros</p>
                <p class="text-sm text-slate-500">Intenta describir tus síntomas con más detalle.</p>
            </div>`;
            document.getElementById('resultados').classList.remove('hidden');
            return;
        }

        // Tarjetas de especialidad
        especialidades.forEach((esp, i) => {
            const badgeClass = i === 0 ? 'badge-1' : i === 1 ? 'badge-2' : 'badge-3';
            const posLabel   = i === 0 ? '⭐ Mejor coincidencia' : `#${i + 1}`;

            let medicosHtml = '';
            const medicos = esp.medicos?.data ?? esp.medicos ?? [];

            if (medicos.length > 0) {
                medicosHtml = `
                <div class="mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,0.07);">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3">Médicos disponibles</p>
                    <div class="space-y-2">`;

                medicos.forEach(m => {
                    const rate = m.calificacion_promedio
                        ? `<div class="flex items-center gap-1 mt-0.5">
                            <svg class="w-3 h-3 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-xs text-slate-500">${parseFloat(m.calificacion_promedio).toFixed(1)}</span>
                           </div>`
                        : '';

                    medicosHtml += `
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5" style="background:rgba(255,255,255,0.035);border:1px solid rgba(255,255,255,0.07);">
                        <div class="doc-avatar">
                            <svg class="w-4 h-4 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.125a7.5 7.5 0 0114.998 0"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-white text-sm">Dr. ${m.nombre ?? 'Médico'}</p>
                            ${rate}
                        </div>
                    </div>`;
                });
                medicosHtml += '</div></div>';
            } else {
                medicosHtml = `
                <div class="mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,0.07);">
                    <p class="text-xs italic" style="color:#475569;">No hay médicos registrados aún en esta especialidad.</p>
                </div>`;
            }

            const pct = Math.min(Math.round((esp.puntuacion / 60) * 100), 100);
            const barColor = esp.urgencia === 'urgente' ? '#ef4444'
                : i === 0 ? '#6366f1' : '#475569';

            lista.innerHTML += `
            <div class="result-card fade-up">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <h3 class="font-bold text-white text-lg leading-tight">${esp.especialidad}</h3>
                        ${esp.red_flags && esp.red_flags.length > 0
                            ? `<p class="text-red-400 text-xs font-medium mt-1">🚨 ${esp.red_flags.join(' · ')}</p>`
                            : ''}
                    </div>
                    <span class="${badgeClass} inline-block px-2.5 py-1 rounded-full text-xs font-bold flex-shrink-0">${posLabel}</span>
                </div>

                ${esp.justificacion ? `
                <div class="rounded-xl px-4 py-3 mb-4 text-sm leading-relaxed" style="background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.14);color:#94a3b8;">
                    ${esp.justificacion}
                </div>` : ''}

                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs text-slate-600">Afinidad</span>
                    <div class="flex-1 rounded-full h-1.5" style="background:rgba(255,255,255,0.07);">
                        <div style="background:${barColor}; height:6px; border-radius:999px; width:${pct}%; transition:width .6s ease;"></div>
                    </div>
                    <span class="text-xs font-bold text-slate-400">${esp.puntuacion} pts</span>
                </div>

                ${medicosHtml}
            </div>`;
        });

        const res = document.getElementById('resultados');
        res.classList.remove('hidden');
        res.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ─── Helpers ──────────────────────────────────────────────────────
    function mostrarCarga(v) {
        document.getElementById('estadoCarga').classList.toggle('hidden', !v);
    }
    function ocultarResultados() {
        document.getElementById('resultados').classList.add('hidden');
        document.getElementById('listaResultados').innerHTML = '';
    }
    function mostrarError(msg) {
        document.getElementById('msgErrorText').textContent = msg;
        document.getElementById('msgError').classList.remove('hidden');
    }
    function ocultarError() {
        document.getElementById('msgError').classList.add('hidden');
    }
    function bloquearBtn(v) {
        const btn = document.getElementById('btnBuscar');
        btn.disabled = v;
        btn.innerHTML = v
            ? `<svg class="w-5 h-5 spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg> Analizando...`
            : `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg> Buscar mi médico ideal`;
    }

    // Ctrl+Enter
    document.getElementById('sintomas').addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') buscarMedico();
    });
    </script>
</body>
</html>
