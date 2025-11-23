-- ============================================================
-- SafeNode - Script para Associar Sites Existentes aos Donos
-- Execute DEPOIS de adicionar a coluna user_id
-- ============================================================

-- PASSO 1: Ver todos os sites atuais
SELECT id, domain, display_name, user_id, created_at 
FROM safenode_sites 
ORDER BY id;

-- PASSO 2: Ver todos os usuários
SELECT id, username, email, full_name 
FROM safenode_users 
ORDER BY id;

-- ============================================================
-- PASSO 3: ASSOCIAR CADA SITE AO SEU DONO
-- Baseado no backup fornecido, existe apenas 1 site:
-- Site ID 2: denfy.vercel.app
-- 
-- Você precisa descobrir qual usuário criou este site
-- Opções de usuários:
-- - ID 1: admin@safenode.cloud
-- - ID 2: slavosier298@gmail.com
-- - ID 3: lavosiersilva02@gmail.com  
-- - ID 4: joselucenadev@gmail.com
-- ============================================================

-- EXEMPLO: Se o site "denfy.vercel.app" pertence ao usuário ID 4:
-- UPDATE safenode_sites SET user_id = 4 WHERE id = 2;

-- OU, se não souber, pergunte ao dono do site ou associe ao admin:
-- UPDATE safenode_sites SET user_id = 1 WHERE id = 2; -- admin

-- ============================================================
-- PASSO 4: Verificar se todos os sites têm dono
-- ============================================================
SELECT id, domain, display_name, user_id 
FROM safenode_sites 
WHERE user_id IS NULL;

-- Se aparecer algum site com user_id NULL, associe manualmente:
-- UPDATE safenode_sites SET user_id = X WHERE id = Y;

-- ============================================================
-- PASSO 5: Tornar user_id obrigatório (depois de associar todos)
-- ============================================================
-- Descomente e execute APENAS depois de associar todos os sites:
-- ALTER TABLE safenode_sites MODIFY user_id INT(11) NOT NULL;

