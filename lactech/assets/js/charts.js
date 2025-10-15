// =====================================================
// GRÁFICOS - LACTECH
// =====================================================
// Arquivo 4 de 5 - Gráficos e dashboards
// =====================================================

// Criar gráfico de produção
window.createProductionChart = function(canvasId, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (!data || data.length === 0) {
        ctx.fillStyle = '#666';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Nenhum dado disponível', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    const labels = data.map(item => window.formatDate(item.production_date));
    const volumes = data.map(item => parseFloat(item.volume_liters));
    
    // Configurações do gráfico
    const padding = 40;
    const chartWidth = canvas.width - 2 * padding;
    const chartHeight = canvas.height - 2 * padding;
    
    const maxVolume = Math.max(...volumes);
    const minVolume = Math.min(...volumes);
    const range = maxVolume - minVolume || 1;
    
    // Desenhar eixos
    ctx.strokeStyle = '#ccc';
    ctx.lineWidth = 1;
    
    // Eixo Y
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, canvas.height - padding);
    ctx.stroke();
    
    // Eixo X
    ctx.beginPath();
    ctx.moveTo(padding, canvas.height - padding);
    ctx.lineTo(canvas.width - padding, canvas.height - padding);
    ctx.stroke();
    
    // Desenhar linha de produção
    ctx.strokeStyle = '#2196F3';
    ctx.lineWidth = 3;
    ctx.beginPath();
    
    data.forEach((item, index) => {
        const x = padding + (index / (data.length - 1)) * chartWidth;
        const y = canvas.height - padding - ((parseFloat(item.volume_liters) - minVolume) / range) * chartHeight;
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    
    ctx.stroke();
    
    // Desenhar pontos
    ctx.fillStyle = '#2196F3';
    data.forEach((item, index) => {
        const x = padding + (index / (data.length - 1)) * chartWidth;
        const y = canvas.height - padding - ((parseFloat(item.volume_liters) - minVolume) / range) * chartHeight;
        
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, 2 * Math.PI);
        ctx.fill();
    });
    
    // Desenhar labels do eixo Y
    ctx.fillStyle = '#666';
    ctx.font = '12px Arial';
    ctx.textAlign = 'right';
    
    for (let i = 0; i <= 5; i++) {
        const value = minVolume + (i / 5) * range;
        const y = canvas.height - padding - (i / 5) * chartHeight;
        ctx.fillText(window.formatVolume(value), padding - 5, y + 4);
    }
    
    // Desenhar labels do eixo X
    ctx.textAlign = 'center';
    data.forEach((item, index) => {
        const x = padding + (index / (data.length - 1)) * chartWidth;
        ctx.fillText(window.formatDate(item.production_date), x, canvas.height - padding + 20);
    });
};

// Criar gráfico de qualidade
window.createQualityChart = function(canvasId, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (!data || data.length === 0) {
        ctx.fillStyle = '#666';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Nenhum dado disponível', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    const labels = data.map(item => window.formatDate(item.test_date));
    const fatData = data.map(item => parseFloat(item.fat_percentage) || 0);
    const proteinData = data.map(item => parseFloat(item.protein_percentage) || 0);
    
    // Configurações do gráfico
    const padding = 40;
    const chartWidth = canvas.width - 2 * padding;
    const chartHeight = canvas.height - 2 * padding;
    
    const maxValue = Math.max(...fatData, ...proteinData);
    const minValue = Math.min(...fatData, ...proteinData);
    const range = maxValue - minValue || 1;
    
    // Desenhar eixos
    ctx.strokeStyle = '#ccc';
    ctx.lineWidth = 1;
    
    // Eixo Y
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, canvas.height - padding);
    ctx.stroke();
    
    // Eixo X
    ctx.beginPath();
    ctx.moveTo(padding, canvas.height - padding);
    ctx.lineTo(canvas.width - padding, canvas.height - padding);
    ctx.stroke();
    
    // Desenhar linha de gordura
    ctx.strokeStyle = '#FF9800';
    ctx.lineWidth = 3;
    ctx.beginPath();
    
    fatData.forEach((value, index) => {
        const x = padding + (index / (fatData.length - 1)) * chartWidth;
        const y = canvas.height - padding - ((value - minValue) / range) * chartHeight;
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    
    ctx.stroke();
    
    // Desenhar linha de proteína
    ctx.strokeStyle = '#4CAF50';
    ctx.lineWidth = 3;
    ctx.beginPath();
    
    proteinData.forEach((value, index) => {
        const x = padding + (index / (proteinData.length - 1)) * chartWidth;
        const y = canvas.height - padding - ((value - minValue) / range) * chartHeight;
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    
    ctx.stroke();
    
    // Desenhar pontos
    fatData.forEach((value, index) => {
        const x = padding + (index / (fatData.length - 1)) * chartWidth;
        const y = canvas.height - padding - ((value - minValue) / range) * chartHeight;
        
        ctx.fillStyle = '#FF9800';
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, 2 * Math.PI);
        ctx.fill();
    });
    
    proteinData.forEach((value, index) => {
        const x = padding + (index / (proteinData.length - 1)) * chartWidth;
        const y = canvas.height - padding - ((value - minValue) / range) * chartHeight;
        
        ctx.fillStyle = '#4CAF50';
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, 2 * Math.PI);
        ctx.fill();
    });
    
    // Desenhar labels do eixo Y
    ctx.fillStyle = '#666';
    ctx.font = '12px Arial';
    ctx.textAlign = 'right';
    
    for (let i = 0; i <= 5; i++) {
        const value = minValue + (i / 5) * range;
        const y = canvas.height - padding - (i / 5) * chartHeight;
        ctx.fillText(window.formatPercentage(value), padding - 5, y + 4);
    }
    
    // Desenhar labels do eixo X
    ctx.textAlign = 'center';
    labels.forEach((label, index) => {
        const x = padding + (index / (labels.length - 1)) * chartWidth;
        ctx.fillText(label, x, canvas.height - padding + 20);
    });
    
    // Legenda
    ctx.fillStyle = '#FF9800';
    ctx.fillRect(canvas.width - 120, 10, 15, 15);
    ctx.fillStyle = '#333';
    ctx.font = '12px Arial';
    ctx.textAlign = 'left';
    ctx.fillText('Gordura', canvas.width - 100, 22);
    
    ctx.fillStyle = '#4CAF50';
    ctx.fillRect(canvas.width - 120, 30, 15, 15);
    ctx.fillStyle = '#333';
    ctx.fillText('Proteína', canvas.width - 100, 42);
};

// Criar gráfico financeiro
window.createFinancialChart = function(canvasId, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Limpar canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (!data || data.length === 0) {
        ctx.fillStyle = '#666';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Nenhum dado disponível', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    // Separar receitas e despesas
    const receitas = data.filter(item => item.type === 'receita');
    const despesas = data.filter(item => item.type === 'despesa');
    
    const totalReceitas = receitas.reduce((sum, item) => sum + parseFloat(item.amount), 0);
    const totalDespesas = despesas.reduce((sum, item) => sum + parseFloat(item.amount), 0);
    const saldo = totalReceitas - totalDespesas;
    
    // Configurações do gráfico
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = Math.min(centerX, centerY) - 50;
    
    // Desenhar gráfico de pizza
    const total = totalReceitas + totalDespesas;
    
    if (total > 0) {
        // Receitas
        const receitasAngle = (totalReceitas / total) * 2 * Math.PI;
        ctx.fillStyle = '#4CAF50';
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, 0, receitasAngle);
        ctx.closePath();
        ctx.fill();
        
        // Despesas
        ctx.fillStyle = '#f44336';
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, receitasAngle, 2 * Math.PI);
        ctx.closePath();
        ctx.fill();
    }
    
    // Desenhar texto central
    ctx.fillStyle = '#333';
    ctx.font = 'bold 18px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('Saldo', centerX, centerY - 10);
    ctx.fillText(window.formatCurrency(saldo), centerX, centerY + 15);
    
    // Legenda
    ctx.fillStyle = '#4CAF50';
    ctx.fillRect(20, 20, 15, 15);
    ctx.fillStyle = '#333';
    ctx.font = '12px Arial';
    ctx.textAlign = 'left';
    ctx.fillText(`Receitas: ${window.formatCurrency(totalReceitas)}`, 40, 32);
    
    ctx.fillStyle = '#f44336';
    ctx.fillRect(20, 40, 15, 15);
    ctx.fillStyle = '#333';
    ctx.fillText(`Despesas: ${window.formatCurrency(totalDespesas)}`, 40, 52);
};

// Atualizar dashboard
window.updateDashboard = async function() {
    try {
        const api = await window.waitForAPI();
        
        // Carregar dados dos últimos 30 dias
        const endDate = new Date().toISOString().split('T')[0];
        const startDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        
        // Produção
        const productionResult = await api.production.getHistory(startDate, endDate);
        if (productionResult.success) {
            window.createProductionChart('productionChart', productionResult.data);
        }
        
        // Qualidade
        const qualityResult = await api.quality.getHistory(startDate, endDate);
        if (qualityResult.success) {
            window.createQualityChart('qualityChart', qualityResult.data);
        }
        
        // Financeiro
        const financialResult = await api.financial.getHistory(startDate, endDate);
        if (financialResult.success) {
            window.createFinancialChart('financialChart', financialResult.data);
        }
        
    } catch (error) {
        console.error('Erro ao atualizar dashboard:', error);
        window.showNotification('Erro ao carregar dados do dashboard', 'error');
    }
};

console.log('✅ Gráficos carregados');
