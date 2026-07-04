-- Tabela de Players já existe: Players(PlayerID, MoedaMumu, CarteiraJSON, etc.)

-- Preços atuais dos ativos
CREATE TABLE Ativos (
    Nome NVARCHAR(50) PRIMARY KEY,
    Preco INT NOT NULL,
    UltimaVariacao INT NOT NULL DEFAULT 0
);

-- Histórico de trades
CREATE TABLE HistoricoTrades (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    PlayerID INT NOT NULL,
    Ativo NVARCHAR(50) NOT NULL,
    Tipo NVARCHAR(10) NOT NULL, -- comprar ou vender
    Quantidade INT NOT NULL,
    PrecoUnitario INT NOT NULL,
    DataHora DATETIME DEFAULT GETDATE()
);
