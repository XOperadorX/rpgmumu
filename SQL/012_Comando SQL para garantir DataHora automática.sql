USE MumuDB;
GO

-- Adiciona a coluna DataHora se não existir
IF COL_LENGTH('Historico', 'DataHora') IS NULL
BEGIN
    ALTER TABLE Historico ADD DataHora DATETIME DEFAULT GETDATE();
    PRINT '? Coluna DataHora adicionada com valor padrão GETDATE()';
END
ELSE
BEGIN
    -- Se já existir, garante que o DEFAULT está configurado
    DECLARE @dfName NVARCHAR(128);
    SELECT @dfName = df.name
    FROM sys.tables t
    JOIN sys.columns c ON t.object_id = c.object_id
    LEFT JOIN sys.default_constraints df ON c.default_object_id = df.object_id
    WHERE t.name = 'Historico' AND c.name = 'DataHora';

    IF @dfName IS NULL
    BEGIN
        ALTER TABLE Historico ADD CONSTRAINT DF_Historico_DataHora DEFAULT GETDATE() FOR DataHora;
        PRINT '? DEFAULT GETDATE() adicionado à coluna DataHora';
    END
END
GO
