-- Migration: Adicionar campos OAuth na tabela users
-- Execute este SQL se a tabela users já existir

USE safecode;

-- Adicionar campos para OAuth se não existirem
ALTER TABLE users 
  MODIFY COLUMN password_hash VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS provider VARCHAR(50) NULL COMMENT 'google, github, email',
  ADD COLUMN IF NOT EXISTS provider_id VARCHAR(255) NULL COMMENT 'ID do usuário no provider',
  ADD INDEX IF NOT EXISTS idx_provider (provider, provider_id);

