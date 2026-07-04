PRINT '?? Verificando estrutura da tabela Items...';

-- 1?? Cria backup de segurança antes de alterar (opcional, mas recomendado)
IF OBJECT_ID('Items_backup') IS NULL
BEGIN
    SELECT * INTO Items_backup FROM Items;
    PRINT '?? Backup criado: Items_backup';
END
ELSE
BEGIN
    PRINT '?? Já existe backup anterior (Items_backup).';
END

-- 2?? Adiciona colunas que faltarem
IF COL_LENGTH('Items', 'PlayerID') IS NULL
BEGIN
    ALTER TABLE Items ADD PlayerID INT NULL;
    PRINT '? Coluna adicionada: PlayerID';
END

IF COL_LENGTH('Items', 'CharID') IS NULL
BEGIN
    ALTER TABLE Items ADD CharID INT NULL;
    PRINT '? Coluna adicionada: CharID';
END

IF COL_LENGTH('Items', 'Nome') IS NULL
BEGIN
    -- Tenta renomear colunas parecidas
    IF COL_LENGTH('Items', 'ItemName') IS NOT NULL
    BEGIN
        EXEC sp_rename 'Items.ItemName', 'Nome', 'COLUMN';
        PRINT '?? Coluna renomeada: ItemName ? Nome';
    END
    ELSE IF COL_LENGTH('Items', 'Name') IS NOT NULL
    BEGIN
        EXEC sp_rename 'Items.Name', 'Nome', 'COLUMN';
        PRINT '?? Coluna renomeada: Name ? Nome';
    END
    ELSE
    BEGIN
        ALTER TABLE Items ADD Nome VARCHAR(100) NULL;
        PRINT '? Coluna adicionada: Nome';
    END
END

IF COL_LENGTH('Items', 'Quantidade') IS NULL
BEGIN
    -- Tenta renomear colunas parecidas
    IF COL_LENGTH('Items', 'Quantity') IS NOT NULL
    BEGIN
        EXEC sp_rename 'Items.Quantity', 'Quantidade', 'COLUMN';
        PRINT '?? Coluna renomeada: Quantity ? Quantidade';
    END
    ELSE
    BEGIN
        ALTER TABLE Items ADD Quantidade INT DEFAULT 1;
        PRINT '? Coluna adicionada: Quantidade';
    END
END

-- 3?? Garante tipos e tamanhos
ALTER TABLE Items ALTER COLUMN Nome VARCHAR(100);
ALTER TABLE Items ALTER COLUMN Quantidade INT;

PRINT '? Estrutura verificada e ajustada com sucesso.';

-- 4?? Exibe estrutura final
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'Items';

PRINT '?? Tabela Items pronta para uso com dung.php e inventario.php';
