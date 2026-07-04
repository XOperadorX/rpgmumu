CREATE TABLE Sementes (
    SementeID INT IDENTITY PRIMARY KEY,
    PlayerID INT NOT NULL,
    FrutaID INT NOT NULL,
    Quantidade INT DEFAULT 0,
    FOREIGN KEY (PlayerID) REFERENCES Players(PlayerID),
    FOREIGN KEY (FrutaID) REFERENCES Frutas(FrutaID)
);
