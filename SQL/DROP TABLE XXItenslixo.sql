-- 1️⃣ Remover as constraints que referenciam a tabela
DECLARE @sql NVARCHAR(MAX) = N'';

SELECT @sql += N'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) 
    + '].[' + OBJECT_NAME(parent_object_id) 
    + '] DROP CONSTRAINT [' + name + '];' + CHAR(13)
FROM sys.foreign_keys
WHERE referenced_object_id = OBJECT_ID('XXItenslixo');

EXEC sp_executesql @sql;

-- 2️⃣ Agora sim pode dropar a tabela
DROP TABLE XXItenslixo;
