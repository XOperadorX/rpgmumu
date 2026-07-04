USE MumuDB;
GO

-- Adiciona a coluna PlayerID, se não existir
IF COL_LENGTH('Historico', 'PlayerID') IS NULL
BEGIN
    ALTER TABLE Historico ADD PlayerID INT NULL;
    PRINT '? Coluna PlayerID adicionada.';
END

-- Adiciona a coluna Acao, se não existir
IF COL_LENGTH('Historico', 'Acao') IS NULL
BEGIN
    ALTER TABLE Historico ADD Acao NVARCHAR(50) NULL;
    PRINT '? Coluna Acao adicionada.';
END

-- Adiciona a coluna Item, se não existir
IF COL_LENGTH('Historico', 'Item') IS NULL
BEGIN
    ALTER TABLE Historico ADD Item NVARCHAR(100) NULL;
    PRINT '? Coluna Item adicionada.';
END

-- Adiciona a coluna DataHora (para registro automático da data)
IF COL_LENGTH('Historico', 'DataHora') IS NULL
BEGIN
    ALTER TABLE Historico ADD DataHora DATETIME DEFAULT GETDATE();
    PRINT '? Coluna DataHora adicionada.';
END
GO
