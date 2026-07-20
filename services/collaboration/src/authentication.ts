import type { Extension, onAuthenticatePayload } from "@hocuspocus/server";
import { jwtVerify } from "jose";
import type { RowDataPacket } from "mysql2";
import { config } from "./config.js";
import { parseDocumentId, pool } from "./database.js";
import type { CollaborationContext, CollaborationUser } from "./types.js";

export class AuthenticationExtension implements Extension {
  async onAuthenticate({
    token,
    documentName,
    connection,
  }: onAuthenticatePayload): Promise<CollaborationContext> {
    if (!token) throw new Error("Authentication required");
    const documentId = parseDocumentId(documentName);
    const { payload } = await jwtVerify(token, config.jwtSecret, {
      issuer: "outline-php",
      audience: "outline-collaboration",
      algorithms: ["HS256"],
    });

    const user: CollaborationUser = {
      id: Number(payload.sub),
      workspaceId: Number(payload.workspace),
      documentId: Number(payload.document),
      name: String(payload.name ?? ""),
      color: String(payload.color ?? "#6B7280"),
      canRead: payload.canRead === true,
      canUpdate: payload.canUpdate === true,
    };

    if (!user.id || user.documentId !== documentId || !user.canRead) {
      throw new Error("Authorization required");
    }

    const [rows] = await pool.query<RowDataPacket[]>(
      "SELECT id FROM documents WHERE id=? AND workspace_id=? AND deleted_at IS NULL LIMIT 1",
      [documentId, user.workspaceId]
    );
    if (rows.length === 0) throw new Error("Document not found");

    if (!user.canUpdate) connection.readOnly = true;
    return { user };
  }
}
