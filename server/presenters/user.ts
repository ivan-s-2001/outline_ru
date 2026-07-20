import type {
  NotificationSettings,
  UserPreferences,
  UserRole,
} from "@shared/types";
import { splitUserName } from "@shared/utils/userName";
import env from "@server/env";
import type { User } from "@server/models";

type Options = {
  includeDetails?: boolean;
  includeEmail?: boolean;
};

type UserPresentation = {
  id: string;
  name: string;
  lastName: string | null;
  firstName: string;
  middleName: string | null;
  avatarUrl: string | null | undefined;
  createdAt: Date;
  updatedAt: Date;
  deletedAt: Date | null;
  lastActiveAt: Date | null;
  color: string;
  role: UserRole;
  isSuspended: boolean;
  email?: string | null;
  language?: string;
  preferences?: UserPreferences | null;
  notificationSettings?: NotificationSettings;
  timezone?: string | null;
};

export default function presentUser(
  user: User,
  options: Options = {}
): UserPresentation {
  const { lastName, firstName, middleName } = splitUserName(user.name);
  const userData: UserPresentation = {
    id: user.id,
    name: user.name,
    lastName,
    firstName,
    middleName,
    avatarUrl: user.avatarUrl,
    color: user.color,
    role: user.role,
    isSuspended: user.isSuspended,
    createdAt: user.createdAt,
    updatedAt: user.updatedAt,
    deletedAt: user.deletedAt,
    lastActiveAt: user.lastActiveAt,
    timezone: user.timezone,
  };

  if (options.includeDetails) {
    userData.email = user.email;
    userData.language = user.language || env.DEFAULT_LANGUAGE;
    userData.preferences = user.preferences;
    userData.notificationSettings = user.notificationSettings;
  }

  if (options.includeEmail) {
    userData.email = user.email;
  }

  return userData;
}
