CREATE TABLE dbo.Frutas (
    FrutaID INT IDENTITY(1,1) PRIMARY KEY,
    Nome NVARCHAR(100) NOT NULL,
    TempoCrescimento INT NOT NULL,          -- Minutos até colher
    PrecoSemente DECIMAL(10,2) NOT NULL,    -- Quanto custa a semente
    PrecoVendaSemente DECIMAL(10,2) NOT NULL, -- Venda da semente (normalmente menor)
    PrecoFruta DECIMAL(10,2) NOT NULL,      -- Preço base da fruta para compra
    PrecoVendaFruta DECIMAL(10,2) NOT NULL, -- Venda da fruta (valor maior)
    PrecoTrocaFruta DECIMAL(10,2) NULL,     -- Valor de troca da fruta
    PrecoTrocaSemente DECIMAL(10,2) NULL,   -- Valor de troca da semente
    Raridade TINYINT DEFAULT 1,             -- 1 = comum, 2 = rara, 3 = épica
    DataCriacao DATETIME DEFAULT GETDATE()
);
