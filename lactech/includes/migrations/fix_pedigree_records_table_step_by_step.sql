-- Correção da tabela pedigree_records (Execute passo a passo)
-- Execute cada seção separadamente e verifique se funcionou antes de continuar

-- ==========================================
-- PASSO 1: Remover duplicatas
-- ==========================================
DELETE pr1 FROM pedigree_records pr1
INNER JOIN pedigree_records pr2 
WHERE pr1.id < pr2.id 
  AND pr1.animal_id = pr2.animal_id 
  AND pr1.generation = pr2.generation 
  AND pr1.position = pr2.position 
  AND pr1.farm_id = pr2.farm_id;

-- ==========================================
-- PASSO 2: Corrigir IDs que estão como 0
-- Execute este bloco completo de uma vez
-- ==========================================
SET @new_id = (SELECT COALESCE(MAX(id), 0) FROM pedigree_records WHERE id > 0);

UPDATE pedigree_records 
SET id = (@new_id := @new_id + 1)
WHERE id = 0
ORDER BY created_at, animal_id, generation, position;

-- ==========================================
-- PASSO 3: Adicionar PRIMARY KEY
-- Execute este comando. Se der erro dizendo que já existe, ignore.
-- ==========================================
ALTER TABLE `pedigree_records` 
  ADD PRIMARY KEY (`id`);

-- ==========================================
-- PASSO 4: Adicionar AUTO_INCREMENT
-- Execute este comando APENAS se o Passo 3 foi bem-sucedido
-- ==========================================
ALTER TABLE `pedigree_records` 
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ==========================================
-- PASSO 5: Adicionar UNIQUE constraint
-- Se der erro dizendo que já existe, ignore.
-- ==========================================
ALTER TABLE `pedigree_records` 
  ADD UNIQUE KEY `unique_pedigree_record` (`animal_id`, `generation`, `position`, `farm_id`);

-- ==========================================
-- PASSO 6: Adicionar índices (execute cada um separadamente)
-- Se der erro dizendo que já existe, ignore e continue.
-- ==========================================
ALTER TABLE `pedigree_records` 
  ADD KEY `idx_animal_id` (`animal_id`);

ALTER TABLE `pedigree_records` 
  ADD KEY `idx_related_animal_id` (`related_animal_id`);

ALTER TABLE `pedigree_records` 
  ADD KEY `idx_farm_id` (`farm_id`);

