-- 1️⃣ Criar o banco de dados
CREATE DATABASE MumuDB;
GO

USE MumuDB;
GO

-- 2️⃣ Tabela de Players (jogadores)
CREATE TABLE Players (
    PlayerID INT IDENTITY(1,1) NOT NULL,
    Username VARCHAR(50) NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    MoedaMumu INT DEFAULT 0
    CONSTRAINT PK_Players PRIMARY KEY (PlayerID)
);
GO

-- Inserir um jogador de teste
INSERT INTO Players (Username, PasswordHash)
VALUES ('Teste', '123456'); -- Aqui você pode colocar hash real depois
GO

-- 3️⃣ Tabela Characters (personagens)
CREATE TABLE Characters (
    CharID INT IDENTITY(1,1) NOT NULL,
    PlayerID INT NOT NULL,
    Name VARCHAR(50) NOT NULL,
    Class VARCHAR(20) NOT NULL,
    Level INT DEFAULT 1,
    Exp INT DEFAULT 0,
    HP INT DEFAULT 100,
    CONSTRAINT PK_Characters PRIMARY KEY (CharID)
);
GO

-- Inserir um personagem de teste
INSERT INTO Characters (PlayerID, Name, Class, Level, Exp, HP)
VALUES (1, 'Hero', 'Knight', 1, 0, 100);
GO

-- 4️⃣ Tabela Items (inventário)
CREATE TABLE Items (
    ItemID INT IDENTITY(1,1) NOT NULL,
    CharID INT NOT NULL,
    Name VARCHAR(50) NOT NULL,
    CONSTRAINT PK_Items PRIMARY KEY (ItemID)
);
GO




-- 5️⃣ Tabela DungeonLog (histórico)
CREATE TABLE DungeonLog (
    LogID INT IDENTITY(1,1) NOT NULL,
    CharID INT NOT NULL,
    Message VARCHAR(MAX) NOT NULL,
    CreatedAt DATETIME DEFAULT GETDATE(),
    CONSTRAINT PK_DungeonLog PRIMARY KEY (LogID)
);
GO

-- Inserir log de teste
INSERT INTO DungeonLog (CharID, Message)
VALUES (1, 'Personagem entrou na dungeon de teste.');
GO


INSERT INTO Players (PlayerID, MoedaMumu)
SELECT PlayerID, 0 FROM Players
WHERE PlayerID NOT IN (SELECT PlayerID FROM Players);

SELECT dl.*, c.Name
FROM DungeonLog dl
JOIN Characters c ON dl.CharID = c.CharID
WHERE c.PlayerID = 1
ORDER BY dl.DataHora DESC;

-- Exemplo inicial
INSERT INTO Players (Username, PasswordHash) VALUES ('admin', '123456'); -- senha só exemplo
INSERT INTO Characters (PlayerID, Name, Class) VALUES (1, 'GuerreiroMumu', 'Knight');
