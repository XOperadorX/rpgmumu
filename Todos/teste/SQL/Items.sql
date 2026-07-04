USE [MumuDB]
GO

/****** Object:  Table [dbo].[Items]    Script Date: 21/09/2025 23:50:07 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[Items](
	[ItemID] [int] IDENTITY(1,1) NOT NULL,
	[CharID] [int] NULL,
	[Name] [nvarchar](50) NOT NULL,
	[Rarity] [nvarchar](20) NULL,
	[Power] [int] NULL,
PRIMARY KEY CLUSTERED 
(
	[ItemID] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[Items]  WITH CHECK ADD FOREIGN KEY([CharID])
REFERENCES [dbo].[Characters] ([CharID])
GO


