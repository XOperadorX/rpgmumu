SELECT dl.*, c.Name
FROM DungeonLog dl
JOIN Characters c ON dl.CharID = c.CharID
WHERE c.PlayerID = ?
ORDER BY dl.DataHora DESC;
