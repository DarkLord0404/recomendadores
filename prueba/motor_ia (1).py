# motor_ia.py
import pandas as pd
from geopy.distance import geodesic

dict_sintomas = {
    'corazon': 'Cardiología', 'pecho': 'Cardiología', 'palpitaciones': 'Cardiología', 
    'piel': 'Dermatología', 'mancha': 'Dermatología', 'acne': 'Dermatología',
    'niño': 'Pediatría', 'bebe': 'Pediatría', 'infantil': 'Pediatría',
    'diente': 'Odontología', 'muela': 'Odontología', 'caries': 'Odontología',
    'mente': 'Psicología', 'ansiedad': 'Psicología', 'estres': 'Psicología',
    'mujer': 'Ginecología', 'embarazo': 'Ginecología', 'citologia': 'Ginecología',
    'hueso': 'Ortopedia', 'fractura': 'Ortopedia', 'espalda': 'Ortopedia',
    'ojo': 'Oftalmología', 'vision': 'Oftalmología', 'lente': 'Oftalmología',
    'migraña': 'Neurología', 'cerebro': 'Neurología',
    'rehabilitacion': 'Fisioterapia', 'musculo': 'Fisioterapia',
    'dieta': 'Nutrición', 'peso': 'Nutrición'
}

def detectar_especialidad(texto_usuario):
    texto_usuario = texto_usuario.lower()
    puntos_especialidad = {}
    for termino, especialidad in dict_sintomas.items():
        if termino in texto_usuario:
            puntos_especialidad[especialidad] = puntos_especialidad.get(especialidad, 0) + 1
    
    if puntos_especialidad:
        return max(puntos_especialidad, key=puntos_especialidad.get)
    return "Médico General / Otra"

# motor_ia.py (Sección de la función recomendar_medico_completo)

# motor_ia.py

def recomendar_medico_completo(sintoma_usuario, lat_usuario, lon_usuario, dataframe, top_n=5):
    # 1. Detectar especialidad
    esp_buscada = detectar_especialidad(sintoma_usuario)
    
    # 2. Filtrar candidatos
    candidatos = dataframe[dataframe['Especialidad_Lab'] == esp_buscada].copy()
    
    if candidatos.empty:
        candidatos = dataframe[dataframe['Especialidad_Lab'] == "Médico General / Otra"].copy()

    # 3. CÁLCULO DE DISTANCIA (Versión robusta sin .apply)
    # Creamos una lista simple de distancias para evitar el ValueError de Pandas
    distancias = []
    punto_usuario = (lat_usuario, lon_usuario)
    
    for idx, row in candidatos.iterrows():
        punto_medico = (row['latitud'], row['longitud'])
        dist = geodesic(punto_usuario, punto_medico).km
        distancias.append(float(dist))
    
    # Asignamos la lista directamente a la columna
    candidatos['distancia_km'] = distancias
    
    # 4. Retornar resultados ordenados
    return candidatos.sort_values(by='distancia_km').head(top_n), esp_buscada