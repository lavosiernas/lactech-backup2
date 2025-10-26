/**
 * PDF Generator - Lactech
 * Geração de relatórios em PDF
 */

class PDFGenerator {
    constructor() {
        this.templates = new Map();
        this.init();
    }

    init() {
        console.log('📄 PDF Generator inicializado');
        this.setupTemplates();
        this.setupPrintStyles();
    }

    /**
     * Configurar templates
     */
    setupTemplates() {
        // Template para relatório de volume
        this.templates.set('volume_report', {
            title: 'Relatório de Volume de Leite',
            fields: ['data', 'volume', 'periodo', 'temperatura', 'produtor'],
            format: 'A4'
        });

        // Template para relatório de qualidade
        this.templates.set('quality_report', {
            title: 'Relatório de Qualidade',
            fields: ['data', 'gordura', 'proteina', 'lactose', 'ccs', 'cbt'],
            format: 'A4'
        });

        // Template para relatório financeiro
        this.templates.set('financial_report', {
            title: 'Relatório Financeiro',
            fields: ['data', 'tipo', 'valor', 'descricao', 'status'],
            format: 'A4'
        });
    }

    /**
     * Configurar estilos de impressão
     */
    setupPrintStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                .no-print { display: none !important; }
                .print-only { display: block !important; }
                body { font-family: Arial, sans-serif; }
                .pdf-header { 
                    text-align: center; 
                    margin-bottom: 20px; 
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .pdf-content { 
                    margin: 20px 0; 
                }
                .pdf-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 10px 0;
                }
                .pdf-table th, .pdf-table td { 
                    border: 1px solid #333; 
                    padding: 8px; 
                    text-align: left;
                }
                .pdf-table th { 
                    background-color: #f5f5f5; 
                    font-weight: bold;
                }
                .pdf-footer { 
                    margin-top: 30px; 
                    text-align: center; 
                    font-size: 12px; 
                    color: #666;
                }
                @page { 
                    margin: 1cm; 
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Gerar PDF de relatório de volume
     */
    generateVolumeReport(data, options = {}) {
        const template = this.templates.get('volume_report');
        const content = this.buildVolumeReportContent(data, template);
        
        return this.generatePDF(content, {
            title: template.title,
            ...options
        });
    }

    /**
     * Gerar PDF de relatório de qualidade
     */
    generateQualityReport(data, options = {}) {
        const template = this.templates.get('quality_report');
        const content = this.buildQualityReportContent(data, template);
        
        return this.generatePDF(content, {
            title: template.title,
            ...options
        });
    }

    /**
     * Gerar PDF de relatório financeiro
     */
    generateFinancialReport(data, options = {}) {
        const template = this.templates.get('financial_report');
        const content = this.buildFinancialReportContent(data, template);
        
        return this.generatePDF(content, {
            title: template.title,
            ...options
        });
    }

    /**
     * Construir conteúdo do relatório de volume
     */
    buildVolumeReportContent(data, template) {
        const dateRange = this.getDateRange(data);
        const totalVolume = data.reduce((sum, item) => sum + (item.volume || 0), 0);
        const averageVolume = data.length > 0 ? totalVolume / data.length : 0;

        return `
            <div class="pdf-header">
                <h1>${template.title}</h1>
                <p>Período: ${dateRange}</p>
                <p>Total de Registros: ${data.length}</p>
            </div>
            
            <div class="pdf-content">
                <h2>Resumo</h2>
                <p><strong>Volume Total:</strong> ${totalVolume.toFixed(2)}L</p>
                <p><strong>Volume Médio:</strong> ${averageVolume.toFixed(2)}L</p>
                
                <h2>Detalhes</h2>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Volume (L)</th>
                            <th>Período</th>
                            <th>Temperatura (°C)</th>
                            <th>Produtor</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(item => `
                            <tr>
                                <td>${this.formatDate(item.collection_date)}</td>
                                <td>${item.volume || 0}</td>
                                <td>${this.formatPeriod(item.period)}</td>
                                <td>${item.temperature || '-'}</td>
                                <td>${item.producer_name || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="pdf-footer">
                <p>Relatório gerado em ${new Date().toLocaleString('pt-BR')}</p>
                <p>Lactech - Sistema de Gestão de Fazenda</p>
            </div>
        `;
    }

    /**
     * Construir conteúdo do relatório de qualidade
     */
    buildQualityReportContent(data, template) {
        const dateRange = this.getDateRange(data);
        const avgFat = this.calculateAverage(data, 'fat_percentage');
        const avgProtein = this.calculateAverage(data, 'protein_percentage');

        return `
            <div class="pdf-header">
                <h1>${template.title}</h1>
                <p>Período: ${dateRange}</p>
                <p>Total de Testes: ${data.length}</p>
            </div>
            
            <div class="pdf-content">
                <h2>Resumo</h2>
                <p><strong>Gordura Média:</strong> ${avgFat.toFixed(2)}%</p>
                <p><strong>Proteína Média:</strong> ${avgProtein.toFixed(2)}%</p>
                
                <h2>Detalhes</h2>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Gordura (%)</th>
                            <th>Proteína (%)</th>
                            <th>Lactose (%)</th>
                            <th>CCS</th>
                            <th>CBT</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(item => `
                            <tr>
                                <td>${this.formatDate(item.test_date)}</td>
                                <td>${item.fat_percentage || '-'}</td>
                                <td>${item.protein_percentage || '-'}</td>
                                <td>${item.lactose_percentage || '-'}</td>
                                <td>${item.ccs || '-'}</td>
                                <td>${item.cbt || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="pdf-footer">
                <p>Relatório gerado em ${new Date().toLocaleString('pt-BR')}</p>
                <p>Lactech - Sistema de Gestão de Fazenda</p>
            </div>
        `;
    }

    /**
     * Construir conteúdo do relatório financeiro
     */
    buildFinancialReportContent(data, template) {
        const dateRange = this.getDateRange(data);
        const totalIncome = data.filter(item => item.type === 'income').reduce((sum, item) => sum + (item.amount || 0), 0);
        const totalExpense = data.filter(item => item.type === 'expense').reduce((sum, item) => sum + (item.amount || 0), 0);
        const balance = totalIncome - totalExpense;

        return `
            <div class="pdf-header">
                <h1>${template.title}</h1>
                <p>Período: ${dateRange}</p>
                <p>Total de Registros: ${data.length}</p>
            </div>
            
            <div class="pdf-content">
                <h2>Resumo Financeiro</h2>
                <p><strong>Receitas:</strong> R$ ${totalIncome.toFixed(2)}</p>
                <p><strong>Despesas:</strong> R$ ${totalExpense.toFixed(2)}</p>
                <p><strong>Saldo:</strong> R$ ${balance.toFixed(2)}</p>
                
                <h2>Detalhes</h2>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Descrição</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(item => `
                            <tr>
                                <td>${this.formatDate(item.created_at)}</td>
                                <td>${this.formatType(item.type)}</td>
                                <td>R$ ${(item.amount || 0).toFixed(2)}</td>
                                <td>${item.description || '-'}</td>
                                <td>${this.formatStatus(item.status)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="pdf-footer">
                <p>Relatório gerado em ${new Date().toLocaleString('pt-BR')}</p>
                <p>Lactech - Sistema de Gestão de Fazenda</p>
            </div>
        `;
    }

    /**
     * Gerar PDF
     */
    generatePDF(content, options = {}) {
        const { title = 'Relatório', filename = 'relatorio.pdf' } = options;
        
        // Criar janela de impressão
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                    .pdf-header { 
                        text-align: center; 
                        margin-bottom: 20px; 
                        border-bottom: 2px solid #333;
                        padding-bottom: 10px;
                    }
                    .pdf-content { margin: 20px 0; }
                    .pdf-table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin: 10px 0;
                    }
                    .pdf-table th, .pdf-table td { 
                        border: 1px solid #333; 
                        padding: 8px; 
                        text-align: left;
                    }
                    .pdf-table th { 
                        background-color: #f5f5f5; 
                        font-weight: bold;
                    }
                    .pdf-footer { 
                        margin-top: 30px; 
                        text-align: center; 
                        font-size: 12px; 
                        color: #666;
                    }
                    @media print {
                        @page { margin: 1cm; }
                    }
                </style>
            </head>
            <body>
                ${content}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Aguardar carregamento e imprimir
        printWindow.onload = () => {
            printWindow.print();
            printWindow.close();
        };
        
        return printWindow;
    }

    /**
     * Obter intervalo de datas
     */
    getDateRange(data) {
        if (data.length === 0) return 'N/A';
        
        const dates = data.map(item => new Date(item.collection_date || item.test_date || item.created_at));
        const minDate = new Date(Math.min(...dates));
        const maxDate = new Date(Math.max(...dates));
        
        return `${minDate.toLocaleDateString('pt-BR')} - ${maxDate.toLocaleDateString('pt-BR')}`;
    }

    /**
     * Calcular média
     */
    calculateAverage(data, field) {
        const values = data.map(item => parseFloat(item[field]) || 0);
        const sum = values.reduce((acc, val) => acc + val, 0);
        return values.length > 0 ? sum / values.length : 0;
    }

    /**
     * Formatar data
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('pt-BR');
    }

    /**
     * Formatar período
     */
    formatPeriod(period) {
        const periods = {
            'manha': 'Manhã',
            'tarde': 'Tarde',
            'noite': 'Noite',
            'madrugada': 'Madrugada'
        };
        return periods[period] || period;
    }

    /**
     * Formatar tipo
     */
    formatType(type) {
        const types = {
            'income': 'Receita',
            'expense': 'Despesa'
        };
        return types[type] || type;
    }

    /**
     * Formatar status
     */
    formatStatus(status) {
        const statuses = {
            'pending': 'Pendente',
            'completed': 'Concluído',
            'cancelled': 'Cancelado'
        };
        return statuses[status] || status;
    }
}

// Inicializar PDF Generator
document.addEventListener('DOMContentLoaded', () => {
    window.pdfGenerator = new PDFGenerator();
});

// Exportar para uso global
window.PDFGenerator = PDFGenerator;

