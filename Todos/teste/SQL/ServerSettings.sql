CREATE TABLE ServerSettings (
    ServerName NVARCHAR(100) NOT NULL,
    XPRate INT NOT NULL DEFAULT 1,
    DropRate INT NOT NULL DEFAULT 1,
    CurrencyName NVARCHAR(50) NOT NULL DEFAULT 'MoedaMumu',
    RegisterEnabled BIT NOT NULL DEFAULT 1
);
