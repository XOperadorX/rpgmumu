-- Cria o banco
CREATE DATABASE MumuDB;
GO
USE MumuDB;
GO

-- Jogadores
CREATE TABLE Players (
    PlayerID INT IDENTITY PRIMARY KEY,
    Username NVARCHAR(50) UNIQUE NOT NULL,
    PasswordHash NVARCHAR(255) NOT NULL,
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- Personagens
CREATE TABLE Characters (
    CharID INT IDENTITY PRIMARY KEY,
    PlayerID INT FOREIGN KEY REFERENCES Players(PlayerID),
    Name NVARCHAR(50) NOT NULL,
    Class NVARCHAR(20) NOT NULL,
    Level INT DEFAULT 1,
    Exp INT DEFAULT 0
);

-- Itens
CREATE TABLE Items (
    ItemID INT IDENTITY PRIMARY KEY,
    CharID INT FOREIGN KEY REFERENCES Characters(CharID),
    Name NVARCHAR(50) NOT NULL,
    Rarity NVARCHAR(20),
    Power INT
);

-- Exemplo inicial
INSERT INTO Players (Username, PasswordHash) VALUES ('admin', '123456'); -- senha só exemplo
INSERT INTO Characters (PlayerID, Name, Class) VALUES (1, 'GuerreiroMumu', 'Knight');
