USE MumuDB;
GO

-- Cria a tabela se ainda não existir
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='EnemyPositions' AND xtype='U')
BEGIN
    CREATE TABLE dbo.EnemyPositions (
        EnemyID INT PRIMARY KEY,
        Xpos INT NOT NULL,
        Ypos INT NOT NULL
    );
END;
GO
