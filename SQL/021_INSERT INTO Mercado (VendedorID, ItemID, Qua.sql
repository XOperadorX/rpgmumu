INSERT INTO Mercado (VendedorID, ItemID, Quantidade, PrecoMoedaMumu)
VALUES
((SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador1'), 1, 2, 50.0),   -- Esmeralda
((SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador1'), 2, 1, 80.0),   -- Cristal
((SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador2'), 4, 1, 120.0),  -- Prata
((SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador3'), 8, 1, 200.0);  -- Espada Antiga
