INSERT INTO [MumuDB].[dbo].[Items] 
([CharID], [Nome], [PlayerID], [Quantidade], [Descricao], [DataAdquirido], [UsadoPor], [PodeUsar], [PodeMarcarLixo], [PodeEnviarArmazem], [PodeSoltar], [Raridade], [Valor])
VALUES
(101, 'Espada de Bronze', 201, 1, 'Uma espada básica de bronze.', GETDATE(), NULL, 1, 1, 1, 1, 1, 50),
(102, 'Poção de Vida', 202, 5, 'Recupera 50 de HP.', GETDATE(), NULL, 1, 1, 1, 1, 1, 10),
(103, 'Armadura de Ferro', 203, 1, 'Armadura resistente de ferro.', GETDATE(), NULL, 1, 1, 1, 1, 2, 150),
(104, 'Elixir de Mana', 204, 3, 'Recupera 30 de MP.', GETDATE(), NULL, 1, 1, 1, 1, 1, 20),
(105, 'Arco de Madeira', 205, 1, 'Arco simples de madeira.', GETDATE(), NULL, 1, 1, 1, 1, 1, 40),
(106, 'Anel do Poder', 206, 1, 'Aumenta a força em 10%.', GETDATE(), NULL, 1, 1, 1, 0, 3, 500),
(107, 'Botas da Velocidade', 207, 1, 'Aumenta a velocidade em 15%.', GETDATE(), NULL, 1, 1, 1, 0, 3, 400),
(108, 'Cajado de Fogo', 208, 1, 'Permite lançar magias de fogo.', GETDATE(), NULL, 1, 1, 1, 0, 4, 800),
(109, 'Escudo de Aço', 209, 1, 'Protege contra ataques físicos.', GETDATE(), NULL, 1, 1, 1, 1, 2, 200),
(110, 'Poção de Invisibilidade', 210, 2, 'Fica invisível por 30 segundos.', GETDATE(), NULL, 1, 1, 1, 0, 4, 1000);
