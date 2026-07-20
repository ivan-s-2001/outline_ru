import { HocuspocusProvider } from "@hocuspocus/provider";
import { IndexeddbPersistence } from "y-indexeddb";
import * as Y from "yjs";
import i18n from "i18next";
import * as React from "react";
import { useEffect, useMemo, useRef, useState } from "react";
import { initReactI18next } from "react-i18next";
import { render } from "react-dom";
import { ThemeProvider } from "styled-components";
import History from "@shared/editor/extensions/History";
import Comment from "@shared/editor/marks/Comment";
import Mention from "@shared/editor/nodes/Mention";
import { richExtensions } from "@shared/editor/nodes";
import light from "@shared/styles/theme";
import Editor from "~/editor";
import type { Editor as EditorHandle } from "~/editor";
import MultiplayerExtension from "~/editor/extensions/Multiplayer";
import StandaloneMention from "./StandaloneMention";

void i18n.use(initReactI18next).init({
  lng: "ru",
  fallbackLng: "ru",
  keySeparator: false,
  returnNull: false,
  interpolation: { escapeValue: false },
  resources: {
    ru: {
      translation: {
        "Type '/' to insert, or start writing…":
          "Введите / для вставки блока или начните писать…",
        "Type '/' to browse options": "Введите / для выбора блока",
        Untitled: "Без названия",
        Bold: "Жирный",
        Italic: "Курсив",
        Underline: "Подчёркивание",
        Strikethrough: "Зачёркивание",
        Link: "Ссылка",
        "Code block": "Блок кода",
        "Bulleted list": "Маркированный список",
        "Numbered list": "Нумерованный список",
        "Todo list": "Список задач",
        Quote: "Цитата",
        Table: "Таблица",
        Image: "Изображение",
        Attachment: "Вложение",
        Notice: "Примечание",
        Math: "Формула",
      },
    },
  },
  react: { useSuspense: false },
});

type AuthUser = {
  id: string;
  name: string;
  color: string;
};

type AuthResponse = {
  data: AuthUser;
};

type CollaborationTokenResponse = {
  data: {
    token: string;
    expiresIn: number;
    canUpdate?: boolean;
  };
};

type AttachmentResponse = {
  data: {
    id: string;
    name: string;
    contentType: string;
    size: number;
    url: string;
  };
};

type MentionableUser = {
  id: string;
  name: string;
  login: string;
};

type MentionableUsersResponse = {
  data: MentionableUser[];
};

type EditorMountProps = {
  element: HTMLElement;
};

const coreExtensions = [
  StandaloneMention,
  Comment,
  ...richExtensions.filter((extension) => extension !== Mention),
];

function csrfHeaders(): Record<string, string> {
  const csrfParam = document
    .querySelector<HTMLMetaElement>('meta[name="csrf-param"]')
    ?.getAttribute("content");
  const csrfToken = document
    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
    ?.getAttribute("content");

  if (!csrfToken) {
    return {};
  }

  return {
    "X-CSRF-Token": csrfToken,
    ...(csrfParam ? { [csrfParam]: csrfToken } : {}),
  };
}

async function getJson<T>(url: string): Promise<T> {
  const response = await fetch(url, {
    credentials: "same-origin",
    headers: { Accept: "application/json" },
  });
  if (!response.ok) {
    const payload = await response.json().catch(() => undefined);
    throw new Error(payload?.message ?? `HTTP ${response.status}`);
  }
  return response.json() as Promise<T>;
}

async function postJson<T>(url: string, body: object): Promise<T> {
  const response = await fetch(url, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...csrfHeaders(),
    },
    body: JSON.stringify(body),
  });

  if (!response.ok) {
    const payload = await response.json().catch(() => undefined);
    throw new Error(payload?.message ?? `HTTP ${response.status}`);
  }

  return response.json() as Promise<T>;
}

async function uploadEditorFile(
  value: File | string,
  documentId: string,
  options?: { id?: string; onProgress?: (fractionComplete: number) => void }
): Promise<string> {
  if (!(value instanceof File)) {
    throw new Error("Загрузка файла по внешнему URL пока не поддерживается");
  }

  const body = new FormData();
  body.append("file", value, value.name);
  options?.onProgress?.(0);

  const response = await fetch(`/documents/${documentId}/attachments`, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      ...csrfHeaders(),
    },
    body,
  });
  const payload = (await response.json().catch(() => undefined)) as
    | AttachmentResponse
    | { message?: string }
    | undefined;
  if (!response.ok || !payload || !("data" in payload)) {
    throw new Error(
      payload && "message" in payload
        ? payload.message
        : `HTTP ${response.status}`
    );
  }

  options?.onProgress?.(1);
  return payload.data.url;
}

function parseContent(element: HTMLElement) {
  try {
    const value = JSON.parse(element.dataset.contentJson ?? "");
    if (value && value.type === "doc") {
      return value;
    }
  } catch (_error) {
    // The Bootstrap fallback remains visible if the stored projection is invalid.
  }

  return { type: "doc", content: [{ type: "paragraph" }] };
}

function setStatus(element: HTMLElement, text: string, variant: string) {
  const status = document.querySelector<HTMLElement>("[data-editor-status]");
  if (!status) {
    return;
  }
  status.textContent = text;
  status.className = `badge text-bg-${variant}`;
  element.dataset.status = text;
}

function hideFallback() {
  document.querySelectorAll<HTMLElement>("[data-editor-fallback]").forEach(
    (fallback) => {
      fallback.hidden = true;
    }
  );
}

function syncForm(editor: EditorHandle | null) {
  if (!editor) {
    return;
  }

  const jsonInput = document.querySelector<HTMLInputElement>(
    "[data-editor-json-input]"
  );
  if (jsonInput) {
    jsonInput.value = JSON.stringify(editor.value(false));
  }

  const textInput = document.querySelector<HTMLTextAreaElement>(
    ".document-text-fallback"
  );
  if (textInput) {
    textInput.value = editor.getPlainText();
  }
}

function insertUserMention(
  editor: EditorHandle | null,
  target: MentionableUser,
  actorId: string
) {
  if (!editor?.schema.nodes.mention || editor.view.state.selection.empty === false) {
    editor?.view.focus();
  }
  if (!editor?.schema.nodes.mention) {
    return;
  }

  const node = editor.schema.nodes.mention.create({
    type: "user",
    label: target.name,
    modelId: target.id,
    actorId,
    id: crypto.randomUUID(),
  });
  const transaction = editor.view.state.tr
    .replaceSelectionWith(node)
    .insertText(" ")
    .scrollIntoView();
  editor.view.dispatch(transaction);
  editor.view.focus();
  syncForm(editor);
}

function MentionPicker({
  users,
  actorId,
  editorRef,
}: {
  users: MentionableUser[];
  actorId: string;
  editorRef: React.RefObject<EditorHandle>;
}) {
  const [selectedId, setSelectedId] = useState("");
  const selected = users.find((candidate) => candidate.id === selectedId);

  return (
    <div className="d-flex flex-wrap align-items-center gap-2 border-bottom pb-3 mb-3">
      <label className="small fw-semibold mb-0" htmlFor="yii-mention-user">
        Упомянуть
      </label>
      <select
        id="yii-mention-user"
        className="form-select form-select-sm"
        style={{ maxWidth: 320 }}
        value={selectedId}
        onChange={(event) => setSelectedId(event.target.value)}
      >
        <option value="">Выберите пользователя</option>
        {users.map((candidate) => (
          <option key={candidate.id} value={candidate.id}>
            {candidate.name} (@{candidate.login})
          </option>
        ))}
      </select>
      <button
        type="button"
        className="btn btn-outline-secondary btn-sm"
        disabled={!selected}
        onClick={() => {
          if (!selected) {
            return;
          }
          insertUserMention(editorRef.current, selected, actorId);
          setSelectedId("");
        }}
      >
        Вставить @
      </button>
    </div>
  );
}

function LocalEditor({ element }: EditorMountProps) {
  const editorRef = useRef<EditorHandle>(null);
  const readOnly = element.dataset.editorMode === "read";
  const defaultValue = useMemo(() => parseContent(element), [element]);

  useEffect(() => {
    setStatus(
      element,
      readOnly ? "Просмотр" : "Локальный редактор",
      "secondary"
    );
  }, [element, readOnly]);

  return (
    <Editor
      ref={editorRef}
      defaultValue={defaultValue}
      extensions={coreExtensions}
      embeds={[]}
      readOnly={readOnly}
      canUpdate={!readOnly}
      canComment={!readOnly}
      placeholder="Введите / для вставки блока или начните писать…"
      onInit={() => {
        hideFallback();
        syncForm(editorRef.current);
      }}
      onChange={() => syncForm(editorRef.current)}
      onClickLink={(href, event) => {
        if (event?.metaKey || event?.ctrlKey || event?.shiftKey) {
          return;
        }
        if (href.startsWith("/")) {
          event?.preventDefault();
          window.location.assign(href);
        }
      }}
    />
  );
}

function CollaborativeEditor({ element }: EditorMountProps) {
  const editorRef = useRef<EditorHandle>(null);
  const documentId = element.dataset.documentId ?? "";
  const readOnlyPage = element.dataset.editorMode === "read";
  const [user, setUser] = useState<AuthUser>();
  const [mentionableUsers, setMentionableUsers] = useState<MentionableUser[]>([]);
  const [provider, setProvider] = useState<HocuspocusProvider>();
  const [document] = useState(() => new Y.Doc());
  const [canUpdate, setCanUpdate] = useState(!readOnlyPage);

  useEffect(() => {
    let disposed = false;
    let localProvider: IndexeddbPersistence | undefined;
    let remoteProvider: HocuspocusProvider | undefined;

    const connect = async () => {
      setStatus(element, "Подключение…", "warning");

      const [auth, collaboration, mentionables] = await Promise.all([
        postJson<AuthResponse>("/api/auth.info", {}),
        postJson<CollaborationTokenResponse>(
          "/api/auth.collaborationToken",
          { documentId }
        ),
        getJson<MentionableUsersResponse>("/mention/users"),
      ]);
      if (disposed) {
        return;
      }

      setUser(auth.data);
      setMentionableUsers(mentionables.data);
      setCanUpdate(!readOnlyPage && collaboration.data.canUpdate !== false);
      localProvider = new IndexeddbPersistence(`document.${documentId}`, document);

      const websocketUrl =
        element.dataset.collaborationUrl ??
        `${window.location.protocol === "https:" ? "wss:" : "ws:"}//${
          window.location.host
        }/collaboration`;

      remoteProvider = new HocuspocusProvider({
        url: websocketUrl,
        name: `document.${documentId}`,
        document,
        token: collaboration.data.token,
      });
      remoteProvider.on("status", ({ status }) => {
        setStatus(
          element,
          status === "connected"
            ? "Совместное редактирование"
            : "Переподключение…",
          status === "connected" ? "success" : "warning"
        );
      });
      remoteProvider.on("synced", () => {
        hideFallback();
        syncForm(editorRef.current);
      });
      remoteProvider.on("authenticationFailed", () => {
        setStatus(element, "Нет доступа", "danger");
      });
      remoteProvider.on("close", () => {
        if (!disposed) {
          setStatus(element, "Связь потеряна", "warning");
        }
      });

      setProvider(remoteProvider);
    };

    void connect().catch(() => {
      if (!disposed) {
        setStatus(element, "Локальный режим", "secondary");
      }
    });

    return () => {
      disposed = true;
      remoteProvider?.destroy();
      void localProvider?.destroy();
      document.destroy();
    };
  }, [document, documentId, element, readOnlyPage]);

  const extensions = useMemo(() => {
    if (!provider || !user) {
      return undefined;
    }

    return [
      ...coreExtensions.filter(
        (extension) => extension !== History && !(extension instanceof History)
      ),
      new MultiplayerExtension({
        user,
        provider,
        document,
      }),
    ];
  }, [document, provider, user]);

  if (!provider || !user || !extensions) {
    return null;
  }

  return (
    <>
      {!readOnlyPage && canUpdate && (
        <MentionPicker
          users={mentionableUsers}
          actorId={user.id}
          editorRef={editorRef}
        />
      )}
      <Editor
        ref={editorRef}
        defaultValue={parseContent(element)}
        extensions={extensions}
        embeds={[]}
        readOnly={readOnlyPage || !canUpdate}
        canUpdate={canUpdate}
        canComment
        userId={user.id}
        uploadFile={(file, options) =>
          uploadEditorFile(file, documentId, options)
        }
        placeholder="Введите / для вставки блока или начните писать…"
        onInit={() => {
          hideFallback();
          syncForm(editorRef.current);
        }}
        onChange={() => syncForm(editorRef.current)}
        onClickLink={(href, event) => {
          if (event?.metaKey || event?.ctrlKey || event?.shiftKey) {
            return;
          }
          if (href.startsWith("/")) {
            event?.preventDefault();
            window.location.assign(href);
          }
        }}
      />
    </>
  );
}

function EditorMount({ element }: EditorMountProps) {
  const hasPersistedDocument = Boolean(element.dataset.documentId);
  const collaborationEnabled = element.dataset.collaboration !== "false";

  return (
    <ThemeProvider theme={light}>
      {hasPersistedDocument && collaborationEnabled ? (
        <CollaborativeEditor element={element} />
      ) : (
        <LocalEditor element={element} />
      )}
    </ThemeProvider>
  );
}

document.querySelectorAll<HTMLElement>("#outline-rich-editor").forEach(
  (element) => {
    render(<EditorMount element={element} />, element);
  }
);
