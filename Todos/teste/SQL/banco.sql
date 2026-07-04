-- Tabela de jogadores
CREATE TABLE Players (
    PlayerID INT PRIMARY KEY IDENTITY,
    PlayerName NVARCHAR(50),
    MoedaMumu INT DEFAULT 0
);

-- Tabela de banco
CREATE TABLE BankAccounts (
    AccountID INT PRIMARY KEY IDENTITY,
    PlayerID INT FOREIGN KEY REFERENCES Players(PlayerID),
    Corrente INT DEFAULT 0,
    Poupanca INT DEFAULT 0,
    Pix INT DEFAULT 0,
    Real INT DEFAULT 0,
    LastInterest DATETIME DEFAULT GETDATE()
);
