-- Delete schema from StudiGPT, if KI-Quiz exist
DELETE FROM schema_version
WHERE domain = 'StudiGPT'
  AND EXISTS (
    SELECT 1
    FROM schema_version AS sv
    WHERE sv.domain = 'KI-Quiz'
);