USE [MumuDB]
GO

/****** Object:  Table [dbo].[DungeonLog]    Script Date: 21/09/2025 23:49:39 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

CREATE TABLE [dbo].[DungeonLog](
	[LogID] [int] IDENTITY(1,1) NOT NULL,
	[CharID] [int] NOT NULL,
	[Message] [varchar](max) NOT NULL,
	[CreatedAt] [datetime] NULL,
	[DataHora] [datetime] NULL,
	[XP] [int] NULL,
	[Item] [nvarchar](100) NULL,
	[DataRegistro] [datetime] NULL,
 CONSTRAINT [PK_DungeonLog] PRIMARY KEY CLUSTERED 
(
	[LogID] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO

ALTER TABLE [dbo].[DungeonLog] ADD  DEFAULT (getdate()) FOR [CreatedAt]
GO

ALTER TABLE [dbo].[DungeonLog] ADD  DEFAULT (getdate()) FOR [DataHora]
GO

ALTER TABLE [dbo].[DungeonLog] ADD  DEFAULT ((0)) FOR [XP]
GO

ALTER TABLE [dbo].[DungeonLog] ADD  DEFAULT (getdate()) FOR [DataRegistro]
GO


