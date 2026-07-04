CREATE TABLE Frutas (
    FrutaID INT IDENTITY PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL,
    TempoCrescimento INT NOT NULL, -- em minutos
    PrecoCompra INT NOT NULL,
    PrecoVenda INT NOT NULL
);
