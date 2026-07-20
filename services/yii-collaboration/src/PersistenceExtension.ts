import { randomUUID } from "node:crypto";
import type {
  Extension,
  onLoadDocumentPayload,
  onStoreDocumentPayload,
} from "@hocuspocus/server";
import type { PoolConnection } from "mysql2/promise";
import { Node } from "prosemirror-model";
import { prosemirrorToYDoc, yDocToProsemirrorJSON } from "y-prosemirror";
import * as Y from "yjs";
import { schema } from "@server/editor/index.ts";
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

function collectUserMentions(value: unknown): Map<string, string> {
  const mentions = new Map<string, string>();

  const walk = (current: unknown, path = "0") => {
    if (!current || typeof current !== "object") {
      return;
    }

    if (Array.isArray(current)) {
      current.forEach((child, index) => walk(child, `${path}.${index}`));
      return;
    }

    const node = current as Record<string, unknown>;
    const attrs =
      node.attrs && typeof node.attrs === "object"
        ? (node.attrs as Record<string, unknown>)
        : undefined;
    if (node.type === "mention" && attrs?.type === "user") {
      const userId = typeof attrs.modelId === "string" ? attrs.modelId : "";
      const rawMentionId = typeof attrs.id === "string" ? attrs.id : "";
      if (/^[0-9a-fA-F-]{36}$/.test(userId)) {
        const mentionId = rawMentionId || `${path}:${userId}`;
        mentions.set(mentionId, userId);
      }
    }

    Object.entries(node).forEach(([key, child]) => walk(child, `${path}.${key}`));
  };

  walk(value);
  return mentions;
}

async function storeMentionNotifications(
  connection: PoolConnection,
  options: {
    workspaceId: string;
    documentId: string;
    actorId: string;
    previous: object | undefined;
    current: object;
  }
): Promise<void> {
  const previous = collectUserMentions(options.previous);
  const current = collectUserMentions(options.current);

  for (const [mentionId, recipientId] of current) {
    if (previous.has(mentionId) || recipientId === options.actorId) {
      continue;
    }

    const [recipients] = await connection.query<Array<{ id: string }>>(
      `SELECT id
       FROM users
       WHERE id = ? AND workspace_id = ? AND status = 'active'
       LIMIT 1`,
      [recipientId, options.workspaceId]
    );
    if (!recipients[0]) {
      continue;
    }

    const uniqueKey = `mention:${options.documentId}:${mentionId}:${recipientId}`;
    await connection.execute(
      `INSERT IGNORE INTO notifications
        (id, workspace_id, user_id, actor_id, type, document_id,
         comment_id, unique_key, data, read_at, archived_at, created_at)
       VALUES (?, ?, ?, ?, 'document_mention', ?, NULL, ?, ?, NULL, NULL,
               CURRENT_TIMESTAMP(6))`,
      [
        randomUUID(),
        options.workspaceId,
        recipientId,
        options.actorId,
        options.documentId,
        uniqueKey,
        JSON.stringify({ mentionId }),
      ]
    );
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
        Array<{
          title: string;
          revision_number: number;
          content_json: unknown;
        }>
      >(
        `SELECT title, revision_number, content_json
         FROM documents
         WHERE id = ? AND workspace_id = ? AND deleted_at IS NULL
         FOR UPDATE`,
        [documentId, user.workspaceId]
      );
      const stored = documents[0];
      if (!stored) {
        throw new Error("Document not found while storing collaboration state");
      }

      const previousContent = parseStoredContent(stored.content_json);
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

      await storeMentionNotifications(connection, {
        workspaceId: user.workspaceId,
        documentId,
        actorId: user.id,
        previous: previousContent,
        current: json,
      });

      await connection.commit();
    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
  }
}
