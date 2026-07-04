IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'dbo.Frutas') AND type in (N'U'))
BEGIN
    CREATE TABLE dbo.Frutas (
        FrutaID INT IDENTITY(1,1) PRIMARY KEY,
        Nome NVARCHAR(100) NOT NULL,
        TempoCrescimento INT NOT NULL,
        PrecoSemente DECIMAL(10,2) NOT NULL,
        PrecoVendaSemente DECIMAL(10,2) NOT NULL,
        PrecoFruta DECIMAL(10,2) NOT NULL,
        PrecoVendaFruta DECIMAL(10,2) NOT NULL,
        PrecoTrocaFruta DECIMAL(10,2) NULL,
        PrecoTrocaSemente DECIMAL(10,2) NULL,
        Raridade TINYINT DEFAULT 1,
        DataCriacao DATETIME DEFAULT GETDATE()
    );
END
