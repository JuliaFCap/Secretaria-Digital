-- Execute em bancos já instalados antes de atualizar os arquivos PHP.
-- Senhas SHA-256 existentes serão convertidas para password_hash()
-- automaticamente após o próximo login válido.
ALTER TABLE tb_usuarios
  MODIFY senha VARCHAR(255) NOT NULL;
