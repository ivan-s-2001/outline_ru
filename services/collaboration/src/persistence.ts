import type {
  Extension,
  onLoadDocumentPayload,
  onStoreDocumentPayload,
} from "@hocuspocus/server";
import type { RowDataPacket } from "mysql2";
import * as Y from "yjs";
import { yDocToProsemirrorJSON } from "y-prosemirror";
import { parseDocumentId, pool } from "./database.js";
import type { CollaborationContext } from "./types.js";

const textFromNode = (node: unknown): string => {
  if (!node || typeof node !== "object") return "";
  const value = node as { text?: unknown; content?: unknown[] };
  const own = typeof value.text === "string" ? value.text : "";
  const children = Array.isArray(value.content)
    ? value.content.map(textFromNode).filter(Boolean).join(" ")
    : "";
  return [own, children].filter(Boolean).join(" ").replace(/\s+/g, " ").trim();
};

export class PersistenceExtension implements Extension {
  async onLoadDocument({ documentName }: onLoadDocumentPayload): Promise<Y.Doc> {
    const documentId = parseDocumentId(documentName);
    const [rows] = await pool.query<RowDataPacket[]>(
      "SELECT collaboration_state FROM documents WHERE id=? LIMIT 1",
      [documentId]
    );
    if (rows.length === 0) throw new Error("Document not found");

    const ydoc = new Y.Doc();
    const state = rows[0].collaboration_state as Buffer | null;
    if (state?.length) Y.applyUpdate(ydoc, new Uint8Array(state));
    return ydoc;
  }

  async onStoreDocument({ document, documentName, context }: onStoreDocumentPayload): Promise<void> {
    const documentId = parseDocumentId(documentName);
    const typedContext = context as CollaborationContext;
    const state = Buffer.from(Y.encodeStateAsUpdate(document));
    const content = yDocToProsemirrorJSON(document, "default");
    const contentJson = JSON.stringify(content);
    const contentText = textFromNode(content);
    const userId = typedContext.user?.id ?? null;

    const connection = await pool.getConnection();
    try {
      await connection.beginTransaction();
      await connection.execute(
        "UPDATE documents SET collaboration_state=?, content_json=?, content_text=?, version=version+1, updated_by=COALESCE(?, updated_by), updated_at=UTC_TIMESTAMP() WHERE id=?",
        [state, contentJson, contentText, userId, documentId]
      );
      await connection.execute(
        "INSERT INTO document_revisions (document_id, version, title, content_json, content_text, collaboration_state, created_by, created_at) SELECT id, version, title, content_json, content_text, collaboration_state, COALESCE(?, updated_by), UTC_TIMESTAMP() FROM documents WHERE id=?",
        [userId, documentId]
      );
      await connection.commit();
    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
  }
}
