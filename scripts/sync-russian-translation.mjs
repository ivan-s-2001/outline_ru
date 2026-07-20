import fs from "node:fs/promises";
import path from "node:path";

const root = process.cwd();
const translationUrl =
  process.env.RUSSIAN_TRANSLATION_URL ||
  "https://raw.githubusercontent.com/flameshikari/outline-ru/3a959876a49a064c4241cc1e3d69024484315af7/translation/ru.json";

const translationOverrides = {
  "Open in split view": "Открыть в режиме разделения",
  "Close pane": "Закрыть панель",
  "Split pane": "Дополнительная панель",
  "Main pane": "Основная панель",
  "Sorry, the link could not be copied": "Не удалось скопировать ссылку",
};

async function read(relativePath) {
  return fs.readFile(path.join(root, relativePath), "utf8");
}

async function fileExists(relativePath) {
  try {
    await fs.access(path.join(root, relativePath));
    return true;
  } catch (error) {
    if (error?.code === "ENOENT") {
      return false;
    }
    throw error;
  }
}

async function write(relativePath, content) {
  const target = path.join(root, relativePath);
  await fs.mkdir(path.dirname(target), { recursive: true });
  await fs.writeFile(target, content, "utf8");
}

async function ensureReplacement(relativePath, search, replacement, readyCheck) {
  const content = await read(relativePath);

  if (readyCheck(content)) {
    return;
  }

  // Git checkouts on Windows may use CRLF while replacement fragments use LF.
  // Normalize only for matching, then preserve the file's original line endings.
  const usesCrlf = content.includes("\r\n");
  const normalizedContent = content.replace(/\r\n/g, "\n");
  const normalizedSearch = search.replace(/\r\n/g, "\n");
  const normalizedReplacement = replacement.replace(/\r\n/g, "\n");

  if (!normalizedContent.includes(normalizedSearch)) {
    throw new Error(
      `Не удалось обновить ${relativePath}: ожидаемый фрагмент не найден. ` +
        "Вероятно, структура новой версии Outline изменилась."
    );
  }

  const updated = normalizedContent.replace(
    normalizedSearch,
    normalizedReplacement
  );
  await write(relativePath, usesCrlf ? updated.replace(/\n/g, "\r\n") : updated);
}

async function connectRussianLocale() {
  await ensureReplacement(
    "shared/i18n/index.ts",
    `  {\n    label: "Español (Spanish)",\n    value: "es_ES",\n  },`,
    `  {\n    label: "Русский (Russian)",\n    value: "ru_RU",\n  },\n  {\n    label: "Español (Spanish)",\n    value: "es_ES",\n  },`,
    (content) => content.includes('value: "ru_RU"')
  );

  await ensureReplacement(
    "shared/utils/date.ts",
    `import { pl } from "date-fns/locale/pl";`,
    `import { pl } from "date-fns/locale/pl";\nimport { ru } from "date-fns/locale/ru";`,
    (content) => content.includes('date-fns/locale/ru"')
  );

  await ensureReplacement(
    "shared/utils/date.ts",
    `  pl_PL: pl,`,
    `  pl_PL: pl,\n  ru_RU: ru,`,
    (content) => content.includes("  ru_RU: ru,")
  );

  await ensureReplacement(
    "server/env.ts",
    `environment.DEFAULT_LANGUAGE ?? "en_US"`,
    `environment.DEFAULT_LANGUAGE ?? "ru_RU"`,
    (content) => content.includes('environment.DEFAULT_LANGUAGE ?? "ru_RU"')
  );

  for (const relativePath of [".env.sample", "docker.env.example"]) {
    if (!(await fileExists(relativePath))) {
      continue;
    }

    await ensureReplacement(
      relativePath,
      `DEFAULT_LANGUAGE=en_US`,
      `DEFAULT_LANGUAGE=ru_RU`,
      (content) => content.includes("DEFAULT_LANGUAGE=ru_RU")
    );
  }
}

function hasPluralTranslation(key, translation) {
  if (!key.endsWith("_plural")) {
    return false;
  }

  const base = key.slice(0, -"_plural".length);
  return [
    "_zero",
    "_one",
    "_two",
    "_few",
    "_many",
    "_other",
    "_0",
    "_1",
    "_2",
  ].some((suffix) => Object.hasOwn(translation, `${base}${suffix}`));
}

async function validateCoverage(translation) {
  const english = JSON.parse(
    await read("shared/i18n/locales/en_US/translation.json")
  );
  const missing = Object.keys(english).filter(
    (key) =>
      !Object.hasOwn(translation, key) && !hasPluralTranslation(key, translation)
  );

  if (missing.length > 0) {
    const preview = missing.slice(0, 20).map((key) => `- ${key}`).join("\n");
    throw new Error(
      `Русский перевод не покрывает ${missing.length} строк английского словаря:\n${preview}`
    );
  }
}

async function downloadTranslation() {
  console.log(`Загрузка русского перевода: ${translationUrl}`);
  const response = await fetch(translationUrl, {
    headers: { "User-Agent": "outline-ru-build" },
  });

  if (!response.ok) {
    throw new Error(
      `Не удалось загрузить русский перевод: HTTP ${response.status} ${response.statusText}`
    );
  }

  const raw = await response.text();
  const parsed = JSON.parse(raw);
  const translation = { ...parsed, ...translationOverrides };
  const keys = Object.keys(translation);

  if (keys.length < 500) {
    throw new Error(
      `Файл перевода выглядит неполным: найдено только ${keys.length} строк.`
    );
  }

  await validateCoverage(translation);
  await write(
    "shared/i18n/locales/ru_RU/translation.json",
    `${JSON.stringify(translation, null, 2)}\n`
  );
  console.log(`Русский перевод сохранён и проверен: ${keys.length} строк.`);
}

await connectRussianLocale();
await downloadTranslation();
console.log("Русская локализация Outline подключена.");
