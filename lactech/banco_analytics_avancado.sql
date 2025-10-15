-- =====================================================
-- BANCO DE DADOS AVANÇADO - DASHBOARD ANALÍTICO
-- =====================================================

-- Tabela para indicadores gerenciais
CREATE TABLE IF NOT EXISTS management_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    indicator_type VARCHAR(50) NOT NULL,
    indicator_name VARCHAR(100) NOT NULL,
    current_value DECIMAL(10,2),
    target_value DECIMAL(10,2),
    unit VARCHAR(20),
    calculation_date DATE NOT NULL,
    period_type ENUM('daily', 'weekly', 'monthly', 'yearly') DEFAULT 'monthly',
    status ENUM('good', 'warning', 'critical') DEFAULT 'good',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    INDEX idx_farm_indicator (farm_id, indicator_type),
    INDEX idx_calculation_date (calculation_date)
);

-- Tabela para métricas de produção
CREATE TABLE IF NOT EXISTS production_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    metric_date DATE NOT NULL,
    total_volume DECIMAL(10,2) DEFAULT 0,
    average_fat_percentage DECIMAL(5,2) DEFAULT 0,
    average_protein_percentage DECIMAL(5,2) DEFAULT 0,
    average_scc DECIMAL(10,0) DEFAULT 0,
    total_animals_milked INT DEFAULT 0,
    average_production_per_animal DECIMAL(8,2) DEFAULT 0,
    production_efficiency DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_farm_date (farm_id, metric_date),
    INDEX idx_farm_date (farm_id, metric_date)
);

-- Tabela para métricas reprodutivas
CREATE TABLE IF NOT EXISTS reproductive_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    metric_date DATE NOT NULL,
    pregnancy_rate DECIMAL(5,2) DEFAULT 0,
    calving_interval_days INT DEFAULT 0,
    services_per_conception DECIMAL(3,1) DEFAULT 0,
    first_heat_days INT DEFAULT 0,
    total_inseminations INT DEFAULT 0,
    successful_inseminations INT DEFAULT 0,
    open_cows_count INT DEFAULT 0,
    pregnant_cows_count INT DEFAULT 0,
    dry_cows_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_farm_date (farm_id, metric_date),
    INDEX idx_farm_date (farm_id, metric_date)
);

-- Tabela para métricas sanitárias
CREATE TABLE IF NOT EXISTS health_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    metric_date DATE NOT NULL,
    mastitis_rate DECIMAL(5,2) DEFAULT 0,
    lameness_rate DECIMAL(5,2) DEFAULT 0,
    mortality_rate DECIMAL(5,2) DEFAULT 0,
    vaccination_coverage DECIMAL(5,2) DEFAULT 0,
    treatment_cost DECIMAL(10,2) DEFAULT 0,
    prevention_cost DECIMAL(10,2) DEFAULT 0,
    total_health_cost DECIMAL(10,2) DEFAULT 0,
    health_efficiency_score DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_farm_date (farm_id, metric_date),
    INDEX idx_farm_date (farm_id, metric_date)
);

-- Tabela para métricas financeiras
CREATE TABLE IF NOT EXISTS financial_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    metric_date DATE NOT NULL,
    total_revenue DECIMAL(12,2) DEFAULT 0,
    milk_revenue DECIMAL(12,2) DEFAULT 0,
    animal_sales_revenue DECIMAL(12,2) DEFAULT 0,
    total_costs DECIMAL(12,2) DEFAULT 0,
    feed_costs DECIMAL(12,2) DEFAULT 0,
    labor_costs DECIMAL(12,2) DEFAULT 0,
    health_costs DECIMAL(12,2) DEFAULT 0,
    maintenance_costs DECIMAL(12,2) DEFAULT 0,
    net_profit DECIMAL(12,2) DEFAULT 0,
    profit_margin DECIMAL(5,2) DEFAULT 0,
    cost_per_liter DECIMAL(8,2) DEFAULT 0,
    revenue_per_animal DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_farm_date (farm_id, metric_date),
    INDEX idx_farm_date (farm_id, metric_date)
);

-- Tabela para alertas de performance
CREATE TABLE IF NOT EXISTS performance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    metric_type VARCHAR(50) NOT NULL,
    alert_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    current_value DECIMAL(10,2),
    target_value DECIMAL(10,2),
    deviation_percentage DECIMAL(5,2),
    is_active BOOLEAN DEFAULT TRUE,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_farm_active (farm_id, is_active),
    INDEX idx_alert_type (alert_type),
    INDEX idx_alert_level (alert_level)
);

-- Views para dashboard
CREATE OR REPLACE VIEW v_dashboard_summary AS
SELECT 
    f.id as farm_id,
    f.name as farm_name,
    -- Produção (últimos 30 dias)
    COALESCE(SUM(pm.total_volume), 0) as total_volume_30d,
    COALESCE(AVG(pm.average_fat_percentage), 0) as avg_fat_30d,
    COALESCE(AVG(pm.average_protein_percentage), 0) as avg_protein_30d,
    COALESCE(AVG(pm.production_efficiency), 0) as production_efficiency_30d,
    
    -- Reprodutivo (último mês)
    COALESCE(rm.pregnancy_rate, 0) as pregnancy_rate,
    COALESCE(rm.calving_interval_days, 0) as calving_interval,
    COALESCE(rm.services_per_conception, 0) as services_per_conception,
    
    -- Sanitário (último mês)
    COALESCE(hm.mastitis_rate, 0) as mastitis_rate,
    COALESCE(hm.health_efficiency_score, 0) as health_score,
    
    -- Financeiro (último mês)
    COALESCE(finm.net_profit, 0) as net_profit,
    COALESCE(finm.profit_margin, 0) as profit_margin,
    COALESCE(finm.cost_per_liter, 0) as cost_per_liter,
    
    -- Alertas ativos
    (SELECT COUNT(*) FROM performance_alerts pa WHERE pa.farm_id = f.id AND pa.is_active = TRUE) as active_alerts_count
    
FROM farms f
LEFT JOIN production_metrics pm ON f.id = pm.farm_id AND pm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
LEFT JOIN reproductive_metrics rm ON f.id = rm.farm_id AND rm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
LEFT JOIN health_metrics hm ON f.id = hm.farm_id AND hm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
LEFT JOIN financial_metrics finm ON f.id = finm.farm_id AND finm.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
WHERE f.id = 1
GROUP BY f.id, f.name, rm.pregnancy_rate, rm.calving_interval_days, rm.services_per_conception,
         hm.mastitis_rate, hm.health_efficiency_score, finm.net_profit, finm.profit_margin, finm.cost_per_liter;

-- View para indicadores de performance
CREATE OR REPLACE VIEW v_performance_indicators AS
SELECT 
    mi.*,
    f.name as farm_name,
    CASE 
        WHEN mi.current_value >= mi.target_value * 1.1 THEN 'excellent'
        WHEN mi.current_value >= mi.target_value THEN 'good'
        WHEN mi.current_value >= mi.target_value * 0.8 THEN 'warning'
        ELSE 'critical'
    END as performance_status
FROM management_indicators mi
JOIN farms f ON mi.farm_id = f.id
WHERE mi.farm_id = 1
ORDER BY mi.indicator_type, mi.calculation_date DESC;

-- Triggers para atualização automática de métricas
DELIMITER //

CREATE TRIGGER tr_update_production_metrics_after_volume
AFTER INSERT ON volume_records
FOR EACH ROW
BEGIN
    INSERT INTO production_metrics (farm_id, metric_date, total_volume, total_animals_milked)
    VALUES (NEW.farm_id, NEW.collection_date, NEW.volume, 1)
    ON DUPLICATE KEY UPDATE
        total_volume = total_volume + NEW.volume,
        total_animals_milked = total_animals_milked + 1,
        average_production_per_animal = total_volume / total_animals_milked,
        updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER tr_update_production_metrics_after_quality
AFTER INSERT ON quality_tests
FOR EACH ROW
BEGIN
    UPDATE production_metrics 
    SET 
        average_fat_percentage = (
            SELECT AVG(fat_percentage) 
            FROM quality_tests qt 
            WHERE qt.farm_id = NEW.farm_id 
            AND DATE(qt.test_date) = NEW.test_date
        ),
        average_protein_percentage = (
            SELECT AVG(protein_percentage) 
            FROM quality_tests qt 
            WHERE qt.farm_id = NEW.farm_id 
            AND DATE(qt.test_date) = NEW.test_date
        ),
        average_scc = (
            SELECT AVG(scc) 
            FROM quality_tests qt 
            WHERE qt.farm_id = NEW.farm_id 
            AND DATE(qt.test_date) = NEW.test_date
        ),
        updated_at = CURRENT_TIMESTAMP
    WHERE farm_id = NEW.farm_id AND metric_date = DATE(NEW.test_date);
END//

CREATE TRIGGER tr_update_reproductive_metrics_after_insemination
AFTER INSERT ON inseminations
FOR EACH ROW
BEGIN
    -- Atualizar métricas reprodutivas do dia
    INSERT INTO reproductive_metrics (farm_id, metric_date, total_inseminations)
    VALUES (NEW.farm_id, NEW.insemination_date, 1)
    ON DUPLICATE KEY UPDATE
        total_inseminations = total_inseminations + 1,
        updated_at = CURRENT_TIMESTAMP;
        
    -- Se inseminação foi bem-sucedida, atualizar contadores
    IF NEW.success = 1 THEN
        UPDATE reproductive_metrics 
        SET successful_inseminations = successful_inseminations + 1,
            pregnancy_rate = (successful_inseminations + 1) / (total_inseminations + 1) * 100
        WHERE farm_id = NEW.farm_id AND metric_date = NEW.insemination_date;
    END IF;
END//

DELIMITER ;

-- Índices para performance
CREATE INDEX idx_production_metrics_period ON production_metrics (farm_id, metric_date, period_type);
CREATE INDEX idx_reproductive_metrics_period ON reproductive_metrics (farm_id, metric_date);
CREATE INDEX idx_health_metrics_period ON health_metrics (farm_id, metric_date);
CREATE INDEX idx_financial_metrics_period ON financial_metrics (farm_id, metric_date);
CREATE INDEX idx_performance_alerts_active ON performance_alerts (farm_id, is_active, alert_level);

