export type CollaborationUser = {
  id: number;
  workspaceId: number;
  documentId: number;
  name: string;
  color: string;
  canRead: boolean;
  canUpdate: boolean;
};

export type CollaborationContext = {
  user?: CollaborationUser;
};
