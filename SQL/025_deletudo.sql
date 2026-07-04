-- Substitua pelo username da conta que deseja deletar
DECLARE @Username NVARCHAR(50) = 'teste001';
DECLARE @PlayerID INT;

BEGIN TRY
    BEGIN TRANSACTION;

    -- 1?? Obter PlayerID
    SELECT @PlayerID = PlayerID 
    FROM [MumuDB].[dbo].[Players] 
    WHERE Username = @Username;

    IF @PlayerID IS NULL
    BEGIN
        RAISERROR('Usuário não encontrado!', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- 2?? Deletar Inventario e Personagens (mesmo sem FK)
    IF OBJECT_ID('MumuDB.dbo.Personagens', 'U') IS NOT NULL 
       AND OBJECT_ID('MumuDB.dbo.Inventario', 'U') IS NOT NULL
    BEGIN
        DELETE FROM [MumuDB].[dbo].[Inventario]
        WHERE CharID IN (
            SELECT CharID FROM [MumuDB].[dbo].[Personagens] WHERE PlayerID = @PlayerID
        );

        DELETE FROM [MumuDB].[dbo].[Personagens]
        WHERE PlayerID = @PlayerID;
    END

    -- 3?? Deletar registros de todas as tabelas que têm FK para Players
    DECLARE @sql NVARCHAR(MAX);

    DECLARE fk_cursor CURSOR FOR
    SELECT 
        t.name AS TableName,
        c.name AS ColumnName
    FROM sys.foreign_key_columns fkc
    INNER JOIN sys.tables t ON fkc.parent_object_id = t.object_id
    INNER JOIN sys.columns c ON fkc.parent_object_id = c.object_id AND fkc.parent_column_id = c.column_id
    INNER JOIN sys.tables ref_t ON fkc.referenced_object_id = ref_t.object_id
    INNER JOIN sys.columns ref_c ON fkc.referenced_object_id = ref_c.object_id AND fkc.referenced_column_id = ref_c.column_id
    WHERE ref_t.name = 'Players' AND ref_c.name = 'PlayerID';

    OPEN fk_cursor;
    DECLARE @TableName NVARCHAR(128), @ColumnName NVARCHAR(128);

    FETCH NEXT FROM fk_cursor INTO @TableName, @ColumnName;
    WHILE @@FETCH_STATUS = 0
    BEGIN
        SET @sql = N'DELETE FROM [' + @TableName + '] WHERE [' + @ColumnName + '] = @PlayerID;';
        EXEC sp_executesql @sql, N'@PlayerID INT', @PlayerID;
        FETCH NEXT FROM fk_cursor INTO @TableName, @ColumnName;
    END

    CLOSE fk_cursor;
    DEALLOCATE fk_cursor;

    -- 4?? Deletar o jogador
    DELETE FROM [MumuDB].[dbo].[Players]
    WHERE PlayerID = @PlayerID;

    COMMIT TRANSACTION;
    PRINT 'Conta e todos os dados relacionados deletados com sucesso!';

END TRY
BEGIN CATCH
    ROLLBACK TRANSACTION;

    DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @ErrorSeverity INT = ERROR_SEVERITY();
    DECLARE @ErrorState INT = ERROR_STATE();

    RAISERROR('Erro ao deletar conta: %s', @ErrorSeverity, @ErrorState, @ErrorMessage);
END CATCH
