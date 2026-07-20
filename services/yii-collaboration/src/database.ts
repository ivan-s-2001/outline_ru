import mysql from "mysql2/promise";
import { config } from "./config.js";

export const pool = mysql.createPool({
  host: config.database.host,
  port: config.database.port,
  database: config.database.database,
  user: config.database.user,
  password: config.database.password,
  charset: config.database.charset,
  connectionLimit: config.database.connectionLimit,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0,
  namedPlaceholders: false,
  supportBigNumbers: true,
  dateStrings: true,
});

export type DocumentRow = mysql.RowDataPacket & {
  id: string;
  workspace_id: string;
  title: string;
  content_json: string | object | null;
  content_text: string | null;
  yjs_state: Buffer | null;
  revision_number: number;
};

export function parseDocumentId(documentName: string): string {
  const match = /^document\.([0-9a-fA-F-]{36})$/.exec(documentName);
  if (!match) {
    throw new Error("Invalid collaborative document name");
  }
  return match[1];
}

export async function findDocument(
  documentId: string,
  workspaceId?: string
): Promise<DocumentRow | undefined> {
  const parameters: string[] = [documentId];
  let sql = `
    SELECT id, workspace_id, title, content_json, content_text, yjs_state, revision_number
    FROM documents
    WHERE id = ? AND deleted_at IS NULL AND archived_at IS NULL
  `;
  if (workspaceId) {
    sql += " AND workspace_id = ?";
    parameters.push(workspaceId);
  }
  sql += " LIMIT 1";

  const [rows] = await pool.query<DocumentRow[]>(sql, parameters);
  return rows[0];
}
