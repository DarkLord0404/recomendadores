# Sistema Recomendador Médico — Documentación

## ¿Qué es?

Es un sistema recomendador **basado en contenido** (*content-based filtering*) que analiza la descripción libre de síntomas de un paciente y recomienda las **opciones médicas más adecuadas** — especialistas o médico general — junto con los **3 profesionales más cercanos** a su ubicación GPS.

El sistema **no diagnostica**. Orienta al paciente sobre qué tipo de atención médica buscar y dónde encontrarla cerca.

---

## Arquitectura general

```
Paciente describe síntomas (texto libre)
+ datos opcionales: sexo, edad, antecedentes, tiempo de evolución
        ↓
[Navegador] Solicita ubicación GPS (API Geolocation)
        ↓
[API REST - Laravel] POST /api/analizar
        ↓
┌──────────────────────────────────────────────────────┐
│                  RecomendadorService                  │
│                                                      │
│  1. GPT-4o-mini → extrae términos médicos            │
│     (vocabulario controlado + contexto clínico)      │
│                                                      │
│  2. KeywordMatcherService → puntúa las 12            │
│     especialidades por score ponderado               │
│                                                      │
│  3. Ajuste demográfico → si edad < 14 años,          │
│     aplica boost de Pediatría                        │
│                                                      │
│  4. MySQL → busca hasta 30 candidatos por            │
│     especialidad ganadora                            │
│                                                      │
│  5. Haversine → reordena por distancia al usuario    │
└──────────────────────────────────────────────────────┘
        ↓
[Respuesta JSON] Top 3 opciones médicas + 3 profesionales c/u
        ↓
[Frontend Blade + Leaflet.js] Acordeón + Mapa interactivo
```

---

## El motor recomendador: cómo funciona paso a paso

Este es el núcleo del trabajo. El proceso de recomendación combina tres mecanismos distintos que trabajan en secuencia.

---

### Paso 1 — Extracción de términos médicos (GPT-4o-mini)

**Archivo:** `app/Services/RecomendadorService.php` → `extraerTerminosViaGPT()`

El texto libre del paciente no se puede comparar directamente contra keywords: tiene variaciones ortográficas, coloquialismos y estructuras imprevisibles. GPT-4o-mini actúa como **normalizador semántico**: traduce el lenguaje natural a términos médicos canónicos del sistema.

**Contexto enviado al modelo:**
- Descripción de síntomas (obligatoria)
- Antecedentes médicos (si los hay)
- Edad y sexo biológico (si se proporcionan — influyen en prevalencia de enfermedades)
- Tiempo de evolución de los síntomas (si se indica)
- Lista completa de **todos los términos reconocidos** del sistema (extraída del JSON de keywords)

**Restricción crítica de seguridad:** el prompt instruye al modelo a usar **únicamente términos de esa lista**, sin inventar otros. Los términos devueltos se validan programáticamente contra el vocabulario antes de usarse (defensa contra alucinaciones).

**Respuesta esperada del modelo:**
```json
{
  "terminos_identificados": ["dolor toracico", "palpitaciones", "disnea"],
  "nivel_urgencia": "critico",
  "resumen_medico": "Paciente con dolor torácico, taquicardia y dificultad respiratoria."
}
```

| Campo | Valores posibles | Descripción |
|---|---|---|
| `terminos_identificados` | lista de strings | Términos del vocabulario controlado presentes en los síntomas |
| `nivel_urgencia` | `normal` / `urgente` / `critico` | Estimación de prioridad clínica |
| `resumen_medico` | string | Paráfrasis clínica de los síntomas (máx. 2 oraciones) |

---

### Paso 2 — Puntuación de especialidades (KeywordMatcher)

**Archivo:** `app/Services/KeywordMatcherService.php`  
**Datos:** `resources/data/especialidades_keywords.json`

Con los términos canónicos ya extraídos, `KeywordMatcherService` calcula un **score numérico** para cada una de las 12 especialidades del sistema.

**Estructura del JSON por especialidad:**

| Tipo de término | Función |
|---|---|
| `keywords` | Términos positivos con peso (ej. `"palpitaciones"` → peso 8) |
| `red_flags` | Señales de alarma con peso alto (ej. `"dolor toracico"` → peso 15 en cardiología) |
| `negative_keywords` | Términos que restan puntos — evitan falsos positivos entre especialidades similares |
| `synonyms` | Mapa sinónimo → canónico, para normalizar antes del scoring |

**Algoritmo de puntuación:**

$$\text{Score}(E) = \sum_{t \in \text{keywords}} w_t \cdot \mathbb{1}[t \in T] + \sum_{r \in \text{red\_flags}} w_r \cdot \mathbb{1}[r \in T] - \sum_{n \in \text{negativos}} |w_n| \cdot \mathbb{1}[n \in T]$$

Donde $T$ es el conjunto de términos normalizados detectados por GPT.

**Comportamiento del scoring:**
- Una especialidad solo entra al ranking si su score es **> 0**.
- Los `red_flags` activados elevan significativamente el score y escalan el `nivel_urgencia` a `critico` en `RecomendadorService`.
- Los `negative_keywords` penalizan especialidades que comparten síntomas generales con otras más específicas (ej. "fatiga" aparece en varias — los negativos reducen el ruido).
- Se devuelven las **top 3** por puntuación descendente.

**Las 12 especialidades del sistema:**

| Clave JSON | Especialidad en BD |
|---|---|
| `medicina_general` | Medicina General |
| `pediatria` | Pediatría |
| `cardiologia` | Cardiología |
| `neumologia` | Neumología |
| `gastroenterologia` | Gastroenterología |
| `neurologia` | Neurología |
| `dermatologia` | Dermatología |
| `urologia` | Urología |
| `ginecologia` | Ginecología |
| `ortopedia` | Traumatología |
| `psiquiatria` | Psiquiatría |
| `otorrinolaringologia` | Otorrinolaringología |

---

### Paso 3 — Ajuste demográfico: boost pediátrico

**Archivo:** `app/Services/RecomendadorService.php` → `applyPediatricBoost()`

Cuando el paciente tiene **menos de 14 años**, los síntomas generales (fiebre, tos, vómito, diarrea) producen scores similares entre `medicina_general` y `pediatria`. Para corregir esto, se aplica un ajuste de pesos posterior al scoring:

| Ajuste | Valor |
|---|---|
| Boost a `pediatria` | +25 puntos |
| Penalización a `medicina_general` | −15 puntos |
| Si `pediatria` no puntuó en absoluto | Se inyecta con score base de 25 |

Después del ajuste se reordena el top 3. El efecto práctico es que **Pediatría encabeza el resultado** para cualquier síntoma general en menores, a menos que un síntoma muy específico de otra especialidad (ej. cardiológico) tenga un score suficientemente alto para superarla.

---

### Paso 4 — Búsqueda y ordenamiento geográfico (Haversine)

**Archivo:** `app/Services/RecomendadorService.php` → `buildEspecialidadesResult()` y `haversineKm()`

Para cada una de las top 3 especialidades resultado:

1. Se consultan hasta **30 candidatos disponibles** en MySQL, ordenados inicialmente por `calificacion_promedio` descendente.
2. Si el usuario compartió su GPS, se calcula la distancia a cada candidato con la **fórmula de Haversine**:

$$d = 2R \arcsin\!\left(\sqrt{\sin^2\!\frac{\Delta\phi}{2} + \cos\phi_1\cos\phi_2\sin^2\!\frac{\Delta\lambda}{2}}\right)$$

Donde $R = 6371\,\text{km}$, $\phi$ = latitud, $\lambda$ = longitud.

3. Los 30 candidatos se reordenan por distancia ascendente.
4. Se devuelven los **3 más cercanos** con todos sus datos.
5. Si no hay GPS disponible, se devuelven los 3 mejor calificados sin distancia.

---

## Datos de entrada del sistema

El endpoint `POST /api/analizar` acepta:

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `sintomas` | string | Sí | Descripción libre de los síntomas |
| `edad` | integer | No | Edad en años — activa boost pediátrico si < 14 |
| `sexo` | string | No | `masculino` / `femenino` — contexto para GPT |
| `enfermedades_previas` | string | No | Antecedentes médicos del paciente |
| `tiempo_evolucion` | string | No | Cuánto llevan los síntomas — contexto para GPT |
| `latitud` | float | No | Coordenada GPS del usuario |
| `longitud` | float | No | Coordenada GPS del usuario |

---

## Datos por profesional

Cada profesional en la respuesta incluye:

| Campo | Fuente | Notas |
|---|---|---|
| `nombre` | `users.name` | Nombre completo |
| `especialidad` | `especialidades.nombre` | Ej: "Cardiología" |
| `area_medica` | `especialidades.area_medica` | Agrupación de la especialidad |
| `calificacion_promedio` | `medicos` | Decimal 0–5 |
| `total_consultas` | `medicos` | Entero |
| `bio` | `medicos` | Texto libre, nullable |
| `foto_url` | `medicos` | URL de imagen, nullable |
| `disponible` | `medicos` | Boolean |
| `latitud` / `longitud` | migración geo | Coordenadas sintéticas (bounding box Cali) |
| `direccion` | migración geo | Dirección textual, nullable |
| `distancia_km` | calculado (Haversine) | Solo presente si el usuario compartió GPS |

---

## Base de datos de médicos

**Origen de los datos:** [REPS — Registro Especial de Prestadores de Salud](https://www.datos.gov.co/) (datos abiertos del Ministerio de Salud de Colombia).

- Dataset original: **76.395 filas**
- Filtro aplicado: `ClasePrestadorDesc = 'Profesional Independiente'` y `MunicipioSedeDesc = 'CALI'`
- Registros importados: **3.526 médicos** en Cali
- Las coordenadas geográficas son **sintéticas** dentro del bounding box de Cali — el dataset REPS no incluye coordenadas GPS reales.

**Seeder:** `database/seeders/MedicoSeeder.php`  
**Migración geoespacial:** `2026_04_20_000001_add_geo_columns_to_medicos_table.php`

---

## Flujo completo con ejemplo

**Entrada:**
> Paciente, 45 años, describe: *"Llevo 3 días con dolor de pecho fuerte, siento que el corazón se me acelera y tengo dificultad para respirar"*

**Paso 1 — GPT extrae y normaliza:**
```json
{
  "terminos_identificados": ["dolor toracico", "palpitaciones", "disnea"],
  "nivel_urgencia": "critico",
  "resumen_medico": "Paciente con dolor torácico, taquicardia y dificultad respiratoria."
}
```

**Paso 2 — KeywordMatcher puntúa las 12 especialidades:**

| Especialidad | Keywords activas | Red flags | Score |
|---|---|---|---|
| Cardiología | palpitaciones (8) + disnea (8) | dolor toracico (15) | **31 pts** ← 1ª |
| Neumología | disnea (10) | — | 10 pts |
| Medicina General | — | — | 0 pts |

**Paso 3 — Sin boost pediátrico** (paciente tiene 45 años).

**Paso 4 — Haversine ordena médicos de Cardiología (GPS disponible):**

| Profesional | Distancia |
|---|---|
| Dra. Torres | 0.8 km ← más cercana |
| Dr. García | 1.4 km |
| Dr. López | 2.1 km |

**Resultado mostrado:** alerta de urgencia crítica, resumen clínico, acordeón con las 3 opciones y mapa con los cardiólogos marcados.

---

**Ejemplo con paciente pediátrico:**

> Paciente, **6 años**, describe: *"Tiene fiebre desde ayer, tos y no quiere comer"*

Tras el scoring, `medicina_general` y `pediatria` tienen scores similares (~15 pts). El boost pediátrico suma +25 a Pediatría y resta −15 a Medicina General → **Pediatría encabeza el resultado**.

---

## Evolución: del prototipo Python al sistema en producción

| Aspecto | Prototipo (`motor_ia.py`) | Sistema final (Laravel + PHP) |
|---|---|---|
| Detección de especialidad | Diccionario simple (~15 palabras) | GPT-4o-mini + JSON ponderado (keywords / red_flags / negative_keywords) |
| Contexto clínico | Sin contexto | Edad, sexo, antecedentes y tiempo de evolución enviados al modelo |
| Ajuste demográfico | Ninguno | Boost pediátrico automático para menores de 14 años |
| Entrada del usuario | Variable hardcodeada | Texto libre en interfaz web |
| Geolocalización | Coordenadas fijas | GPS real del navegador (API Web Geolocation) |
| Datos | CSV leído con pandas | MySQL, 3.526 médicos (dataset REPS, Cali) |
| Distancia | `geopy.geodesic` | Haversine implementado en PHP |
| Interfaz | Terminal / Jupyter Notebook | Aplicación web + mapa interactivo (Leaflet.js) |
| Despliegue | Local | VPS en producción (`recomienda.koqoi.com`) |

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| IA / PLN | OpenAI GPT-4o-mini |
| Base de datos | MySQL |
| Frontend | Blade + Tailwind CSS + Leaflet.js 1.9 |
| Tiles del mapa | CartoDB Voyager (sin API key) |
| Datos médicos | REPS — datos.gov.co |
| Hosting | VPS Ubuntu, Nginx |
