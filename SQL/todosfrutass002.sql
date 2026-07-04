-- Tabela Players
CREATE TABLE Players (
    PlayerID INT IDENTITY PRIMARY KEY,
    Username NVARCHAR(50) NOT NULL,
    PasswordHash NVARCHAR(255) NOT NULL,
    MoedaMumu INT DEFAULT 100,
    Nivel INT DEFAULT 1,
    XP INT DEFAULT 0,
    CreatedAt DATETIME DEFAULT GETDATE(),
    UpdatedAt DATETIME DEFAULT GETDATE()
);

-- Tabela Frutas
CREATE TABLE Frutas (
    FrutaID INT IDENTITY PRIMARY KEY,
    Nome NVARCHAR(50) NOT NULL,
    TempoCrescimento INT NOT NULL -- minutos
);

-- Tabela Fazenda
CREATE TABLE Fazenda (
    PlantioID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT NOT NULL,
    PlantadoEm DATETIME DEFAULT GETDATE(),
    Colhido BIT DEFAULT 0,
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);

-- Tabela Inventário de Frutas
CREATE TABLE InventarioFrutas (
    InventarioID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT DEFAULT 0,
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);

-- Tabela Sementes
CREATE TABLE Sementes (
    SementeID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT DEFAULT 0,
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);

-- Tabela Mercado
CREATE TABLE MercadoFrutas (
    MercadoID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT NOT NULL,
    Preco INT NOT NULL,
    Tipo NVARCHAR(10) DEFAULT 'venda', -- 'venda' ou 'compra'
    CriadoEm DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);
