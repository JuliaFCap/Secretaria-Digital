-- Execute em bancos ja instalados para exigir telefone em todas as contas.
-- Telefones de placeholder ficam com WhatsApp desativado; substitua-os pelos
-- dados corretos antes de habilitar o recebimento de avisos.
UPDATE tb_usuarios
SET telefone = '(00) 00000-0000',
    aviso_whatsapp = 0
WHERE telefone IS NULL OR TRIM(telefone) = '';

-- RA e obrigatorio apenas para alunos; contas sem RA recebem um identificador
-- sintetico, unico por usuario, para posterior substituicao pelo RA correto.
UPDATE tb_usuarios
SET ra = CASE login
  WHEN 'mario.ambrosio' THEN '20250001'
  WHEN 'lara.mendonca' THEN '20250002'
  WHEN 'pedro.azevedo' THEN '20250003'
  WHEN 'camila.tavares' THEN '20250004'
  WHEN 'rafael.campos' THEN '20250005'
  WHEN 'isabela.freire' THEN '20250006'
  WHEN 'diego.barros' THEN '20250007'
END
WHERE nivel = 'aluno'
  AND ra LIKE 'DEMO-%'
  AND login IN (
    'mario.ambrosio', 'lara.mendonca', 'pedro.azevedo',
    'camila.tavares', 'rafael.campos', 'isabela.freire', 'diego.barros'
  );

UPDATE tb_usuarios
SET ra = CONCAT('2026', LPAD(id_usuario, 8, '0'))
WHERE nivel = 'aluno' AND (ra IS NULL OR TRIM(ra) = '');

-- Semestre sintetico apenas para alunos sem um valor valido.
UPDATE tb_usuarios
SET semestre = 1
WHERE nivel = 'aluno' AND (semestre IS NULL OR semestre < 1 OR semestre > 10);

ALTER TABLE tb_usuarios
  MODIFY telefone VARCHAR(20) NOT NULL;
