-- =============================================
-- Relatório de inventário por jogador
-- =============================================

-- 1?? Criar tabela temporária de valores dos itens
IF OBJECT_ID('tempdb..#ItemValores') IS NOT NULL
    DROP TABLE #ItemValores;

CREATE TABLE #ItemValores (
    ItemID INT PRIMARY KEY,
    Valor INT
);

-- 2?? Inserir valores dos itens (adicione mais conforme necessário)
INSERT INTO #ItemValores (ItemID, Valor)
VALUES
(101, 50),   -- Espada
(102, 70),   -- Escudo
(103, 20),   -- Poção
(104, 100);  -- Armadura

-- 3?? Consulta principal: concatena itens e calcula total
SELECT
    p.PlayerID,
    p.Nome AS NomeJogador,
    -- Concatena os itens: Nome xQuantidade (Valor)
    STUFF((
        SELECT ', ' + i2.Nome + ' x' + CAST(i2.Quantidade AS VARCHAR(10)) 
               + ' (' + CAST(iv.Valor AS VARCHAR(10)) + ')'
        FROM [MumuDB].[dbo].[Items] i2
        INNER JOIN #ItemValores iv
            ON i2.ItemID = iv.ItemID
        WHERE i2.PlayerID = p.PlayerID
        FOR XML PATH(''), TYPE
    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS Itens,
    -- Soma total de valor dos itens
    SUM(i.Quantidade * iv.Valor) AS TotalValorItens
FROM [MumuDB].[dbo].[Players] p
INNER JOIN [MumuDB].[dbo].[Items] i
    ON i.PlayerID = p.PlayerID
INNER JOIN #ItemValores iv
    ON i.ItemID = iv.ItemID
GROUP BY p.PlayerID, p.Nome
ORDER BY TotalValorItens DESC;
