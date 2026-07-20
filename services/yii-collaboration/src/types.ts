export type CollaborationUser = {
  id: string;
  workspaceId: string;
  documentId: string;
  name: string;
  color: string;
  canRead: boolean;
  canUpdate: boolean;
};

export type CollaborationContext = {
  user: CollaborationUser;
};
