import * as React from "react";
import type { ComponentProps } from "@shared/editor/types";
import Mention from "@shared/editor/nodes/Mention";

export default class StandaloneMention extends Mention {
  component = ({ node, isSelected }: ComponentProps) => {
    const type = String(node.attrs.type ?? "user");
    const modelId = String(node.attrs.modelId ?? "");
    const label = String(node.attrs.label ?? "");
    const className = `mention${
      isSelected ? " ProseMirror-selectednode" : ""
    }`;
    const attributes = {
      id: node.attrs.id ? String(node.attrs.id) : undefined,
      className,
      "data-type": type,
      "data-id": modelId,
      "data-actorid": node.attrs.actorId
        ? String(node.attrs.actorId)
        : undefined,
    };

    if (type === "document") {
      return (
        <a {...attributes} href={`/documents/${encodeURIComponent(modelId)}`}>
          {label}
        </a>
      );
    }
    if (type === "collection") {
      return (
        <a {...attributes} href={`/collections/${encodeURIComponent(modelId)}`}>
          {label}
        </a>
      );
    }

    return (
      <span {...attributes}>{type === "user" ? `@${label}` : label}</span>
    );
  };
}
