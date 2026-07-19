import type { AnyExtensionClass } from "../../lib/types";

/**
 * Fork-specific editor blocks.
 *
 * Keep custom nodes registered here instead of editing the main extension list
 * for every new block. This reduces conflicts when updating Outline.
 */
export const customBlockExtensions: AnyExtensionClass[] = [];
