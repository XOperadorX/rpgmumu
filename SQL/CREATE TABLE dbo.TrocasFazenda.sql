CREATE TABLE dbo.TrocasFazenda (
    TrocaID INT IDENTITY(1,1) PRIMARY KEY,
    PlayerID INT NOT NULL,
    TipoOrigem NVARCHAR(20),  -- 'Semente' ou 'Fruta'
    FrutaOrigemID INT,
    QuantidadeOrigem INT,
    TipoDestino NVARCHAR(20), -- 'Semente' ou 'Fruta'
    FrutaDestinoID INT,
    QuantidadeDestino INT,
    DataTroca DATETIME DEFAULT GETDATE()
);
