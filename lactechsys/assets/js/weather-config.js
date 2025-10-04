/**
 * CONFIGURAÇÃO DO MODAL DE CLIMA
 * 
 * API GRATUITA SEM CHAVE - WeatherAPI.com
 * 
 * PLANO GRATUITO INCLUI:
 * - 1 milhão de chamadas/mês
 * - Dados atuais e previsão 3 dias
 * - Sem necessidade de chave
 * - Múltiplas cidades
 */

// CONFIGURAÇÃO DA API
const WEATHER_CONFIG = {
    // API gratuita sem chave necessária
    apiKey: null, 
    
    // URLs da API gratuita
    baseUrl: 'https://api.weatherapi.com/v1',
    
    // Configurações padrão
    defaultLocation: {
        lat: -23.5505,  // São Paulo (fallback)
        lon: -46.6333,
        city: 'São Paulo'
    },
    
    // Unidades (metric = Celsius)
    units: 'metric',
    
    // Idioma (pt = Português)
    lang: 'pt'
};

// EXEMPLO DE CHAVE (NÃO FUNCIONA - APENAS EXEMPLO):
// const WEATHER_CONFIG = {
//     apiKey: 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
//     baseUrl: 'https://api.openweathermap.org/data/2.5',
//     defaultLocation: { lat: -23.5505, lon: -46.6333 },
//     units: 'metric',
//     lang: 'pt'
// };

// Verificar se a chave foi configurada
if (WEATHER_CONFIG.apiKey === 'SUA_CHAVE_AQUI') {
    console.warn('⚠️ ATENÇÃO: Configure sua chave da API OpenWeatherMap em weather-config.js');
    console.warn('📖 Instruções: https://openweathermap.org/api');
}

// Exportar configuração
window.WEATHER_CONFIG = WEATHER_CONFIG;
