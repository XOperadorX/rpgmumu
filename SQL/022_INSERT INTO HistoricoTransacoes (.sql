INSERT INTO HistoricoTransacoes (CompradorID, VendedorID, ItemID, Quantidade, PrecoMoedaMumu, Tipo)
VALUES
((SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador2'),
 (SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador1'),
 1, 1, 50.0, 'COMPRA'),

((SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador3'),
 (SELECT TOP 1 PlayerID FROM Players WHERE Username='Jogador2'),
 4, 1, 120.0, 'COMPRA');
