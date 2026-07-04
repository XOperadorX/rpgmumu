CREATE TABLE [MumuDB].[dbo].[Currency] (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    PrecoAtual DECIMAL(18,2) NOT NULL,
    LastUpdate DATETIME DEFAULT GETDATE()
);

-- Inserir preço inicial
INSERT INTO [MumuDB].[dbo].[Currency] (PrecoAtual) VALUES (10.00);
