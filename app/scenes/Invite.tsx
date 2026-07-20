import { observer } from "mobx-react";
import { PlusIcon } from "outline-icons";
import pluralize from "pluralize";
import * as React from "react";
import { useTranslation, Trans } from "react-i18next";
import { Link } from "react-router-dom";
import { toast } from "sonner";
import styled from "styled-components";
import { errToString } from "@shared/utils/error";
import { UserRole } from "@shared/types";
import { parseEmail } from "@shared/utils/email";
import { formatUserName } from "@shared/utils/userName";
import { UserValidation } from "@shared/validations";
import Button from "~/components/Button";
import Flex from "~/components/Flex";
import Input from "~/components/Input";
import type { Option } from "~/components/InputSelect";
import { InputSelect } from "~/components/InputSelect";
import { ResizingHeightContainer } from "~/components/ResizingHeightContainer";
import Text from "~/components/Text";
import Tooltip from "~/components/Tooltip";
import useCurrentTeam from "~/hooks/useCurrentTeam";
import useCurrentUser from "~/hooks/useCurrentUser";
import usePolicy from "~/hooks/usePolicy";
import useStores from "~/hooks/useStores";

type Props = {
  onSubmit: () => void;
};

type InviteRequest = {
  email: string;
  lastName: string;
  firstName: string;
  middleName: string;
};

const emptyInvite = (): InviteRequest => ({
  email: "",
  lastName: "",
  firstName: "",
  middleName: "",
});

function Invite({ onSubmit }: Props) {
  const [isSaving, setIsSaving] = React.useState(false);
  const [invites, setInvites] = React.useState<InviteRequest[]>([
    emptyInvite(),
  ]);
  const { users, collections } = useStores();
  const user = useCurrentUser();
  const team = useCurrentTeam();
  const { t } = useTranslation();
  const predictedDomain = parseEmail(user.email).domain;
  const can = usePolicy(team);
  const [role, setRole] = React.useState<UserRole>(UserRole.Member);

  const handleSubmit = React.useCallback(
    async (ev: React.SyntheticEvent) => {
      ev.preventDefault();
      setIsSaving(true);

      try {
        const response = await users.invite(
          invites
            .filter((invite) => invite.email)
            .map(({ email, lastName, firstName, middleName }) => ({
              email,
              name: formatUserName({ lastName, firstName, middleName }),
              role,
            }))
        );
        onSubmit();

        if (response.length > 0) {
          toast.success(
            t("{{ count }} invites sent", { count: response.length })
          );
        } else {
          toast.message(t("Those email addresses are already invited"));
        }
      } catch (err) {
        toast.error(errToString(err));
      } finally {
        setIsSaving(false);
      }
    },
    [onSubmit, invites, role, t, users]
  );

  const handleChange = React.useCallback((ev, index: number) => {
    setInvites((prevInvites) => {
      const newInvites = [...prevInvites];
      newInvites[index] = {
        ...newInvites[index],
        [ev.target.name]: ev.target.value,
      };
      return newInvites;
    });
  }, []);

  const handleAdd = React.useCallback(() => {
    if (invites.length >= UserValidation.maxInvitesPerRequest) {
      toast.message(
        t("Sorry, you can only send {{MAX_INVITES}} invites at a time", {
          MAX_INVITES: UserValidation.maxInvitesPerRequest,
        })
      );
      return;
    }

    setInvites((prevInvites) => [...prevInvites, emptyInvite()]);
  }, [invites, t]);

  const handleKeyDown = React.useCallback(
    (ev: React.KeyboardEvent<HTMLInputElement>) => {
      if (ev.key === "Enter") {
        ev.preventDefault();
        handleAdd();
      }
    },
    [handleAdd]
  );

  const roleName = pluralize(role);
  const collectionCount = collections.nonPrivate.length;
  const collectionAccessNote = collectionCount ? (
    <span>
      <Trans>Invited {{ roleName }} will receive access to</Trans>{" "}
      <Tooltip
        content={
          <>
            {collections.nonPrivate.map((collection) => (
              <li key={collection.id}>{collection.name}</li>
            ))}
          </>
        }
      >
        <strong>
          <Trans>{{ collectionCount }} collections</Trans>
        </strong>
      </Tooltip>
      .{" "}
    </span>
  ) : undefined;

  const options = React.useMemo<Option[]>(() => {
    const memo: Option[] = [];

    if (user.isAdmin) {
      memo.push({
        type: "item",
        label: t("Admin"),
        description: t("Can manage all workspace settings"),
        value: UserRole.Admin,
      });
    }

    return [
      ...memo,
      {
        type: "item",
        label: t("Editor"),
        description: t("Can create, edit, and delete documents"),
        value: UserRole.Member,
      },
      {
        type: "item",
        label: t("Viewer"),
        description: t("Can view and comment"),
        value: UserRole.Viewer,
      },
    ];
  }, [t, user]);

  return (
    <form onSubmit={handleSubmit}>
      <Flex gap={8} column>
        {team.guestSignin ? (
          <Text as="p" type="secondary">
            <Trans
              defaults="Invite people to join your workspace. They can sign in with {{signinMethods}} or use their email address."
              values={{
                signinMethods: team.signinMethods,
              }}
            />{" "}
            {collectionAccessNote}
          </Text>
        ) : (
          <Text as="p" type="secondary">
            <Trans
              defaults="Invite members to join your workspace. They will need to sign in with {{signinMethods}}."
              values={{
                signinMethods: team.signinMethods,
              }}
            />{" "}
            {collectionAccessNote}
            {can.update && (
              <Trans>
                As an admin you can also{" "}
                <Link to="/settings/authentication">enable email sign-in</Link>.
              </Trans>
            )}
          </Text>
        )}
        <Flex gap={12} column>
          <InputSelect
            options={options}
            onChange={(r) => setRole(r as UserRole)}
            value={role}
            label={t("Invite as")}
          />

          <ResizingHeightContainer style={{ minHeight: 132, marginBottom: 8 }}>
            <Flex gap={16} column>
              {invites.map((invite, index) => {
                const hasAnyValue = Boolean(
                  invite.email ||
                    invite.lastName ||
                    invite.firstName ||
                    invite.middleName
                );
                const required = index === 0 || hasAnyValue;

                return (
                  <Flex key={index} gap={8} column>
                    <Flex gap={8}>
                      <StyledInput
                        type="text"
                        name="lastName"
                        label={t("Фамилия")}
                        labelHidden={index !== 0}
                        onKeyDown={handleKeyDown}
                        onChange={(ev) => handleChange(ev, index)}
                        autoComplete="off"
                        data-1p-ignore
                        value={invite.lastName}
                        maxLength={UserValidation.maxNameLength}
                        required={required}
                        autoFocus={index === 0}
                        flex
                      />
                      <StyledInput
                        type="text"
                        name="firstName"
                        label={t("Имя")}
                        labelHidden={index !== 0}
                        onKeyDown={handleKeyDown}
                        onChange={(ev) => handleChange(ev, index)}
                        autoComplete="off"
                        data-1p-ignore
                        value={invite.firstName}
                        maxLength={UserValidation.maxNameLength}
                        required={required}
                        flex
                      />
                      <StyledInput
                        type="text"
                        name="middleName"
                        label={t("Отчество")}
                        labelHidden={index !== 0}
                        onKeyDown={handleKeyDown}
                        onChange={(ev) => handleChange(ev, index)}
                        autoComplete="off"
                        data-1p-ignore
                        value={invite.middleName}
                        maxLength={UserValidation.maxNameLength}
                        required={required}
                        flex
                      />
                    </Flex>
                    <StyledInput
                      type="email"
                      name="email"
                      label={t("Email")}
                      labelHidden={index !== 0}
                      onKeyDown={handleKeyDown}
                      onChange={(ev) => handleChange(ev, index)}
                      placeholder={`name@${predictedDomain}`}
                      value={invite.email}
                      required={required}
                      autoComplete="off"
                      data-1p-ignore
                      flex
                    />
                  </Flex>
                );
              })}
            </Flex>
          </ResizingHeightContainer>
        </Flex>

        <Flex justify="space-between">
          {invites.length < UserValidation.maxInvitesPerRequest ? (
            <Button
              type="button"
              onClick={handleAdd}
              icon={<PlusIcon />}
              neutral
            >
              {t("Add another")}
            </Button>
          ) : null}
          <Button
            type="submit"
            disabled={isSaving}
            data-on="click"
            data-event-category="invite"
            data-event-action="sendInvites"
          >
            {isSaving ? `${t("Inviting")}…` : t("Send Invites")}
          </Button>
        </Flex>
      </Flex>
    </form>
  );
}

const StyledInput = styled(Input)`
  margin-bottom: -4px;
  min-width: 0;
  flex-shrink: 1;
`;

export default observer(Invite);
