IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[Ativos]') AND type in (N'U'))
BEGIN
    CREATE TABLE Ativos (
        Nome NVARCHAR(50) PRIMARY KEY,
        Preco INT NOT NULL,
        UltimaVariacao INT NOT NULL DEFAULT 0
    );
END
