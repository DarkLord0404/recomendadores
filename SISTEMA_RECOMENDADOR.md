# Sistema Recomendador Médico — Documentación

## ¿Qué es?

Es un sistema recomendador **basado en contenido** (*content-based filtering*) que analiza la descripción de síntomas de un paciente en lenguaje natural y recomienda las especialidades médicas más adecuadas junto con los 3 médicos más cercanos a su ubicación.

---

## Arquitectura general

```
Paciente escribe síntomas
        ↓
[Navegador] Solicita ubicación GPS (API Geolocation)
        ↓
[API REST - Laravel] POST /api/analizar
        ↓
┌─────────────────────────────────────────────┐
│              RecomendadorService             │
│                                             │
│  1. GPT-4o-mini → extrae términos médicos   │
│  2. KeywordMatcherService → puntúa          │
│     especialidades                          │
│  3. MySQL → busca médicos disponibles       │
│  4. Haversine → ordena por distancia        │
└─────────────────────────────────────────────┘
        ↓
[Respuesta JSON] Top 3 especialidades + médicos
        ↓
[Frontend Blade + Leaflet.js] Acordeón + Mapa
```

---

## Componentes del sistema

### 1. Interfaz de usuario (`welcome.blade.php`)

- El paciente ingresa: **síntomas** (texto libre), sexo, edad y antecedentes médicos opcionales.
- Al hacer clic en "Buscar mi médico ideal":
  1. El navegador solicita la **geolocalización GPS** del usuario.
  2. Se envía todo al backend vía `POST /api/analizar`.
- Los resultados se presentan en **dos columnas**:
  - **Izquierda:** acordeón con las especialidades recomendadas (la primera expandida, las demás colapsables).
  - **Derecha:** mapa Leaflet con los médicos de la especialidad activa marcados.

---

### 2. Extracción de términos médicos (GPT-4o-mini)

**Archivo:** `app/Services/RecomendadorService.php` → método `extraerTerminosViaGPT()`

El texto del paciente se envía a **GPT-4o-mini** con un prompt estructurado que incluye:
- La lista completa de términos médicos válidos del sistema (extraída del JSON de keywords).
- Instrucciones para que el modelo **solo use términos de esa lista** (sin inventar otros).

El modelo responde con JSON:
```json
{
  "terminos_identificados": ["dolor de pecho", "palpitaciones"],
  "nivel_urgencia": "urgente",
  "resumen_medico": "Paciente refiere dolor torácico con palpitaciones."
}
```

- `nivel_urgencia` puede ser `normal`, `urgente` o `critico`.
- Los términos devueltos son validados contra la lista antes de usarse (seguridad ante alucinaciones del modelo).

---

### 3. Puntuación de especialidades (KeywordMatcher)

**Archivo:** `app/Services/KeywordMatcherService.php`  
**Datos:** `resources/data/especialidades_keywords.json`

Cada especialidad médica tiene definidos en el JSON:

| Tipo | Función |
|---|---|
| `keywords` | Términos positivos con peso (ej. "palpitaciones" → peso 8) |
| `red_flags` | Señales de alerta con peso alto (ej. "dolor de pecho" → peso 15) |
| `negative_keywords` | Términos que restan puntos (evitan falsos positivos) |
| `synonyms` | Mapa de sinónimos → término canónico |

**Algoritmo de puntuación:**

$$\text{Score}(E) = \sum_{t \in \text{keywords}} w_t \cdot \mathbb{1}[t \in \text{términos detectados}] + \sum_{r \in \text{red\_flags}} w_r \cdot \mathbb{1}[r \in \text{términos detectados}] - \sum_{n \in \text{negativos}} |w_n| \cdot \mathbb{1}[n \in \text{términos detectados}]$$

- Se calculan los scores de las **11 especialidades** disponibles.
- Se devuelven las **top 3** ordenadas por puntuación descendente.
- Si alguna especialidad activó `red_flags`, el nivel de urgencia escala a **crítico**.

**Especialidades cubiertas:**

| Clave JSON | Especialidad |
|---|---|
| `medicina_general` | Medicina General |
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

### 4. Búsqueda y ordenamiento de médicos (Haversine)

**Archivo:** `app/Services/RecomendadorService.php` → `buildEspecialidadesResult()` y `haversineKm()`

Para cada una de las top 3 especialidades:

1. Se consultan hasta **30 candidatos** disponibles en la base de datos, ordenados por calificación promedio.
2. Si se tiene la ubicación del usuario, se calcula la distancia a cada médico con la **fórmula de Haversine**:

$$d = 2R \arcsin\!\left(\sqrt{\sin^2\!\frac{\Delta\phi}{2} + \cos\phi_1\cos\phi_2\sin^2\!\frac{\Delta\lambda}{2}}\right)$$

Donde $R = 6371$ km, $\phi$ = latitud y $\lambda$ = longitud.

3. Los candidatos se reordenan por distancia ascendente.
4. Se devuelven los **3 más cercanos**.

---

### 5. Base de datos de médicos

**Origen de los datos:** [REPS — Registro Especial de Prestadores de Salud](https://www.datos.gov.co/) (datos abiertos del Ministerio de Salud de Colombia).

- Dataset original: **76.395 filas**
- Filtro aplicado: `ClasePrestadorDesc = 'Profesional Independiente'` y `MunicipioSedeDesc = 'CALI'`
- Registros importados: **3.526 médicos** en Cali
- Las coordenadas geográficas son sintéticas dentro del bounding box de Cali (el dataset REPS no incluye coordenadas GPS).

**Seeder:** `database/seeders/MedicoSeeder.php`  
**Migración geoespacial:** `2026_04_20_000001_add_geo_columns_to_medicos_table.php`

---

## Flujo completo con ejemplo

**Entrada del paciente:**
> *"Llevo 3 días con dolor de pecho fuerte, siento que el corazón se me acelera y tengo dificultad para respirar"*

**Paso 1 — GPT extrae términos:**
```json
{
  "terminos_identificados": ["dolor de pecho", "palpitaciones", "disnea"],
  "nivel_urgencia": "critico",
  "resumen_medico": "Paciente con dolor torácico, taquicardia y dificultad respiratoria."
}
```

**Paso 2 — KeywordMatcher puntúa:**
| Especialidad | Puntuación |
|---|---|
| Cardiología | 38 pts ← ganadora |
| Neumología | 12 pts |
| Medicina General | 5 pts |

**Paso 3 — Haversine ordena médicos de Cardiología:**
| Médico | Distancia |
|---|---|
| Dr. Pérez | ⭐ 0.8 km |
| Dr. García | 1.4 km |
| Dr. López | 2.1 km |

**Resultado:** Se muestra alerta roja de urgencia, resumen IA, acordeón con las 3 especialidades y mapa con los 3 cardiólogos más cercanos marcados.

---

## Evolución: del prototipo Python al sistema en producción

| Aspecto | Prototipo (`motor_ia.py`) | Sistema final (Laravel + PHP) |
|---|---|---|
| Detección de especialidad | Diccionario simple (~15 palabras) | GPT-4o-mini + JSON ponderado con 3 tipos de términos |
| Entrada del usuario | Variable hardcodeada en el código | Texto libre en interfaz web |
| Geolocalización | Coordenadas fijas | GPS real del navegador (API Web Geolocation) |
| Datos | CSV leído con pandas | MySQL, 3.526 médicos (dataset REPS) |
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
