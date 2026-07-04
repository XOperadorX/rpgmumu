USE [MumuDB]
GO

/****** Object:  Table [dbo].[Players]    Script Date: 22/09/2025 19:43:06 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

-- Criar tabela Players
CREATE TABLE [dbo].[Players](
    [PlayerID]     INT IDENTITY(1,1) NOT NULL,
    [Username]     VARCHAR(50) NOT NULL,
    [PasswordHash] VARCHAR(255) NOT NULL,
    [MoedaMumu]    INT NULL DEFAULT ((0)),
    [CreatedAt]    DATETIME NOT NULL DEFAULT GETDATE(),   -- Data/hora de criação
    [UpdatedAt]    DATETIME NOT NULL DEFAULT GETDATE(),   -- Data/hora de atualização
 CONSTRAINT [PK_Players] PRIMARY KEY CLUSTERED 
(
    [PlayerID] ASC
) WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, 
       IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) 
) ON [PRIMARY]
GO

SET ANSI_PADDING OFF
GO

-- Trigger para atualizar UpdatedAt automaticamente
CREATE TRIGGER trg_UpdatePlayer
ON [dbo].[Players]
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE [dbo].[Players]
    SET UpdatedAt = GETDATE()
    FROM inserted i
    WHERE [dbo].[Players].PlayerID = i.PlayerID;
END
GO
