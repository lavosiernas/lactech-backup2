-- =====================================================
-- DADOS DE EXEMPLO - SISTEMA DE REPRODUÇÃO
-- =====================================================

-- Inserir animais para reprodução
INSERT INTO animals (animal_number, animal_name, breed, birth_date, sex, sire_number, dam_number, farm_id, reproductive_status) VALUES 
('001', 'Bela', 'Holandesa', '2020-03-15', 'Fêmea', 'S001', 'D001', 1, 'Prenha'),
('002', 'Luna', 'Holandesa', '2019-08-20', 'Fêmea', 'S002', 'D002', 1, 'Prenha'),
('003', 'Mimosa', 'Holandesa', '2021-01-10', 'Fêmea', 'S003', 'D003', 1, 'Vazia'),
('004', 'Flor', 'Holandesa', '2020-11-05', 'Fêmea', 'S001', 'D004', 1, 'Prenha'),
('005', 'Estrela', 'Holandesa', '2019-05-30', 'Fêmea', 'S004', 'D005', 1, 'Prenha');

-- Inserir touros
INSERT INTO bulls (bull_number, bull_name, breed, semen_type, farm_id) VALUES 
('S001', 'Champion', 'Holandesa', 'Convencional', 1),
('S002', 'Thunder', 'Holandesa', 'Sexado', 1),
('S003', 'King', 'Holandesa', 'Convencional', 1),
('S004', 'Storm', 'Holandesa', 'Sexado', 1);

-- Inserir inseminações
INSERT INTO inseminations (animal_id, bull_id, insemination_date, technician_name, success, notes, farm_id) VALUES 
(1, 1, '2024-08-15', 'Dr. João', 1, 'Inseminação bem-sucedida', 1),
(2, 2, '2024-09-10', 'Dr. João', 1, 'Sêmen sexado', 1),
(3, 1, '2024-10-05', 'Dr. João', 0, 'Não pegou', 1),
(4, 3, '2024-07-20', 'Dr. João', 1, 'Primeira IA', 1),
(5, 4, '2024-06-25', 'Dr. João', 1, 'Segunda IA', 1);

-- Inserir controles de prenhez
INSERT INTO pregnancy_controls (animal_id, pregnancy_confirmation_date, expected_birth_date, pregnancy_stage, confirmed_by, notes, farm_id) VALUES 
(1, '2024-09-15', '2025-06-22', 'Normal', 'Dr. João', 'Prenhez confirmada por ultrassom', 1),
(2, '2024-10-10', '2025-07-17', 'Normal', 'Dr. João', 'Prenhez confirmada', 1),
(4, '2024-08-20', '2025-05-27', 'Normal', 'Dr. João', 'Primeira prenhez', 1),
(5, '2024-07-25', '2025-05-01', 'Normal', 'Dr. João', 'Segunda prenhez', 1);

-- Inserir alertas de maternidade
INSERT INTO maternity_alerts (animal_id, alert_type, alert_level, title, message, expected_birth_date, due_date, is_active, created_at, farm_id) VALUES 
(1, 'pré_parto', 'medium', 'Preparação para parto', 'Animal entrando no período de pré-parto. Preparar local de parto.', '2025-06-22', '2025-06-15', 1, NOW(), 1),
(2, 'pré_parto', 'medium', 'Preparação para parto', 'Animal entrando no período de pré-parto. Preparar local de parto.', '2025-07-17', '2025-07-10', 1, NOW(), 1),
(4, 'pré_parto', 'medium', 'Preparação para parto', 'Animal entrando no período de pré-parto. Preparar local de parto.', '2025-05-27', '2025-05-20', 1, NOW(), 1),
(5, 'parto_iminente', 'high', 'Parto iminente', 'Animal com parto previsto para os próximos 7 dias. Monitoramento intensivo necessário.', '2025-05-01', '2025-05-01', 1, NOW(), 1);

-- Inserir ciclos de cio (exemplo)
INSERT INTO heat_cycles (animal_id, heat_date, heat_duration_hours, intensity, observed_by, notes, farm_id) VALUES 
(3, '2024-10-15', 18, 'Alto', 'Funcionário 1', 'Cio bem marcado', 1),
(3, '2024-11-12', 16, 'Médio', 'Funcionário 1', 'Cio regular', 1);

-- Inserir partos históricos (para cálculo de performance)
INSERT INTO births (animal_id, birth_date, birth_type, calf_sex, calf_weight, calf_number, sire_id, notes, farm_id) VALUES 
(1, '2023-06-20', 'Normal', 'Fêmea', 35.5, 'C001', 1, 'Primeiro parto', 1),
(2, '2023-07-15', 'Normal', 'Macho', 38.2, 'C002', 2, 'Parto normal', 1),
(4, '2023-05-25', 'Normal', 'Fêmea', 33.8, 'C003', 3, 'Primeiro parto', 1),
(5, '2023-04-30', 'Normal', 'Fêmea', 36.1, 'C004', 4, 'Segundo parto', 1);

-- Atualizar animais com status reprodutivo baseado nos dados
UPDATE animals SET reproductive_status = 'Prenha' WHERE id IN (1, 2, 4, 5);
UPDATE animals SET reproductive_status = 'Vazia' WHERE id = 3;

