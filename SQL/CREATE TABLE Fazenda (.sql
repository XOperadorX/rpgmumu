CREATE TABLE Fazenda (
    PlantioID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT DEFAULT 1,
    PlantadoEm DATETIME NOT NULL,
    Colhido BIT DEFAULT 0,
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);
