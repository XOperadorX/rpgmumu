-- 1. Players
CREATE TABLE Players (
    PlayerID INT IDENTITY PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    MoedaMumu INT DEFAULT 0,
    XP INT DEFAULT 0,
    Nivel INT DEFAULT 1,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE()
);

-- 2. Frutas
CREATE TABLE Frutas (
    FrutaID INT IDENTITY PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL,
    TempoCrescimento INT NOT NULL, -- minutos
    PrecoCompra INT NOT NULL,
    PrecoVenda INT NOT NULL
);

INSERT INTO Frutas (Nome, TempoCrescimento, PrecoCompra, PrecoVenda)
VALUES
('Morango', 60, 5, 3),
('Mamão', 1440, 20, 15),
('Maracujá', 720, 10, 7),
('Abacaxi', 2160, 25, 18),
('Laranja', 2880, 15, 10),
('Limão', 2880, 12, 8),
('Banana', 1440, 10, 6),
('Acerola', 720, 8, 5),
('Goiaba', 2160, 18, 12),
('Jabuticaba', 4320, 30, 20);

-- 3. Fazenda (plantio)
CREATE TABLE Fazenda (
    PlantioID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT DEFAULT 1,
    PlantadoEm DATETIME DEFAULT GETDATE(),
    Colhido BIT DEFAULT 0,
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);

-- 4. InventarioFrutas
CREATE TABLE InventarioFrutas (
    InventarioID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT DEFAULT 0,
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);

-- 5. MercadoFrutas
CREATE TABLE MercadoFrutas (
    MercadoID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT NOT NULL,
    Preco INT NOT NULL,
    Tipo VARCHAR(10) NOT NULL DEFAULT 'venda',
    CriadoEm DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID),
    CONSTRAINT CHK_Tipo CHECK (Tipo IN ('venda','compra'))
);
