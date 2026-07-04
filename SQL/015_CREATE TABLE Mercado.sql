CREATE TABLE Mercado (
    MercadoID INT IDENTITY(1,1) PRIMARY KEY,
    VendedorID INT NOT NULL,
    ItemID INT NOT NULL,
    Quantidade INT NOT NULL,
    PrecoMoedaMumu DECIMAL(18,2) NOT NULL,
    CriadoEm DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (VendedorID) REFERENCES Players(PlayerID),
    FOREIGN KEY (ItemID) REFERENCES Itens(ItemID)
);
