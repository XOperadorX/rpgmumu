-- 1. Deleta histórico dos personagens não-admin
DELETE H
FROM [MumuDB].[dbo].[Historico] H
INNER JOIN [MumuDB].[dbo].[Characters] C
    ON H.CharID = C.CharID
WHERE C.PlayerID NOT IN (
    SELECT PlayerID
    FROM [MumuDB].[dbo].[Players]
    WHERE Role = 'admin'
);

-- 2. Deleta os personagens não-admin
DELETE FROM [MumuDB].[dbo].[Characters]
WHERE PlayerID NOT IN (
    SELECT PlayerID
    FROM [MumuDB].[dbo].[Players]
    WHERE Role = 'admin'
);

-- 3. Reinicia o contador de CharID
DBCC CHECKIDENT ('[MumuDB].[dbo].[Characters]', RESEED, 0);
