export type UserNameParts = {
  lastName: string | null;
  firstName: string;
  middleName: string | null;
};

/**
 * Splits a Russian full name entered as "Фамилия Имя Отчество".
 * Additional words are preserved as part of the middle name.
 */
export function splitUserName(value: string): UserNameParts {
  const parts = value.trim().split(/\s+/).filter(Boolean);

  if (parts.length === 0) {
    return {
      lastName: null,
      firstName: "",
      middleName: null,
    };
  }

  if (parts.length === 1) {
    return {
      lastName: null,
      firstName: parts[0],
      middleName: null,
    };
  }

  return {
    lastName: parts[0],
    firstName: parts[1],
    middleName: parts.length > 2 ? parts.slice(2).join(" ") : null,
  };
}

/** Builds the compatibility display name in Russian FIO order. */
export function formatUserName({
  lastName,
  firstName,
  middleName,
}: UserNameParts): string {
  return [lastName, firstName, middleName]
    .map((part) => part?.trim())
    .filter(Boolean)
    .join(" ");
}

export function normalizeUserName(value: string): string {
  return formatUserName(splitUserName(value));
}

export function isCompleteUserName(value: string): boolean {
  const { lastName, firstName, middleName } = splitUserName(value);
  return Boolean(lastName && firstName && middleName);
}
