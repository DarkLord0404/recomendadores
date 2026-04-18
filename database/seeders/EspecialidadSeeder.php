<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            ['nombre' => 'Medicina General',       'area_medica' => 'Atención Primaria',        'descripcion' => 'Atención de salud integral para todas las edades'],
            ['nombre' => 'Cardiología',             'area_medica' => 'Medicina Interna',         'descripcion' => 'Enfermedades del corazón y sistema circulatorio'],
            ['nombre' => 'Neurología',              'area_medica' => 'Medicina Interna',         'descripcion' => 'Enfermedades del sistema nervioso central y periférico'],
            ['nombre' => 'Dermatología',            'area_medica' => 'Especialidades Clínicas',  'descripcion' => 'Enfermedades de la piel, cabello y uñas'],
            ['nombre' => 'Gastroenterología',       'area_medica' => 'Medicina Interna',         'descripcion' => 'Enfermedades del sistema digestivo'],
            ['nombre' => 'Endocrinología',          'area_medica' => 'Medicina Interna',         'descripcion' => 'Trastornos hormonales y metabólicos (diabetes, tiroides)'],
            ['nombre' => 'Reumatología',            'area_medica' => 'Medicina Interna',         'descripcion' => 'Enfermedades autoinmunes y del aparato locomotor'],
            ['nombre' => 'Neumología',              'area_medica' => 'Medicina Interna',         'descripcion' => 'Enfermedades del sistema respiratorio'],
            ['nombre' => 'Traumatología',           'area_medica' => 'Cirugía',                  'descripcion' => 'Lesiones y enfermedades del aparato locomotor'],
            ['nombre' => 'Ginecología',             'area_medica' => 'Especialidades Clínicas',  'descripcion' => 'Salud reproductiva femenina'],
            ['nombre' => 'Pediatría',               'area_medica' => 'Atención Primaria',        'descripcion' => 'Atención médica de niños y adolescentes'],
            ['nombre' => 'Psiquiatría',             'area_medica' => 'Salud Mental',             'descripcion' => 'Enfermedades mentales y trastornos emocionales'],
            ['nombre' => 'Oftalmología',            'area_medica' => 'Especialidades Clínicas',  'descripcion' => 'Enfermedades de los ojos'],
            ['nombre' => 'Otorrinolaringología',    'area_medica' => 'Especialidades Clínicas',  'descripcion' => 'Enfermedades de oídos, nariz y garganta'],
            ['nombre' => 'Urología',                'area_medica' => 'Cirugía',                  'descripcion' => 'Enfermedades del tracto urinario y sistema reproductivo masculino'],
            ['nombre' => 'Oncología',               'area_medica' => 'Medicina Interna',         'descripcion' => 'Prevención, diagnóstico y tratamiento del cáncer'],
            ['nombre' => 'Nefrología',              'area_medica' => 'Medicina Interna',         'descripcion' => 'Enfermedades de los riñones'],
            ['nombre' => 'Infectología',            'area_medica' => 'Medicina Interna',         'descripcion' => 'Enfermedades infecciosas y parasitarias'],
        ];

        foreach ($especialidades as $especialidad) {
            Especialidad::updateOrCreate(
                ['nombre' => $especialidad['nombre']],
                $especialidad
            );
        }
    }
}
