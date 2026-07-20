import {
  formatUserName,
  isCompleteUserName,
  normalizeUserName,
  splitUserName,
} from "./userName";

describe("userName", () => {
  describe("splitUserName", () => {
    it("splits surname, first name and patronymic", () => {
      expect(splitUserName("Иванов Иван Иванович")).toEqual({
        lastName: "Иванов",
        firstName: "Иван",
        middleName: "Иванович",
      });
    });

    it("preserves additional words in the middle name", () => {
      expect(splitUserName("Алиев Рашид Магомед Оглы")).toEqual({
        lastName: "Алиев",
        firstName: "Рашид",
        middleName: "Магомед Оглы",
      });
    });

    it("normalizes repeated whitespace", () => {
      expect(splitUserName("  Иванов   Иван   Иванович  ")).toEqual({
        lastName: "Иванов",
        firstName: "Иван",
        middleName: "Иванович",
      });
    });
  });

  it("formats name parts in FIO order", () => {
    expect(
      formatUserName({
        lastName: "Иванов",
        firstName: "Иван",
        middleName: "Иванович",
      })
    ).toBe("Иванов Иван Иванович");
  });

  it("normalizes a full name", () => {
    expect(normalizeUserName(" Иванов   Иван Иванович ")).toBe(
      "Иванов Иван Иванович"
    );
  });

  it("requires all three name parts for registration", () => {
    expect(isCompleteUserName("Иванов Иван Иванович")).toBe(true);
    expect(isCompleteUserName("Иванов Иван")).toBe(false);
  });
});
