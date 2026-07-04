-- 1?? Verifica se a coluna 'Level' existe e cria se não existir
IF NOT EXISTS (
    SELECT * 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Enemies' 
      AND COLUMN_NAME = 'Level'
)
BEGIN
    ALTER TABLE Enemies
    ADD [Level] INT DEFAULT 1;
    PRINT 'Coluna [Level] criada com sucesso!';
END
ELSE
BEGIN
    PRINT 'Coluna [Level] já existe.';
END

-- 2?? Atualiza os níveis dos inimigos
-- Aqui você pode definir a lógica que quiser
-- Exemplo: nível proporcional ao XP (ajuste conforme necessário)
UPDATE Enemies
SET [Level] = CASE 
                 WHEN XP < 100 THEN 1
                 WHEN XP < 500 THEN 2
                 WHEN XP < 1000 THEN 3
                 ELSE 4
              END;

PRINT 'Níveis dos inimigos atualizados com sucesso!';
