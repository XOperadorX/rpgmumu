-- 1️⃣ Apaga a tabela antiga se existir
IF OBJECT_ID('dbo.Enemies', 'U') IS NOT NULL
    DROP TABLE dbo.Enemies;
GO

-- 2️⃣ Cria a nova tabela
CREATE TABLE [dbo].[Enemies](
    [EnemyID] INT IDENTITY(1,1) PRIMARY KEY,
    [Name] NVARCHAR(100) NOT NULL,
    [Level] INT NOT NULL,
    [HP] INT NOT NULL,
    [MaxHP] INT NOT NULL,
    [Mana] INT NOT NULL,
    [MaxMana] INT NOT NULL,
    [Attack] INT NOT NULL,
    [Defense] INT NOT NULL,
    [MagicAttack] INT NOT NULL,
    [MagicDefense] INT NOT NULL,
    [Speed] INT NOT NULL,
    [CritChance] FLOAT NOT NULL,
    [Element] NVARCHAR(50) NOT NULL,
    [XP] INT NOT NULL,
    [Loot] NVARCHAR(MAX),
    [DropRate] FLOAT,
    [SpecialSkill] NVARCHAR(100),
    [BehaviorType] NVARCHAR(50)
);
GO

-- 3️⃣ Insere 20 inimigos
INSERT INTO dbo.Enemies
(Name, Level, HP, MaxHP, Mana, MaxMana, Attack, Defense, MagicAttack, MagicDefense, Speed, CritChance, Element, XP, Loot, DropRate, SpecialSkill, BehaviorType)
VALUES
-- Inimigos comuns
('Goblin', 1, 50, 50, 10, 10, 8, 2, 0, 1, 5, 0.05, 'Terra', 20, 'Moeda,Poção', 0.5, 'Nenhum', 'Agressivo'),
('Slime', 1, 30, 30, 5, 5, 4, 1, 0, 0, 3, 0.02, 'Água', 10, 'Moeda', 0.7, 'Dividir-se', 'Passivo'),
('Lobo', 2, 70, 70, 0, 0, 12, 4, 0, 2, 6, 0.08, 'Terra', 30, 'Moeda,Pelego', 0.5, 'Investida', 'Agressivo'),
('Esqueleto', 2, 60, 60, 0, 0, 10, 3, 0, 2, 4, 0.07, 'Sombrio', 25, 'Osso', 0.6, 'Ataque Sombrio', 'Agressivo'),

-- Inimigos intermediários
('Orc', 3, 120, 120, 20, 20, 15, 5, 0, 3, 4, 0.1, 'Terra', 50, 'Espada,Moeda', 0.4, 'Berserk', 'Agressivo'),
('Mago das Sombras', 5, 80, 80, 50, 50, 5, 3, 20, 10, 6, 0.15, 'Sombrio', 100, 'Poção,Mana', 0.6, 'Bola de Fogo', 'Agressivo'),
('Aranha Gigante', 4, 90, 90, 0, 0, 12, 3, 0, 2, 7, 0.05, 'Veneno', 60, 'Veneno', 0.5, 'Enredar', 'Agressivo'),
('Troll', 5, 200, 200, 10, 10, 20, 10, 0, 5, 3, 0.12, 'Terra', 120, 'Moeda,Pedra', 0.4, 'Regenerar', 'Agressivo'),

-- Inimigos avançados
('Fada da Floresta', 6, 70, 70, 80, 80, 8, 4, 25, 15, 8, 0.15, 'Vento', 150, 'Poção,Mana', 0.6, 'Cura', 'Passivo'),
('Lobo Alfa', 7, 150, 150, 0, 0, 25, 8, 0, 5, 9, 0.2, 'Terra', 200, 'Pelego,Moeda', 0.5, 'Investida Forte', 'Agressivo'),
('Golem de Pedra', 8, 300, 300, 0, 0, 30, 25, 0, 10, 2, 0.1, 'Terra', 300, 'Pedra,Moeda', 0.4, 'Golpe Pesado', 'Agressivo'),
('Dragão Juvenil', 10, 400, 400, 100, 100, 35, 20, 30, 20, 7, 0.25, 'Fogo', 500, 'Escama,Poção', 0.6, 'Sopro de Fogo', 'Agressivo'),

-- Bosses e raros
('Dragão Ancião', 20, 2000, 2000, 500, 500, 80, 50, 100, 50, 10, 0.3, 'Fogo', 5000, 'Escama Lendária,Moeda', 0.9, 'Sopro de Fogo Massivo', 'Boss'),
('Rei Esqueleto', 15, 1200, 1200, 200, 200, 50, 30, 60, 40, 6, 0.25, 'Sombrio', 2500, 'Espada Sombria,Osso', 0.8, 'Maldição', 'Boss'),
('Fenix', 18, 1500, 1500, 300, 300, 60, 40, 80, 50, 12, 0.2, 'Fogo', 3500, 'Pluma,Poção', 0.7, 'Renascer', 'Boss'),
('Minotauro', 12, 1000, 1000, 50, 50, 70, 35, 20, 20, 5, 0.15, 'Terra', 1800, 'Chifre,Moeda', 0.6, 'Investida', 'Agressivo'),
('Gárgula', 14, 900, 900, 80, 80, 60, 40, 30, 30, 8, 0.18, 'Sombrio', 2000, 'Pedra,Poção', 0.7, 'Ataque Sombrio', 'Agressivo'),
('Vampiro', 16, 1100, 1100, 200, 200, 65, 30, 70, 40, 9, 0.2, 'Sombrio', 2800, 'Poção,Sangue', 0.6, 'Sucção de Vida', 'Agressivo'),
('Demônio Menor', 13, 800, 800, 150, 150, 55, 25, 50, 30, 7, 0.15, 'Fogo', 1600, 'Moeda,Poção', 0.6, 'Chamas', 'Agressivo');
GO
