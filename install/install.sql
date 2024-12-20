-- Migrate from StudiGPT plugin, if any
UPDATE plugins SET enabled = 'no' WHERE pluginclassname = 'StudiGPT';
REPLACE INTO schema_version (domain, branch, version) SELECT 'KI-Quiz' as domain, branch, version FROM schema_version WHERE domain = 'StudiGPT';
