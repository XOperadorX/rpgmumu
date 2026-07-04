INSERT INTO Players (Username, PasswordHash, MoedaMumu, CarteiraJSON, CreatedAt, UpdatedAt)
VALUES
('Jogador1', 'hash123', 500, '{"1":5,"2":3,"3":10}', GETDATE(), GETDATE()),
('Jogador2', 'hash123', 800, '{"4":2,"5":1,"6":5}', GETDATE(), GETDATE()),
('Jogador3', 'hash123', 200, '{"7":4,"8":2}', GETDATE(), GETDATE());
