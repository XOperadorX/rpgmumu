/****** Adiciona colunas extras ao personagem, se ainda não existirem ******/
IF COL_LENGTH('dbo.Characters', 'NextLevelExp') IS NULL
BEGIN
    ALTER TABLE [MumuDB].[dbo].[Characters]
    ADD 
        [NextLevelExp] INT NULL,           -- Experiência necessária para o próximo nível
        [Attack] INT NULL,                 -- Ataque
        [Defense] INT NULL,                -- Defesa
        [Magic] INT NULL,                  -- Magia
        [Resistance] INT NULL,             -- Resistência
        [Dexterity] INT NULL,              -- Destreza
        [Initiative] INT NULL,             -- Iniciativa
        [CritChance] DECIMAL(5,2) NULL;    -- Chance de acerto crítico (%)
END
GO

/****** Atualiza o campo Próximo Nível com fórmula exponencial ******/
UPDATE [MumuDB].[dbo].[Characters]
SET [NextLevelExp] = FLOOR(1000 * POWER([Level], 1.5))
WHERE [NextLevelExp] IS NULL 
   OR [NextLevelExp] <> FLOOR(1000 * POWER([Level], 1.5));
GO

/****** Atribui valores iniciais padrão (caso estejam nulos) ******/
UPDATE [MumuDB].[dbo].[Characters]
SET 
    [Attack] = ISNULL([Attack], 10),
    [Defense] = ISNULL([Defense], 10),
    [Magic] = ISNULL([Magic], 5),
    [Resistance] = ISNULL([Resistance], 5),
    [Dexterity] = ISNULL([Dexterity], 5),
    [Initiative] = ISNULL([Initiative], 5),
    [CritChance] = ISNULL([CritChance], 5.00);
GO

/****** Exibe tudo com nomes amigáveis ******/
SELECT TOP 1000 
      [CharID],
      [PlayerID],
      [Name],
      [Class],
      [Level],
      [Exp] AS [Experiência Atual],
      [NextLevelExp] AS [Próximo Nível (Exp Necessária)],
      ([NextLevelExp] - [Exp]) AS [Falta para Subir],
      [HP],
      [MaxHP],
      [Mana],
      [MaxMana],
      [Attack] AS [Ataque],
      [Defense] AS [Defesa],
      [Magic] AS [Magia],
      [Resistance] AS [Resistência],
      [Dexterity] AS [Destreza],
      [Initiative] AS [Iniciativa],
      [CritChance] AS [Chance Crítico %],
      [Power],
      [MaxPower],
      [LastRestore]
FROM [MumuDB].[dbo].[Characters]
ORDER BY [Level] DESC;
GO
