-- =====================================================
-- VERIFICAÇÃO RÁPIDA DA TABELA DE FOTOS
-- =====================================================

-- Verificar se a tabela users existe e tem colunas de foto
SELECT 
    'VERIFICAÇÃO RÁPIDA' as tipo,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users') 
        THEN '✅ Tabela users existe'
        ELSE '❌ Tabela users NÃO existe'
    END as status_tabela,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'profile_photo_url') 
        THEN '✅ Coluna profile_photo_url existe'
        ELSE '❌ Coluna profile_photo_url NÃO existe'
    END as status_foto_perfil,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'report_farm_logo_base64') 
        THEN '✅ Coluna logo fazenda existe'
        ELSE '❌ Coluna logo fazenda NÃO existe'
    END as status_logo_fazenda,
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'report_system_logo_base64') 
        THEN '✅ Coluna logo sistema existe'
        ELSE '❌ Coluna logo sistema NÃO existe'
    END as status_logo_sistema;

-- Verificar dados de fotos existentes
SELECT 
    'DADOS DE FOTOS' as tipo,
    COUNT(*) as total_usuarios,
    COUNT(profile_photo_url) as com_foto_perfil,
    COUNT(report_farm_logo_base64) as com_logo_fazenda,
    COUNT(report_system_logo_base64) as com_logo_sistema
FROM users;

-- Mostrar estrutura das colunas de foto
SELECT 
    column_name,
    data_type,
    is_nullable,
    'Coluna de foto/imagem' as tipo_coluna
FROM information_schema.columns 
WHERE table_name = 'users' 
AND (
    column_name LIKE '%photo%' 
    OR column_name LIKE '%logo%' 
    OR column_name LIKE '%base64%'
)
ORDER BY column_name;
