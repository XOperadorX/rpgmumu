CREATE TABLE dbo.HistoricoTroca (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    DePlayerID INT NOT NULL,
    ParaPlayerID INT NOT NULL,
    ItemID INT NOT NULL,
    Quantidade INT NOT NULL,
    DataRegistro DATETIME DEFAULT GETDATE()
);
