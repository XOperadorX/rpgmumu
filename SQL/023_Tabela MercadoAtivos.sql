USE MumuDB;
GO

-- ============================================
-- ?? Tabela: MercadoAtivos
-- Armazena os ativos negociáveis no mercado RPG
-- ============================================
IF OBJECT_ID('dbo.MercadoAtivos', 'U') IS NOT NULL
    DROP TABLE dbo.MercadoAtivos;
GO

CREATE TABLE dbo.MercadoAtivos (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    Nome NVARCHAR(50) UNIQUE NOT NULL,           -- Nome do ativo (ex: Ouro, Prata)
    PrecoBase DECIMAL(18,2) NOT NULL,            -- Preço inicial
    VariacaoAtual DECIMAL(10,2) DEFAULT 0,       -- Última variação (positiva ou negativa)
    UltimaAtualizacao DATETIME DEFAULT GETDATE(),-- Quando o preço foi alterado
    Estoque INT DEFAULT 1000,                    -- Quantidade total disponível no mercado
    Ativo BIT DEFAULT 1,                         -- 1 = ativo, 0 = desativado
    HistoricoJSON NVARCHAR(MAX) NULL             -- Armazena histórico de preços em formato JSON
);
GO

-- ============================================
-- Inserindo ativos iniciais
-- ============================================
INSERT INTO dbo.MercadoAtivos (Nome, PrecoBase, VariacaoAtual, Estoque)
VALUES 
('Ouro', 50, 0, 1000),
('Prata', 30, 0, 1200),
('Bronze', 10, 0, 2000),
('Cristal', 100, 0, 800),
('Esmeralda', 150, 0, 600);
GO
