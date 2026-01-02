-- Correção da tabela pedigree_records (Versão Segura)
-- Adiciona AUTO_INCREMENT ao ID, PRIMARY KEY e UNIQUE constraint
-- Execute cada bloco separadamente e verifique se houve erro antes de continuar

-- ==========================================
-- Passo 1: Remover duplicatas
-- ==========================================
DELETE pr1 FROM pedigree_records pr1
INNER JOIN pedigree_records pr2 
WHERE pr1.id < pr2.id 
  AND pr1.animal_id = pr2.animal_id 
  AND pr1.generation = pr2.generation 
  AND pr1.position = pr2.position 
  AND pr1.farm_id = pr2.farm_id;

-- ==========================================
-- Passo 2: Verificar se PRIMARY KEY existe e adicionar se necessário
-- Execute este comando. Se der erro dizendo que já existe, ignore e continue.
-- ==========================================
ALTER TABLE `pedigree_records` 
  ADD PRIMARY KEY (`id`);

-- ==========================================
-- Passo 3: Adicionar AUTO_INCREMENT ao ID
-- Execute este comando APENAS se o Passo 2 foi bem-sucedido OU se a PRIMARY KEY já existia
-- ==========================================
ALTER TABLE `pedigree_records` 
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ==========================================
-- Passo 4: Adicionar UNIQUE constraint
-- Se der erro dizendo que já existe, ignore e continue.
-- ==========================================
ALTER TABLE `pedigree_records` 
  ADD UNIQUE KEY `unique_pedigree_record` (`animal_id`, `generation`, `position`, `farm_id`);

-- ==========================================
-- Passo 5: Adicionar índices
-- Se der erro dizendo que já existem, ignore e continue.
-- ==========================================
ALTER TABLE `pedigree_records` 
  ADD KEY `idx_animal_id` (`animal_id`);

ALTER TABLE `pedigree_records` 
  ADD KEY `idx_related_animal_id` (`related_animal_id`);

ALTER TABLE `pedigree_records` 
  ADD KEY `idx_farm_id` (`farm_id`);

