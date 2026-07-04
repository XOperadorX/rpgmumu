USE [MumuDB]
GO

/****** Object:  Table [dbo].[Characters]    Script Date: 21/09/2025 23:48:42 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[Characters](
	[CharID] [int] IDENTITY(1,1) NOT NULL,
	[PlayerID] [int] NULL,
	[Name] [nvarchar](50) NOT NULL,
	[Class] [nvarchar](20) NOT NULL,
	[Level] [int] NULL DEFAULT ((1)),
	[Exp] [int] NULL DEFAULT ((0)),
	[HP] [int] NULL CONSTRAINT [DF_Characters_HP]  DEFAULT ((100)),
	[MaxHP] [int] NULL CONSTRAINT [DF_Characters_MaxHP]  DEFAULT ((100)),
PRIMARY KEY CLUSTERED 
(
	[CharID] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[Characters]  WITH CHECK ADD FOREIGN KEY([PlayerID])
REFERENCES [dbo].[Players2] ([PlayerID])
GO


