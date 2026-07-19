import * as React from "react";
import { useTranslation } from "react-i18next";
import styled from "styled-components";
import { s } from "@shared/styles";
import ButtonLarge from "~/components/ButtonLarge";
import ChangeLanguage from "~/components/ChangeLanguage";
import Flex from "~/components/Flex";
import Heading from "~/components/Heading";
import Input from "~/components/Input";
import Text from "~/components/Text";
import { detectLanguage } from "~/utils/language";
import { BackButton } from "./BackButton";
import { Background } from "./Background";
import { Centered } from "./Centered";
import { Form } from "~/components/primitives/Form";

const WorkspaceSetup = ({ onBack }: { onBack?: () => void }) => {
  const { t } = useTranslation();
  const fullNameHint = t(
    "Пишите ФИО через пробел: фамилия имя отчество"
  );

  return (
    <Background>
      <BackButton onBack={onBack} />
      <ChangeLanguage locale={detectLanguage()} />
      <Centered
        as={Form}
        action="/api/installation.create"
        method="POST"
        gap={12}
      >
        <StyledHeading centered>{t("Create workspace")}</StyledHeading>
        <Content>
          {t(
            "Setup your workspace by providing a name and details for admin login. You can change these later."
          )}
        </Content>
        <Inputs column gap={12}>
          <Input
            name="teamName"
            type="text"
            label={t("Workspace name")}
            placeholder="Acme, Inc"
            required
            autoFocus
            flex
          />
          <FullNameField column gap={4}>
            <Input
              name="userName"
              type="text"
              label={t("ФИО администратора")}
              placeholder={t("Иванов Иван Иванович")}
              pattern="\\S+\\s+\\S+\\s+\\S+(?:\\s+\\S+)*"
              title={fullNameHint}
              aria-describedby="admin-full-name-hint"
              margin={0}
              required
              flex
            />
            <NameHint id="admin-full-name-hint" type="secondary">
              {fullNameHint}
            </NameHint>
          </FullNameField>
          <Input
            name="userEmail"
            type="email"
            label={t("Admin email")}
            required
            flex
          />
        </Inputs>
        <ButtonLarge type="submit" fullwidth>
          {t("Continue")} →
        </ButtonLarge>
      </Centered>
    </Background>
  );
};

const Inputs = styled(Flex)`
  width: 100%;
  text-align: left;
`;

const FullNameField = styled(Flex)`
  width: 100%;
`;

const NameHint = styled(Text)`
  margin: 0;
  font-size: 12px;
`;

const StyledHeading = styled(Heading)`
  margin: 0;
`;

const Content = styled(Text)`
  color: ${s("textSecondary")};
  text-align: center;
  margin-top: -8px;
`;

export default WorkspaceSetup;
