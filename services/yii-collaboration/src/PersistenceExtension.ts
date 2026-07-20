import { randomUUID } from "node:crypto";
import type {
  Extension,
  onLoadDocumentPayload,
  onStoreDocumentPayload,
} from "@hocuspocus/server";
import { Node } from "prosemirror-model";
import { prosemirrorToYDoc, yDocToProsemirrorJSON } from "y-prosemirror";
import * as Y from "yjs";
import { schema } from "@server/editor/index";
import { findDocument, parseDocumentId, pool } from "./database.js";
import type { CollaborationContext } from "./types.js";

function toPlainText(json: object): string {
  try {
    const node = Node.fromJSON(schema, json);
    return node.textBetween(0, node.content.size, "\n\n", " ").trim();
  } catch (_error) {
    return "";
  }
}

function parseStoredContent(value: unknown): object | undefined {
  if (value && typeof value === "object") {
    return value as object;
  }
  if (typeof value !== "string" || value.trim() === "") {
    return undefined;
  }

  try {
    const parsed = JSON.parse(value);
    return parsed && typeof parsed === "object" ? parsed : undefined;
  } catch (_error) {
    return undefined;
  }
}

export class PersistenceExtension implements Extension {
  async onLoadDocument({
    documentName,
  }: onLoadDocumentPayload): Promise<Y.Doc | undefined> {
    const documentId = parseDocumentId(documentName);
    const stored = await findDocument(documentId);
    if (!stored) {
      throw new Error("Document not found");
    }

    if (stored.yjs_state?.length) {
      const document = new Y.Doc();
      Y.applyUpdate(document, new Uint8Array(stored.yjs_state));
      return document;
    }

    const content = parseStoredContent(stored.content_json);
    if (content) {
      try {
        const node = Node.fromJSON(schema, content);
        return prosemirrorToYDoc(node, "default");
      } catch (_error) {
        // An invalid projection must not prevent opening the document; the
        // collaboration state becomes the new authoritative representation.
      }
    }

    return new Y.Doc();
  }

  async onStoreDocument({
    document,
    documentName,
    context,
  }: onStoreDocumentPayload): Promise<void> {
    const documentId = parseDocumentId(documentName);
    const collaboration = context as CollaborationContext;
    const user = collaboration.user;
    if (!user?.id || !user.canUpdate) {
      return;
    }

    const state = Buffer.from(Y.encodeStateAsUpdate(document));
    const json = yDocToProsemirrorJSON(document, "default") as object;
    const text = toPlainText(json);
    const connection = await pool.getConnection();

    try {
      await connection.beginTransaction();
      const [documents] = await connection.query<
        Array<{ title: string; revision_number: number }>
      >(
        `SELECT title, revision_number
         FROM documents
         WHERE id = ? AND workspace_id = ? AND deleted_at IS NULL
         FOR UPDATE`,
        [documentId, user.workspaceId]
      );
      const stored = documents[0];
      if (!stored) {
        throw new Error("Document not found while storing collaboration state");
      }

      const revisionNumber = Number(stored.revision_number) + 1;
      const serialized = JSON.stringify(json);
      await connection.execute(
        `UPDATE documents
         SET yjs_state = ?, content_json = ?, content_text = ?,
             revision_number = ?, updated_by_id = ?, updated_at = CURRENT_TIMESTAMP(6)
         WHERE id = ? AND workspace_id = ?`,
        [
          state,
          serialized,
          text,
          revisionNumber,
          user.id,
          documentId,
          user.workspaceId,
        ]
      );

      await connection.execute(
        `INSERT INTO revisions
          (id, document_id, user_id, title, content_json, content_text,
           yjs_state, revision_number, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(6))`,
        [
          randomUUID(),
          documentId,
          user.id,
          stored.title,
          serialized,
          text,
          state,
          revisionNumber,
        ]
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
