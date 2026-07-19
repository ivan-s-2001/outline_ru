import fs from "node:fs/promises";
import path from "node:path";

const root = process.cwd();
const translationUrl =
  process.env.RUSSIAN_TRANSLATION_URL ||
  "https://raw.githubusercontent.com/flameshikari/outline-ru/3a959876a49a064c4241cc1e3d69024484315af7/translation/ru.json";

async function read(relativePath) {
  return fs.readFile(path.join(root, relativePath), "utf8");
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

  if (!content.includes(search)) {
    throw new Error(
      `Не удалось обновить ${relativePath}: ожидаемый фрагмент не найден. ` +
        "Вероятно, структура новой версии Outline изменилась."
    );
  }

  await write(relativePath, content.replace(search, replacement));
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

  await ensureReplacement(
    ".env.sample",
    `DEFAULT_LANGUAGE=en_US`,
    `DEFAULT_LANGUAGE=ru_RU`,
    (content) => content.includes("DEFAULT_LANGUAGE=ru_RU")
  );
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
  const keys = Object.keys(parsed);

  if (keys.length < 500) {
    throw new Error(
      `Файл перевода выглядит неполным: найдено только ${keys.length} строк.`
    );
  }

  await write("shared/i18n/locales/ru_RU/translation.json", `${raw.trim()}\n`);
  console.log(`Русский перевод сохранён: ${keys.length} строк.`);
}

await connectRussianLocale();
await downloadTranslation();
console.log("Русская локализация Outline подключена.");
