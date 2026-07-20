import type { Extension, onAuthenticatePayload } from "@hocuspocus/server";
import { jwtVerify } from "jose";
import { config } from "./config.js";
import { findDocument, parseDocumentId } from "./database.js";
import type { CollaborationContext, CollaborationUser } from "./types.js";

export class AuthenticationExtension implements Extension {
  async onAuthenticate({
    token,
    documentName,
    connection,
  }: onAuthenticatePayload): Promise<CollaborationContext> {
    if (!token) {
      throw new Error("Authentication required");
    }

    const documentId = parseDocumentId(documentName);
    const { payload } = await jwtVerify(token, config.jwtSecret, {
      issuer: "outline-yii-api",
      audience: "outline-collaboration",
      algorithms: ["HS256"],
    });

    const user: CollaborationUser = {
      id: String(payload.sub ?? ""),
      workspaceId: String(payload.workspace ?? ""),
      documentId: String(payload.document ?? ""),
      name: String(payload.name ?? ""),
      color: String(payload.color ?? "#6B7280"),
      canRead: payload.canRead === true,
      canUpdate: payload.canUpdate === true,
    };

    if (
      !user.id ||
      !user.workspaceId ||
      user.documentId !== documentId ||
      !user.canRead
    ) {
      throw new Error("Authorization required");
    }

    const document = await findDocument(documentId, user.workspaceId);
    if (!document) {
      throw new Error("Document not found");
    }

    if (!user.canUpdate) {
      connection.readOnly = true;
    }

    return { user };
  }
}
