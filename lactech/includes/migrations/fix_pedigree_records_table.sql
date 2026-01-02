-- Correção da tabela pedigree_records
-- Adiciona AUTO_INCREMENT ao ID, PRIMARY KEY e UNIQUE constraint

-- Passo 1: Verificar e remover duplicatas antes de adicionar constraints
-- Manter apenas o registro com o ID maior (mais recente) para cada combinação única
DELETE pr1 FROM pedigree_records pr1
INNER JOIN pedigree_records pr2 
WHERE pr1.id < pr2.id 
  AND pr1.animal_id = pr2.animal_id 
  AND pr1.generation = pr2.generation 
  AND pr1.position = pr2.position 
  AND pr1.farm_id = pr2.farm_id;

-- Passo 1.5: Corrigir IDs que estão como 0
-- Atualizar registros com id = 0 para IDs únicos sequenciais
SET @new_id = (SELECT COALESCE(MAX(id), 0) FROM pedigree_records WHERE id > 0);

UPDATE pedigree_records 
SET id = (@new_id := @new_id + 1)
WHERE id = 0
ORDER BY created_at, animal_id, generation, position;

-- Passo 2: Adicionar PRIMARY KEY primeiro (se não existir)
-- Se já existir, o MySQL vai ignorar ou dar erro, mas é seguro tentar
ALTER TABLE `pedigree_records` 
  ADD PRIMARY KEY (`id`);

-- Passo 3: Agora sim, adicionar AUTO_INCREMENT ao ID (precisa de PRIMARY KEY primeiro)
ALTER TABLE `pedigree_records` 
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Passo 4: Adicionar UNIQUE constraint para evitar duplicatas futuras
ALTER TABLE `pedigree_records` 
  ADD UNIQUE KEY `unique_pedigree_record` (`animal_id`, `generation`, `position`, `farm_id`);

-- Passo 5: Adicionar índices para melhor performance (se não existirem)
ALTER TABLE `pedigree_records` 
  ADD KEY `idx_animal_id` (`animal_id`),
  ADD KEY `idx_related_animal_id` (`related_animal_id`),
  ADD KEY `idx_farm_id` (`farm_id`);

