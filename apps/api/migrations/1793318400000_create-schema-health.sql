-- Up Migration
-- Phase 1 proof table. It confirms that migrations run and roll back.
-- No business data is stored here.
CREATE TABLE schema_health (
  id integer PRIMARY KEY CHECK (id = 1),
  checked_at timestamptz NOT NULL DEFAULT now()
);

-- Down Migration
DROP TABLE schema_health;