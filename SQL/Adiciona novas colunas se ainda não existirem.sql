USE MumuDB;
GO

-- 🟢 Adiciona novas colunas se ainda não existirem
IF COL_LENGTH('dbo.Frutas', 'PrecoVendaSemente') IS NULL
    ALTER TABLE dbo.Frutas ADD PrecoVendaSemente DECIMAL(10,2) NULL;

IF COL_LENGTH('dbo.Frutas', 'PrecoFruta') IS NULL
    ALTER TABLE dbo.Frutas ADD PrecoFruta DECIMAL(10,2) NULL;

IF COL_LENGTH('dbo.Frutas', 'PrecoTrocaFruta') IS NULL
    ALTER TABLE dbo.Frutas ADD PrecoTrocaFruta DECIMAL(10,2) NULL;

IF COL_LENGTH('dbo.Frutas', 'PrecoTrocaSemente') IS NULL
    ALTER TABLE dbo.Frutas ADD PrecoTrocaSemente DECIMAL(10,2) NULL;

IF COL_LENGTH('dbo.Frutas', 'Raridade') IS NULL
    ALTER TABLE dbo.Frutas ADD Raridade TINYINT DEFAULT 1;

-- 🟡 Atualiza os valores para manter lógica econômica coerente
UPDATE dbo.Frutas
SET
    PrecoVendaSemente = ISNULL(PrecoSemente * 0.5, 0),
    PrecoFruta = ISNULL(PrecoSemente * 2, 0),
    PrecoVenda = ISNULL(PrecoSemente * 2.4, 0),  -- mantém compatibilidade com o campo antigo
    PrecoTrocaFruta = ISNULL(PrecoSemente * 1.6, 0),
    PrecoTrocaSemente = ISNULL(PrecoSemente * 0.7, 0);

-- 🟣 Ajusta frutas com raridade baseada no preço da semente
UPDATE dbo.Frutas
SET Raridade =
    CASE
        WHEN PrecoSemente < 15 THEN 1  -- comum
        WHEN PrecoSemente BETWEEN 15 AND 25 THEN 2  -- rara
        ELSE 3  -- épica
    END;
GO
