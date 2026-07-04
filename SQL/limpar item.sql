USE [MumuDB];
GO

PRINT '🧹 Iniciando limpeza da tabela Items...';

-- Verifica se a tabela possui chaves estrangeiras (FOREIGN KEYS)
IF EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE referenced_object_id = OBJECT_ID('dbo.Items')
)
BEGIN
    PRINT '⚠️ A tabela [Items] possui relações com outras tabelas (FOREIGN KEYS).';
    PRINT 'Usando método seguro: DELETE.';
    
    DELETE FROM dbo.Items;

    -- Reinicia o contador de IDENTITY (caso exista coluna auto incremental)
    DBCC CHECKIDENT ('dbo.Items', RESEED, 0);

    PRINT '✅ Todos os registros foram removidos com sucesso.';
    PRINT '🔁 Contador de ID reiniciado para 0.';
END
ELSE
BEGIN
    PRINT '✅ Nenhuma relação (FOREIGN KEY) encontrada.';
    PRINT 'Usando método rápido: TRUNCATE TABLE.';

    TRUNCATE TABLE dbo.Items;

    PRINT '✅ Tabela esvaziada com sucesso.';
    PRINT '🔁 Contador de ID reiniciado automaticamente.';
END
GO
