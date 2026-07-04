SELECT c.CharID, c.Name, p.Xpos, p.Ypos
FROM dbo.Characters c
JOIN CharacterPositions p ON c.CharID = p.CharID AND c.PlayerID = p.PlayerID
WHERE p.Xpos IS NOT NULL AND p.Ypos IS NOT NULL
