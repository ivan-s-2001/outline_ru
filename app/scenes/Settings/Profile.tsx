import { observer } from "mobx-react";
import { ProfileIcon } from "outline-icons";
import * as React from "react";
import { Trans, useTranslation } from "react-i18next";
import { toast } from "sonner";
import { errToString } from "@shared/utils/error";
import { formatUserName, splitUserName } from "@shared/utils/userName";
import Button from "~/components/Button";
import Heading from "~/components/Heading";
import Input from "~/components/Input";
import Scene from "~/components/Scene";
import Text from "~/components/Text";
import { UserChangeEmailDialog } from "~/components/UserDialogs";
import env from "~/env";
import useCurrentUser from "~/hooks/useCurrentUser";
import useStores from "~/hooks/useStores";
import { UserValidation } from "@shared/validations";
import ImageInput from "./components/ImageInput";
import SettingRow from "./components/SettingRow";

const Profile = () => {
  const user = useCurrentUser();
  const { dialogs } = useStores();
  const form = React.useRef<HTMLFormElement>(null);
  const initialName = React.useMemo(() => splitUserName(user.name), [user.name]);
  const [lastName, setLastName] = React.useState(initialName.lastName ?? "");
  const [firstName, setFirstName] = React.useState(initialName.firstName);
  const [middleName, setMiddleName] = React.useState(
    initialName.middleName ?? ""
  );
  const { t } = useTranslation();

  const handleSubmit = async (ev: React.SyntheticEvent) => {
    ev.preventDefault();

    try {
      await user.save({
        name: formatUserName({
          lastName,
          firstName,
          middleName,
        }),
      });
      toast.success(t("Profile saved"));
    } catch (err) {
      toast.error(errToString(err));
    }
  };

  const handleChangeEmail = () => {
    dialogs.openModal({
      title: t("Change email"),
      content: (
        <UserChangeEmailDialog user={user} onSubmit={dialogs.closeAllModals} />
      ),
    });
  };

  const handleAvatarChange = async (avatarUrl: string) => {
    await user.save({ avatarUrl });
    toast.success(t("Profile picture updated"));
  };

  const handleAvatarError = (error: string | null | undefined) => {
    toast.error(error || t("Unable to upload new profile picture"));
  };

  const isValid = form.current?.checkValidity();
  const { isSaving } = user;

  return (
    <Scene title={t("Profile")} icon={<ProfileIcon />}>
      <Heading>{t("Profile")}</Heading>
      <Text as="p" type="secondary">
        <Trans>Manage how you appear to other members of the workspace.</Trans>
      </Text>

      <form onSubmit={handleSubmit} ref={form}>
        <SettingRow
          label={t("Photo")}
          name="avatarUrl"
          description={t("Choose a photo or image to represent yourself.")}
        >
          <ImageInput
            alt={t("Profile picture")}
            onSuccess={handleAvatarChange}
            onError={handleAvatarError}
            model={user}
          />
        </SettingRow>
        <SettingRow
          label={t("Фамилия")}
          name="lastName"
          description={t("ФИО отображается в профиле, документах и графике.")}
        >
          <Input
            id="lastName"
            autoComplete="family-name"
            value={lastName}
            onChange={(ev) => setLastName(ev.target.value)}
            maxLength={UserValidation.maxNameLength}
            showCharacterCount
            required
          />
        </SettingRow>
        <SettingRow label={t("Имя")} name="firstName">
          <Input
            id="firstName"
            autoComplete="given-name"
            value={firstName}
            onChange={(ev) => setFirstName(ev.target.value)}
            maxLength={UserValidation.maxNameLength}
            showCharacterCount
            required
          />
        </SettingRow>
        <SettingRow
          border={env.EMAIL_ENABLED}
          label={t("Отчество")}
          name="middleName"
        >
          <Input
            id="middleName"
            autoComplete="additional-name"
            value={middleName}
            onChange={(ev) => setMiddleName(ev.target.value)}
            maxLength={UserValidation.maxNameLength}
            showCharacterCount
            required
          />
        </SettingRow>

        {env.EMAIL_ENABLED && (
          <SettingRow border={false} label={t("Email address")} name="email">
            <Input
              type="email"
              value={user.email}
              readOnly
              onClick={handleChangeEmail}
            />
          </SettingRow>
        )}

        <Button type="submit" disabled={isSaving || !isValid}>
          {isSaving ? `${t("Saving")}…` : t("Save")}
        </Button>
      </form>
    </Scene>
  );
};

export default observer(Profile);
