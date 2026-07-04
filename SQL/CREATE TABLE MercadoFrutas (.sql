CREATE TABLE MercadoFrutas (
    MercadoID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT NOT NULL,
    Preco INT NOT NULL,
    Tipo VARCHAR(10) NOT NULL DEFAULT 'venda', -- substitui ENUM
    CriadoEm DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID),
    CONSTRAINT CHK_Tipo CHECK (Tipo IN ('venda', 'compra')) -- valida os valores
);
