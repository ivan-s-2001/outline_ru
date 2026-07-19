import { z } from "zod";
import { TeamValidation, UserValidation } from "@shared/validations";
import { isCompleteUserName } from "@shared/utils/userName";
import { BaseSchema } from "@server/routes/api/schema";

export const InstallationCreateSchema = BaseSchema.extend({
  body: z.object({
    /** Team name */
    teamName: z.string().min(1).max(TeamValidation.maxNameLength),
    /** User full name in "Фамилия Имя Отчество" order */
    userName: z
      .string()
      .trim()
      .min(1)
      .max(UserValidation.maxNameLength)
      .refine(isCompleteUserName, {
        error: "Пишите ФИО через пробел: фамилия имя отчество",
      }),
    /** User email */
    userEmail: z.email().max(UserValidation.maxEmailLength),
  }),
});

export type InstallationCreateSchemaReq = z.infer<
  typeof InstallationCreateSchema
>;
