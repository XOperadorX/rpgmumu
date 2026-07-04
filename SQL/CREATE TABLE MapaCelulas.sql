CREATE TABLE MapaCelulas (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    MapaID INT NOT NULL,
    X INT NOT NULL,
    Y INT NOT NULL,
    Tipo CHAR(1) NOT NULL  -- '.' = chão, '#' = parede, 'T' = árvore, etc.
);
