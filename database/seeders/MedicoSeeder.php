<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MedicoSeeder extends Seeder
{
    /**
     * Lee prestadores.csv (REPS), filtra Profesionales Independientes en Cali
     * y los importa como usuarios + médicos con coordenadas sintéticas dentro
     * del área urbana de Cali.
     */
    public function run(): void
    {
        $csvPath = base_path('prueba/prestadores.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("No se encontró prueba/prestadores.csv — omitiendo MedicoSeeder.");
            return;
        }

        // ── 1. Obtener IDs de especialidades existentes ─────────────────────
        $especialidadIds = Especialidad::pluck('id')->values()->toArray();

        if (empty($especialidadIds)) {
            $this->command->warn("No hay especialidades en BD. Ejecuta EspecialidadSeeder primero.");
            return;
        }

        // ── 2. Leer y filtrar CSV ────────────────────────────────────────────
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);

        // Normalizar headers (eliminar BOM UTF-8 si existe)
        if ($headers !== false) {
            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
        }

        $doctores = [];
        $vistosCodigosede = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, $row);

            // Filtrar: Profesional Independiente en Cali
            if (
                $data['ClasePrestadorDesc'] !== 'Profesional Independiente' ||
                strtoupper(trim($data['MunicipioSedeDesc'])) !== 'CALI'
            ) {
                continue;
            }

            $codigo = trim($data['CodigoHabilitacionSede']);

            // Evitar duplicados por código sede
            if (isset($vistosCodigosede[$codigo])) {
                continue;
            }
            $vistosCodigosede[$codigo] = true;

            $doctores[] = $data;
        }

        fclose($handle);

        $total = count($doctores);
        $this->command->info("Importando {$total} profesionales independientes de Cali...");

        if ($total === 0) {
            $this->command->warn("No se encontraron registros para importar.");
            return;
        }

        // ── 3. Password fijo pre-hasheado para todos ─────────────────────────
        $password  = Hash::make('reps2026');
        $timestamp = now()->format('Y-m-d H:i:s');
        $countEsp  = count($especialidadIds);

        // ── 4. Preparar registros de usuarios en chunks ──────────────────────
        $usuariosChunks = array_chunk($doctores, 100);
        $totalInserted  = 0;

        foreach ($usuariosChunks as $chunk) {
            $usuariosRows = [];
            foreach ($chunk as $d) {
                $email = substr($d['CodigoHabilitacionSede'], 0, 40) . '@reps.local';
                $usuariosRows[] = [
                    'name'               => mb_substr(trim($d['NombrePrestador']), 0, 191),
                    'email'              => $email,
                    'email_verified_at'  => $timestamp,
                    'password'           => $password,
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp,
                ];
            }
            DB::table('users')->insertOrIgnore($usuariosRows);
        }

        // ── 5. Recuperar mapa email → user_id ───────────────────────────────
        $allEmails = array_map(
            fn ($d) => substr($d['CodigoHabilitacionSede'], 0, 40) . '@reps.local',
            $doctores
        );

        // Recuperar en chunks para evitar IN-clause muy largo
        $emailToId = [];
        foreach (array_chunk($allEmails, 500) as $emailChunk) {
            $partial = DB::table('users')
                ->whereIn('email', $emailChunk)
                ->pluck('id', 'email')
                ->toArray();
            $emailToId = array_merge($emailToId, $partial);
        }

        // ── 6. Preparar e insertar médicos en chunks ─────────────────────────
        $medicosChunks = array_chunk($doctores, 100);
        $idx = 0;

        foreach ($medicosChunks as $chunk) {
            $medicosRows = [];
            foreach ($chunk as $d) {
                $email  = substr($d['CodigoHabilitacionSede'], 0, 40) . '@reps.local';
                $userId = $emailToId[$email] ?? null;

                if (! $userId) {
                    $idx++;
                    continue;
                }

                // Coordenadas sintéticas dentro del área urbana de Cali
                // Lat: 3.3300 – 3.4800 | Lon: -76.5500 – -76.4800
                $lat = 3.3300 + ($idx * 47 % 1500) / 10000.0;
                $lon = -76.5500 + ($idx * 31 % 700) / 10000.0;

                // Asignación de especialidad determinista por índice
                $espId = $especialidadIds[$idx % $countEsp];

                $medicosRows[] = [
                    'user_id'              => $userId,
                    'especialidad_id'      => $espId,
                    'numero_colegiado'     => mb_substr(trim($d['CodigoHabilitacionSede']), 0, 50),
                    'telefono'             => mb_substr(trim($d['TelefonoSede'] ?? ''), 0, 20) ?: null,
                    'bio'                  => null,
                    'calificacion_promedio'=> round(3.5 + ($idx % 30) / 20, 2), // 3.5 – 4.95
                    'total_consultas'      => $idx % 200,
                    'disponible'           => 1,
                    'latitud'              => $lat,
                    'longitud'             => $lon,
                    'direccion'            => mb_substr(trim($d['DireccionSede'] ?? ''), 0, 255) ?: null,
                    'created_at'           => $timestamp,
                    'updated_at'           => $timestamp,
                ];

                $idx++;
                $totalInserted++;
            }

            if (! empty($medicosRows)) {
                DB::table('medicos')->insertOrIgnore($medicosRows);
            }
        }

        $this->command->info("✅ {$totalInserted} médicos importados correctamente.");
    }
}
