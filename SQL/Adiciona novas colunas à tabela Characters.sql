-- Adiciona novas colunas à tabela Characters
ALTER TABLE [MumuDB].[dbo].[Characters]
ADD 
    [NextLevelExp] INT NULL,           -- Próximo Nível
    [Attack] INT NULL,                 -- Ataque
    [Defense] INT NULL,                -- Defesa
    [Magic] INT NULL,                  -- Magia
    [Resistance] INT NULL,             -- Resistência
    [Dexterity] INT NULL,              -- Destreza
    [Initiative] INT NULL,             -- Iniciativa
    [CritChance] DECIMAL(5,2) NULL;    -- Chance de acerto crítico (%)
