-- Substitua pelo username da conta que deseja deletar
DECLARE @Username NVARCHAR(50) = '001';

BEGIN TRY
    BEGIN TRANSACTION;

    -- 1?? Obter PlayerID
    DECLARE @PlayerID INT;
    SELECT @PlayerID = PlayerID 
    FROM [MumuDB].[dbo].[Players] 
    WHERE Username = @Username;

    IF @PlayerID IS NULL
    BEGIN
        RAISERROR('Usuário não encontrado!', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- 2?? Deletar Inventario
    IF OBJECT_ID('MumuDB.dbo.Personagens', 'U') IS NOT NULL 
       AND OBJECT_ID('MumuDB.dbo.Inventario', 'U') IS NOT NULL
    BEGIN
        DELETE FROM [MumuDB].[dbo].[Inventario]
        WHERE CharID IN (
            SELECT CharID FROM [MumuDB].[dbo].[Personagens] WHERE PlayerID = @PlayerID
        );
    END

    -- 3?? Deletar Personagens
    IF OBJECT_ID('MumuDB.dbo.Personagens', 'U') IS NOT NULL
    BEGIN
        DELETE FROM [MumuDB].[dbo].[Personagens]
        WHERE PlayerID = @PlayerID;
    END

    -- 4?? Deletar registros de LoginHistory
    IF OBJECT_ID('MumuDB.dbo.LoginHistory', 'U') IS NOT NULL
    BEGIN
        DELETE FROM [MumuDB].[dbo].[LoginHistory]
        WHERE PlayerID = @PlayerID;
    END

    -- 5?? Deletar registros de outras tabelas relacionadas (exemplo: CarteiraJSON, etc.)
    -- Adicione aqui outras tabelas que tenham FK para Players
    -- Exemplo:
    -- IF OBJECT_ID('MumuDB.dbo.OutraTabela', 'U') IS NOT NULL
    -- BEGIN
    --     DELETE FROM [MumuDB].[dbo.OutraTabela] WHERE PlayerID = @PlayerID;
    -- END

    -- 6?? Deletar o jogador
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
