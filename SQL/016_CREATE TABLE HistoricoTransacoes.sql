CREATE TABLE HistoricoTransacoes (
    HistoricoID INT IDENTITY(1,1) PRIMARY KEY,
    CompradorID INT NULL,
    VendedorID INT NULL,
    ItemID INT NOT NULL,
    Quantidade INT NOT NULL,
    PrecoMoedaMumu DECIMAL(18,2),
    Tipo NVARCHAR(10) NOT NULL, -- 'COMPRA' ou 'VENDA'
    DataTransacao DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (CompradorID) REFERENCES Players(PlayerID),
    FOREIGN KEY (VendedorID) REFERENCES Players(PlayerID),
    FOREIGN KEY (ItemID) REFERENCES Itens(ItemID)
);
