// Table types for Kysely. Only tables that exist in migrations belong here.
export interface SchemaHealthTable {
  id: number;
  checked_at: Date;
}

export interface Database {
  schema_health: SchemaHealthTable;
}
