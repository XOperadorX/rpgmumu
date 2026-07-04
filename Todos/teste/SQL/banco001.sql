-- Criação da tabela BankAccounts
CREATE TABLE [dbo].[BankAccounts](
    [AccountID] INT IDENTITY(1,1) PRIMARY KEY,
    [PlayerID] INT NOT NULL UNIQUE,
    [Corrente] DECIMAL(18,2) DEFAULT 0,
    [Poupanca] DECIMAL(18,2) DEFAULT 0,
    [Pix] DECIMAL(18,2) DEFAULT 0,
    [Real] DECIMAL(18,2) DEFAULT 0,
    [LastInterest] DATETIME NULL,
    [LastUpdate] DATETIME DEFAULT GETDATE()
);

-- Inserção ou atualização (Upsert) de registro bancário
DECLARE @PlayerID INT = 1; -- substitua pelo PlayerID real
DECLARE @Corrente DECIMAL(18,2) = 100;
DECLARE @Poupanca DECIMAL(18,2) = 50;
DECLARE @Pix DECIMAL(18,2) = 0;
DECLARE @Real DECIMAL(18,2) = 0;

IF EXISTS(SELECT 1 FROM BankAccounts WHERE PlayerID = @PlayerID)
BEGIN
    UPDATE BankAccounts
    SET Corrente = @Corrente,
        Poupanca = @Poupanca,
        Pix = @Pix,
        Real = @Real,
        LastUpdate = GETDATE()
    WHERE PlayerID = @PlayerID;
END
ELSE
BEGIN
    INSERT INTO BankAccounts (PlayerID, Corrente, Poupanca, Pix, Real, LastUpdate)
    VALUES (@PlayerID, @Corrente, @Poupanca, @Pix, @Real, GETDATE());
END
