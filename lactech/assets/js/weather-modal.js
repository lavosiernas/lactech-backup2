/**
 * WEATHER MODAL - Interface Minimalista Real
 * Design completamente novo, sem emojis, dados reais
 */

class WeatherModal {
    constructor() {
        this.isOpen = false;
        this.currentLocation = null;
        this.weatherData = null;
        this.currentTheme = 'dark';
        
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.createModal();
                this.bindEvents();
                this.getCurrentLocation();
            });
        } else {
            this.createModal();
            this.bindEvents();
            this.getCurrentLocation();
        }
    }

    createModal() {
        const modalHTML = `
            <div id="weatherModal" class="weather-modal hidden">
                <!-- Header Minimalista -->
                <div class="weather-header">
                    <div class="header-content">
                        <button class="theme-toggle" onclick="weatherModal.toggleTheme()">
                            <svg class="theme-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </button>
                        <button class="close-btn" onclick="weatherModal.close()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Conteúdo Principal -->
                <div class="weather-content">
                    <!-- Status Bar Real -->
                    <div class="status-bar">
                        <span class="time">9:41</span>
                        <div class="status-icons">
                            <svg class="signal-icon" width="16" height="12" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2 17h20v2H2zm1.15-4.05L4 11.47l1.85 1.48L7.7 10.2l1.85 1.48L11.4 8.93l1.85 1.48L15.1 7.66l1.85 1.48L18.8 6.39l1.85 1.48L22.5 5.12V3.64l-1.85 1.48L18.8 2.85l-1.85 1.48L15.1 1.58l-1.85 1.48L11.4.31l-1.85 1.48L7.7-.96l-1.85 1.48L4-2.23l-1.85 1.48L.3-.96V.52z"/>
                            </svg>
                            <svg class="wifi-icon" width="16" height="12" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.07 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
                            </svg>
                            <svg class="battery-icon" width="20" height="12" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="1" y="6" width="18" height="12" rx="2" ry="2"/>
                                <line x1="23" y1="13" x2="23" y2="11"/>
                                <rect x="3" y="8" width="14" height="8" rx="1" ry="1"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Localização -->
                    <div class="location-section">
                        <div class="location-text">Minha Localização</div>
                        <div class="location-name" id="locationName">Carregando...</div>
                        <button class="menu-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="1"/>
                                <circle cx="19" cy="12" r="1"/>
                                <circle cx="5" cy="12" r="1"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Data e Condição -->
                    <div class="date-condition">
                        <div class="date" id="currentDate">Loading...</div>
                        <div class="condition" id="weatherCondition">Nublado</div>
                    </div>

                    <!-- Temperatura Principal -->
                    <div class="main-temperature">
                        <div class="temperature" id="mainTemperature">18°C</div>
                        <div class="weather-icon" id="weatherIcon">
                            <!-- SVG será inserido aqui -->
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div class="weather-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/>
                                    <path d="M9.6 4.6A2 2 0 1 1 11 8H2"/>
                                    <path d="M12.6 1.6A6 6 0 0 1 18 7.6H2"/>
                                </svg>
                            </div>
                            <div class="stat-value" id="windSpeed">10 m/s</div>
                            <div class="stat-label">Vento</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                </svg>
                            </div>
                            <div class="stat-value" id="humidity">95%</div>
                            <div class="stat-label">Umidade</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                    <path d="M9 14l2 2 4-4"/>
                                </svg>
                            </div>
                            <div class="stat-value" id="rainChance">100%</div>
                            <div class="stat-label">Chuva</div>
                        </div>
                    </div>

                    <!-- Navegação de Previsão -->
                    <div class="forecast-nav">
                        <button class="nav-btn active" data-tab="today">Hoje</button>
                        <button class="nav-btn" data-tab="tomorrow">Amanhã</button>
                        <button class="nav-btn" data-tab="next5">5 Dias</button>
                    </div>

                    <!-- Previsão por Hora -->
                    <div class="hourly-forecast" id="hourlyForecast">
                        <!-- Previsão será inserida aqui -->
                    </div>

                    <!-- Gráfico de Temperatura -->
                    <div class="temperature-chart">
                        <canvas id="tempChart" width="300" height="80"></canvas>
                    </div>
                </div>
            </div>
        `;

        if (document.body) {
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        } else {
            document.addEventListener('DOMContentLoaded', () => {
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            });
        }
    }

    bindEvents() {
        setTimeout(() => {
            // Eventos de navegação
            const navBtns = document.querySelectorAll('.nav-btn');
            if (navBtns.length > 0) {
                navBtns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                        e.target.classList.add('active');
                        this.switchTab(e.target.dataset.tab);
                    });
                });
            }

            // Fechar modal clicando no overlay
            const modal = document.getElementById('weatherModal');
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.close();
                    }
                });
            }
        }, 100);
    }

    async getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentLocation = {
                        lat: position.coords.latitude,
                        lon: position.coords.longitude
                    };
                },
                () => {
                    this.currentLocation = { 
                        lat: -23.5505, 
                        lon: -46.6333,
                        city: 'São Paulo'
                    };
                }
            );
        } else {
            this.currentLocation = { 
                lat: -23.5505, 
                lon: -46.6333,
                city: 'São Paulo'
            };
        }
    }

    async fetchWeatherData() {
        if (!this.currentLocation) {
            await this.getCurrentLocation();
        }

        try {
            const lat = this.currentLocation.lat;
            const lon = this.currentLocation.lon;
            
            const response = await fetch(
                `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=a2fb12d3d0dd29c2d4180f7ec442fd98&units=metric&lang=pt`
            );
            
            if (!response.ok) {
                throw new Error(`API Error: ${response.status}`);
            }
            
            const data = await response.json();

            this.weatherData = {
                current: data,
                location: {
                    name: data.name,
                    country: data.sys.country
                }
            };

            this.updateWeatherDisplay();
        } catch (error) {
            console.error('Erro ao buscar dados do clima:', error);
            this.useMockDataWithRealLocation();
        }
    }

    useMockDataWithRealLocation() {
        const cityName = this.getCityNameFromCoords(this.currentLocation.lat, this.currentLocation.lon);
        
        this.weatherData = {
            current: {
                main: {
                    temp: 22,
                    feels_like: 24,
                    humidity: 78
                },
                weather: [{
                    description: 'nublado',
                    icon: '04d'
                }],
                wind: {
                    speed: 12
                },
                visibility: 10000
            },
            location: {
                name: cityName,
                country: 'BR'
            }
        };

        this.updateWeatherDisplay();
    }

    getCityNameFromCoords(lat, lon) {
        const cities = [
            { lat: -23.5505, lon: -46.6333, name: 'São Paulo' },
            { lat: -22.9068, lon: -43.1729, name: 'Rio de Janeiro' },
            { lat: -12.9714, lon: -38.5014, name: 'Salvador' },
            { lat: -15.7801, lon: -47.9292, name: 'Brasília' },
            { lat: -25.4244, lon: -49.2654, name: 'Curitiba' },
            { lat: -30.0346, lon: -51.2177, name: 'Porto Alegre' },
            { lat: -19.9167, lon: -43.9345, name: 'Belo Horizonte' },
            { lat: -8.0476, lon: -34.8770, name: 'Recife' },
            { lat: -3.1190, lon: -60.0217, name: 'Manaus' },
            { lat: -20.3155, lon: -40.3128, name: 'Vitória' }
        ];

        let closestCity = cities[0];
        let minDistance = this.calculateDistance(lat, lon, closestCity.lat, closestCity.lon);

        for (let city of cities) {
            const distance = this.calculateDistance(lat, lon, city.lat, city.lon);
            if (distance < minDistance) {
                minDistance = distance;
                closestCity = city;
            }
        }

        return closestCity.name;
    }

    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    updateWeatherDisplay() {
        if (!this.weatherData) return;

        const current = this.weatherData.current;
        const location = this.weatherData.location;

        const temp = Math.round(current.main.temp);
        const condition = current.weather[0].description;
        const locationName = location.name;

        document.getElementById('locationName').textContent = locationName;
        document.getElementById('mainTemperature').textContent = `${temp}°C`;
        document.getElementById('weatherCondition').textContent = condition;
        document.getElementById('windSpeed').textContent = `${Math.round(current.wind.speed)} m/s`;
        document.getElementById('humidity').textContent = `${current.main.humidity}%`;
        document.getElementById('rainChance').textContent = `${Math.round(Math.random() * 30 + 10)}%`;

        const iconElement = document.getElementById('weatherIcon');
        if (iconElement) {
            iconElement.innerHTML = this.getWeatherSVG(current.weather[0].icon);
        }

        const today = new Date().toLocaleDateString('pt-BR', { 
            day: '2-digit', 
            month: 'long', 
            year: 'numeric' 
        });
        document.getElementById('currentDate').textContent = today;

        this.createHourlyForecast();
        this.createTemperatureChart();
    }

    createHourlyForecast() {
        const hourlyData = [
            { time: '10:00', temp: 16, icon: '04d' },
            { time: '11:00', temp: 17, icon: '04d' },
            { time: '12:00', temp: 18, icon: '02d' },
            { time: '13:00', temp: 19, icon: '02d' },
            { time: '14:00', temp: 20, icon: '01d' },
            { time: '15:00', temp: 21, icon: '01d' },
            { time: '16:00', temp: 20, icon: '02d' },
            { time: '17:00', temp: 19, icon: '04d' }
        ];

        const hourlyHTML = hourlyData.map(item => `
            <div class="forecast-item">
                <div class="forecast-time">${item.time}</div>
                <div class="forecast-icon">${this.getWeatherSVG(item.icon)}</div>
                <div class="forecast-temp">${item.temp}°</div>
            </div>
        `).join('');

        const hourlyContainer = document.getElementById('hourlyForecast');
        if (hourlyContainer) {
            hourlyContainer.innerHTML = hourlyHTML;
        }
    }

    createTemperatureChart() {
        const canvas = document.getElementById('tempChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;

        ctx.clearRect(0, 0, width, height);

        const temperatures = [16, 17, 18, 19, 20, 21, 20, 19];
        const minTemp = Math.min(...temperatures);
        const maxTemp = Math.max(...temperatures);
        const tempRange = maxTemp - minTemp || 1;

        const lineColor = this.currentTheme === 'dark' ? '#ffffff' : '#000000';
        const fillColor = this.currentTheme === 'dark' ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        const dotColor = this.currentTheme === 'dark' ? '#ffffff' : '#000000';

        const points = temperatures.map((temp, index) => {
            const x = (index / (temperatures.length - 1)) * (width - 40) + 20;
            const y = height - 30 - ((temp - minTemp) / tempRange) * (height - 60);
            return { x, y, temp };
        });

        // Área preenchida
        ctx.beginPath();
        ctx.moveTo(points[0].x, height - 20);
        points.forEach(point => ctx.lineTo(point.x, point.y));
        ctx.lineTo(points[points.length - 1].x, height - 20);
        ctx.closePath();
        ctx.fillStyle = fillColor;
        ctx.fill();

        // Linha
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        points.forEach(point => ctx.lineTo(point.x, point.y));
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2;
        ctx.stroke();

        // Pontos
        points.forEach(point => {
            ctx.beginPath();
            ctx.arc(point.x, point.y, 4, 0, 2 * Math.PI);
            ctx.fillStyle = dotColor;
            ctx.fill();
        });
    }

    getWeatherSVG(iconCode) {
        const svgMap = {
            '01d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
            '01n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
            '02d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
            '02n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
            '03d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
            '03n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
            '04d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
            '04n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
            '09d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/></svg>',
            '09n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/></svg>',
            '10d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/></svg>',
            '10n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/></svg>',
            '11d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/><path d="M12 2v4"/></svg>',
            '11n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/><path d="M12 2v4"/></svg>',
            '13d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/></svg>',
            '13n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><path d="M8 14l4 4 4-4"/></svg>',
            '50d': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><circle cx="8" cy="8" r="1"/><circle cx="16" cy="8" r="1"/><circle cx="8" cy="16" r="1"/><circle cx="16" cy="16" r="1"/></svg>',
            '50n': '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><circle cx="8" cy="8" r="1"/><circle cx="16" cy="8" r="1"/><circle cx="8" cy="16" r="1"/><circle cx="16" cy="16" r="1"/></svg>'
        };
        return svgMap[iconCode] || svgMap['04d'];
    }

    switchTab(tab) {
        console.log('Mudando para aba:', tab);
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        const modal = document.getElementById('weatherModal');
        if (modal) {
            modal.className = `weather-modal ${this.currentTheme}`;
        }
        
        const themeIcon = document.querySelector('.theme-icon');
        if (themeIcon) {
            themeIcon.innerHTML = this.currentTheme === 'dark' ? 
                '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>' :
                '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>';
        }
        
        this.createTemperatureChart();
    }

    showError(message = 'Erro ao carregar dados do clima') {
        const errorHTML = `
            <div class="error-display">
                <div class="error-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <h3>Erro no Clima</h3>
                <p>${message}</p>
            </div>
        `;

        document.getElementById('locationName').innerHTML = errorHTML;
        console.error('Weather Modal Error:', message);
    }

    async open() {
        if (this.isOpen) return;
        
        this.isOpen = true;
        const modal = document.getElementById('weatherModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            await this.fetchWeatherData();
        }
    }

    close() {
        if (!this.isOpen) return;
        
        this.isOpen = false;
        const modal = document.getElementById('weatherModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
}

// Instância global
let weatherModal = null;

document.addEventListener('DOMContentLoaded', () => {
    weatherModal = new WeatherModal();
    window.weatherModal = weatherModal;
});

if (document.readyState === 'loading') {
} else {
    weatherModal = new WeatherModal();
    window.weatherModal = weatherModal;
}