-- ===========================================
-- 1) Tabela Ativos
-- ===========================================
IF OBJECT_ID('dbo.Ativos', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Ativos (
        Nome NVARCHAR(50) PRIMARY KEY,
        Preco INT NOT NULL,
        UltimaVariacao INT NOT NULL
    );

    -- Inserindo dados usando EXEC para evitar erro de parser
    EXEC('INSERT INTO dbo.Ativos (Nome, Preco, UltimaVariacao) VALUES
        (''Ouro'', 50, 0),
        (''Prata'', 30, 0),
        (''Bronze'', 10, 0),
        (''Cristal'', 100, 0),
        (''Esmeralda'', 150, 0)');
END
ELSE
BEGIN
    PRINT 'Tabela Ativos já existe. Verificando colunas...';

    -- Adiciona coluna Preco se não existir
    IF COL_LENGTH('dbo.Ativos', 'Preco') IS NULL
    BEGIN
        ALTER TABLE dbo.Ativos ADD Preco INT NOT NULL DEFAULT 0;
        PRINT 'Coluna Preco adicionada.';
    END

    -- Adiciona coluna UltimaVariacao se não existir
    IF COL_LENGTH('dbo.Ativos', 'UltimaVariacao') IS NULL
    BEGIN
        ALTER TABLE dbo.Ativos ADD UltimaVariacao INT NOT NULL DEFAULT 0;
        PRINT 'Coluna UltimaVariacao adicionada.';
    END
END

-- ===========================================
-- 2) Tabela HistoricoTrades
-- ===========================================
IF OBJECT_ID('dbo.HistoricoTrades', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.HistoricoTrades (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        PlayerID INT NOT NULL,
        Ativo NVARCHAR(50) NOT NULL,
        Tipo NVARCHAR(10) NOT NULL, -- 'comprar' ou 'vender'
        Quantidade INT NOT NULL,
        PrecoUnitario INT NOT NULL,
        DataRegistro DATETIME DEFAULT GETDATE()
    );
END
ELSE
BEGIN
    PRINT 'Tabela HistoricoTrades já existe. Verificando colunas...';

    IF COL_LENGTH('dbo.HistoricoTrades', 'PlayerID') IS NULL
        ALTER TABLE dbo.HistoricoTrades ADD PlayerID INT NOT NULL DEFAULT 0;

    IF COL_LENGTH('dbo.HistoricoTrades', 'Ativo') IS NULL
        ALTER TABLE dbo.HistoricoTrades ADD Ativo NVARCHAR(50) NOT NULL DEFAULT '';

    IF COL_LENGTH('dbo.HistoricoTrades', 'Tipo') IS NULL
        ALTER TABLE dbo.HistoricoTrades ADD Tipo NVARCHAR(10) NOT NULL DEFAULT 'comprar';

    IF COL_LENGTH('dbo.HistoricoTrades', 'Quantidade') IS NULL
        ALTER TABLE dbo.HistoricoTrades ADD Quantidade INT NOT NULL DEFAULT 0;

    IF COL_LENGTH('dbo.HistoricoTrades', 'PrecoUnitario') IS NULL
        ALTER TABLE dbo.HistoricoTrades ADD PrecoUnitario INT NOT NULL DEFAULT 0;

    IF COL_LENGTH('dbo.HistoricoTrades', 'DataRegistro') IS NULL
        ALTER TABLE dbo.HistoricoTrades ADD DataRegistro DATETIME DEFAULT GETDATE();
END
