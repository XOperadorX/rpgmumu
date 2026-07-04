/****** Criação das colunas, caso ainda não existam ******/
IF COL_LENGTH('dbo.Characters', 'NextLevelExp') IS NULL
BEGIN
    ALTER TABLE [MumuDB].[dbo].[Characters]
    ADD 
        [NextLevelExp] INT NULL,
        [Attack] INT NULL,
        [Defense] INT NULL,
        [Magic] INT NULL,
        [Resistance] INT NULL,
        [Dexterity] INT NULL,
        [Initiative] INT NULL,
        [CritChance] DECIMAL(5,2) NULL;
END
GO

/****** Atualiza o campo Próximo Nível com progressão exponencial ******/
UPDATE [MumuDB].[dbo].[Characters]
SET [NextLevelExp] = FLOOR(1000 * POWER([Level], 1.5))
WHERE [NextLevelExp] IS NULL 
   OR [NextLevelExp] <> FLOOR(1000 * POWER([Level], 1.5));
GO

/****** Define valores iniciais (se estiverem nulos) ******/
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


/****** SISTEMA DE PROGRESSÃO DE ATRIBUTOS POR CLASSE ******/
-- Para cada personagem, aumenta os atributos com base na classe e no nível atual

UPDATE [MumuDB].[dbo].[Characters]
SET 
    [Attack] = 
        CASE [Class]
            WHEN 'Warrior' THEN 10 + ([Level] * 3)
            WHEN 'Mage' THEN 5 + ([Level] * 1)
            WHEN 'Archer' THEN 7 + ([Level] * 2)
            WHEN 'Rogue' THEN 8 + ([Level] * 2)
            ELSE [Attack]
        END,
    [Defense] = 
        CASE [Class]
            WHEN 'Warrior' THEN 10 + ([Level] * 3)
            WHEN 'Mage' THEN 5 + ([Level] * 1)
            WHEN 'Archer' THEN 6 + ([Level] * 2)
            WHEN 'Rogue' THEN 5 + ([Level] * 1)
            ELSE [Defense]
        END,
    [Magic] = 
        CASE [Class]
            WHEN 'Mage' THEN 15 + ([Level] * 4)
            WHEN 'Warrior' THEN 3 + ([Level] * 1)
            WHEN 'Archer' THEN 5 + ([Level] * 2)
            WHEN 'Rogue' THEN 4 + ([Level] * 2)
            ELSE [Magic]
        END,
    [Dexterity] = 
        CASE [Class]
            WHEN 'Archer' THEN 10 + ([Level] * 3)
            WHEN 'Rogue' THEN 12 + ([Level] * 3)
            WHEN 'Warrior' THEN 5 + ([Level] * 1)
            WHEN 'Mage' THEN 6 + ([Level] * 1)
            ELSE [Dexterity]
        END,
    [Initiative] = 
        CASE [Class]
            WHEN 'Rogue' THEN 8 + ([Level] * 3)
            WHEN 'Archer' THEN 7 + ([Level] * 2)
            WHEN 'Warrior' THEN 5 + ([Level] * 1)
            WHEN 'Mage' THEN 4 + ([Level] * 1)
            ELSE [Initiative]
        END,
    [CritChance] = 
        CASE [Class]
            WHEN 'Archer' THEN 10 + ([Level] * 0.5)
            WHEN 'Rogue' THEN 12 + ([Level] * 0.7)
            WHEN 'Mage' THEN 5 + ([Level] * 0.3)
            WHEN 'Warrior' THEN 4 + ([Level] * 0.2)
            ELSE [CritChance]
        END,
    [Resistance] = 
        CASE [Class]
            WHEN 'Mage' THEN 8 + ([Level] * 2)
            WHEN 'Warrior' THEN 10 + ([Level] * 3)
            WHEN 'Archer' THEN 7 + ([Level] * 2)
            WHEN 'Rogue' THEN 6 + ([Level] * 2)
            ELSE [Resistance]
        END;
GO


/****** Consulta com todos os atributos visíveis ******/
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
