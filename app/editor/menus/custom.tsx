import type { TFunction } from "i18next";
import type { RefObject } from "react";
import type { MenuItem } from "@shared/editor/types";

/**
 * Items contributed to the editor slash menu by fork-specific blocks.
 *
 * The item name must match a command exposed by the corresponding node.
 * Add separators here when they are needed for visual grouping.
 */
export default function customBlockMenuItems(
  _t: TFunction,
  _documentRef: RefObject<HTMLDivElement>
): MenuItem[] {
  return [];
}
