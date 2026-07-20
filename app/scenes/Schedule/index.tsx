import {
  addDays,
  addMonths,
  addWeeks,
  endOfMonth,
  format,
  isSameDay,
  startOfMonth,
  startOfWeek,
} from "date-fns";
import { ru } from "date-fns/locale";
import { observer } from "mobx-react";
import { CalendarIcon } from "outline-icons";
import * as React from "react";
import { useHistory, useParams } from "react-router-dom";
import styled from "styled-components";
import { s } from "@shared/styles";
import Badge from "~/components/Badge";
import Button from "~/components/Button";
import Flex from "~/components/Flex";
import Heading from "~/components/Heading";
import Scene from "~/components/Scene";
import Tab from "~/components/Tab";
import Tabs from "~/components/Tabs";
import Text from "~/components/Text";
import useCurrentUser from "~/hooks/useCurrentUser";
import { schedulePath } from "~/utils/routeHelpers";

type ScheduleView = "week" | "month";

type RouteParams = {
  view?: string;
};

type EmployeeProps = {
  name: string;
  email: string;
};

const weekStartsOn = 1 as const;
const weekDayNames = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];

function capitalize(value: string) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function Schedule() {
  const { view } = useParams<RouteParams>();
  const history = useHistory();
  const user = useCurrentUser();
  const activeView: ScheduleView = view === "month" ? "month" : "week";
  const [anchorDate, setAnchorDate] = React.useState(() => new Date());

  React.useEffect(() => {
    if (view !== activeView) {
      history.replace(schedulePath(activeView));
    }
  }, [activeView, history, view]);

  const periodLabel = React.useMemo(() => {
    if (activeView === "month") {
      return capitalize(format(anchorDate, "LLLL yyyy", { locale: ru }));
    }

    const from = startOfWeek(anchorDate, { weekStartsOn });
    const to = addDays(from, 6);
    return `${format(from, "d MMMM", { locale: ru })} — ${format(
      to,
      "d MMMM yyyy",
      { locale: ru }
    )}`;
  }, [activeView, anchorDate]);

  const movePeriod = React.useCallback(
    (direction: -1 | 1) => {
      setAnchorDate((current) =>
        activeView === "week"
          ? addWeeks(current, direction)
          : addMonths(current, direction)
      );
    },
    [activeView]
  );

  const employee = React.useMemo<EmployeeProps>(
    () => ({
      name: user.name,
      email: user.email,
    }),
    [user.email, user.name]
  );

  return (
    <Scene
      title="График"
      textTitle="График"
      icon={<CalendarIcon />}
      centered={false}
    >
      <Page>
        <Heading>График</Heading>
        <Text as="p" type="secondary">
          Рабочее расписание сотрудников, отсутствия и переработки.
        </Text>

        <Tabs>
          <Tab to={schedulePath("week")} exact>
            Неделя
          </Tab>
          <Tab to={schedulePath("month")} exact>
            Месяц
          </Tab>
        </Tabs>

        <Toolbar>
          <Flex gap={8} wrap>
            <Button neutral onClick={() => movePeriod(-1)}>
              Назад
            </Button>
            <Button neutral onClick={() => setAnchorDate(new Date())}>
              Сегодня
            </Button>
            <Button neutral onClick={() => movePeriod(1)}>
              Вперёд
            </Button>
          </Flex>
          <PeriodTitle>{periodLabel}</PeriodTitle>
        </Toolbar>

        {activeView === "week" ? (
          <WeekView anchorDate={anchorDate} employee={employee} />
        ) : (
          <MonthView anchorDate={anchorDate} employee={employee} />
        )}

        <Legend aria-label="Обозначения графика">
          <Text type="secondary">Обозначения:</Text>
          <Badge primary>+2 ч</Badge>
          <Text type="secondary">переработка</Text>
          <Badge yellow>Отпуск</Badge>
          <Badge>Больничный</Badge>
        </Legend>
      </Page>
    </Scene>
  );
}

function WeekView({
  anchorDate,
  employee,
}: {
  anchorDate: Date;
  employee: EmployeeProps;
}) {
  const days = React.useMemo(() => {
    const firstDay = startOfWeek(anchorDate, { weekStartsOn });
    return Array.from({ length: 7 }, (_, index) => addDays(firstDay, index));
  }, [anchorDate]);

  return (
    <GridScroller>
      <WeekGrid>
        <HeaderCell $sticky>Сотрудник</HeaderCell>
        {days.map((day, index) => (
          <HeaderCell key={day.toISOString()} $today={isSameDay(day, new Date())}>
            <DayName>{weekDayNames[index]}</DayName>
            <DayNumber>{format(day, "d MMMM", { locale: ru })}</DayNumber>
          </HeaderCell>
        ))}

        <EmployeeCell>
          <EmployeeName>{employee.name}</EmployeeName>
          <EmployeeEmail>{employee.email}</EmployeeEmail>
        </EmployeeCell>
        {days.map((day) => (
          <WeekCell
            key={day.toISOString()}
            $today={isSameDay(day, new Date())}
            aria-label={`${employee.name}, ${format(day, "d MMMM yyyy", {
              locale: ru,
            })}: расписание не задано`}
          >
            <EmptyCellText>—</EmptyCellText>
          </WeekCell>
        ))}
      </WeekGrid>
    </GridScroller>
  );
}

function MonthView({
  anchorDate,
  employee,
}: {
  anchorDate: Date;
  employee: EmployeeProps;
}) {
  const days = React.useMemo(() => {
    const firstDay = startOfMonth(anchorDate);
    const count = endOfMonth(anchorDate).getDate();
    return Array.from({ length: count }, (_, index) => addDays(firstDay, index));
  }, [anchorDate]);

  const gridTemplateColumns = `minmax(220px, 1.4fr) repeat(${days.length}, minmax(44px, 1fr))`;
  const minWidth = 220 + days.length * 44;

  return (
    <GridScroller>
      <MonthGrid style={{ gridTemplateColumns, minWidth }}>
        <HeaderCell $sticky>Сотрудник</HeaderCell>
        {days.map((day) => {
          const weekDayIndex = (day.getDay() + 6) % 7;
          return (
            <CompactHeaderCell
              key={day.toISOString()}
              $today={isSameDay(day, new Date())}
            >
              <CompactDayName>{weekDayNames[weekDayIndex]}</CompactDayName>
              <CompactDayNumber>{day.getDate()}</CompactDayNumber>
            </CompactHeaderCell>
          );
        })}

        <EmployeeCell>
          <EmployeeName>{employee.name}</EmployeeName>
          <EmployeeEmail>{employee.email}</EmployeeEmail>
        </EmployeeCell>
        {days.map((day) => (
          <MonthCell
            key={day.toISOString()}
            $today={isSameDay(day, new Date())}
            aria-label={`${employee.name}, ${format(day, "d MMMM yyyy", {
              locale: ru,
            })}: расписание не задано`}
          />
        ))}
      </MonthGrid>
    </GridScroller>
  );
}

const Page = styled.div`
  width: 100%;
  padding: 24px;
  overflow: hidden;
`;

const Toolbar = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin: 20px 0 12px;
  flex-wrap: wrap;
`;

const PeriodTitle = styled.div`
  color: ${s("textSecondary")};
  font-size: 15px;
  font-weight: 600;
`;

const GridScroller = styled.div`
  width: 100%;
  overflow: auto;
  border: 1px solid ${s("divider")};
  border-radius: 8px;
  background: ${s("background")};
`;

const BaseGrid = styled.div`
  display: grid;

  & > * {
    border-inline-end: 1px solid ${s("divider")};
    border-bottom: 1px solid ${s("divider")};
  }
`;

const WeekGrid = styled(BaseGrid)`
  grid-template-columns: minmax(220px, 1.4fr) repeat(7, minmax(132px, 1fr));
  min-width: 1144px;
`;

const MonthGrid = styled(BaseGrid)``;

const HeaderCell = styled.div<{ $today?: boolean; $sticky?: boolean }>`
  min-height: 58px;
  padding: 10px 12px;
  background: ${({ $today, theme }) =>
    $today ? theme.buttonNeutralBackground : theme.backgroundSecondary};
  color: ${s("textSecondary")};
  font-size: 12px;
  font-weight: 600;
  text-align: center;
  ${({ $sticky, theme }) =>
    $sticky
      ? `
        position: sticky;
        left: 0;
        z-index: 3;
        text-align: left;
        background: ${theme.backgroundSecondary};
      `
      : ""}
`;

const CompactHeaderCell = styled(HeaderCell)`
  min-height: 52px;
  padding: 7px 4px;
`;

const DayName = styled.div`
  margin-bottom: 4px;
  text-transform: uppercase;
`;

const DayNumber = styled.div`
  color: ${s("text")};
  font-size: 13px;
  font-weight: 500;
`;

const CompactDayName = styled(DayName)`
  margin-bottom: 2px;
  font-size: 10px;
`;

const CompactDayNumber = styled(DayNumber)`
  font-size: 12px;
`;

const EmployeeCell = styled.div`
  position: sticky;
  left: 0;
  z-index: 2;
  min-height: 96px;
  padding: 12px;
  background: ${s("background")};
`;

const EmployeeName = styled.div`
  color: ${s("text")};
  font-size: 14px;
  font-weight: 600;
`;

const EmployeeEmail = styled.div`
  margin-top: 3px;
  color: ${s("textTertiary")};
  font-size: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
`;

const WeekCell = styled.div<{ $today?: boolean }>`
  min-height: 96px;
  padding: 8px;
  background: ${({ $today, theme }) =>
    $today ? theme.buttonNeutralBackground : theme.background};
`;

const MonthCell = styled.div<{ $today?: boolean }>`
  min-height: 72px;
  padding: 4px;
  background: ${({ $today, theme }) =>
    $today ? theme.buttonNeutralBackground : theme.background};
`;

const EmptyCellText = styled.span`
  color: ${s("textTertiary")};
`;

const Legend = styled.div`
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 14px;
  flex-wrap: wrap;
`;

export default observer(Schedule);
