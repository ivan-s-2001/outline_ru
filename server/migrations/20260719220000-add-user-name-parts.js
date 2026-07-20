"use strict";

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    await queryInterface.addColumn("users", "lastName", {
      type: Sequelize.STRING,
      allowNull: true,
    });
    await queryInterface.addColumn("users", "firstName", {
      type: Sequelize.STRING,
      allowNull: true,
    });
    await queryInterface.addColumn("users", "middleName", {
      type: Sequelize.STRING,
      allowNull: true,
    });

    await queryInterface.sequelize.query(`
      WITH parsed AS (
        SELECT
          id,
          regexp_split_to_array(btrim(name), E'\\s+') AS parts
        FROM users
      )
      UPDATE users AS u
      SET
        "lastName" = CASE
          WHEN cardinality(parsed.parts) >= 2 THEN NULLIF(parsed.parts[1], '')
          ELSE NULL
        END,
        "firstName" = CASE
          WHEN cardinality(parsed.parts) >= 2 THEN NULLIF(parsed.parts[2], '')
          ELSE NULLIF(parsed.parts[1], '')
        END,
        "middleName" = CASE
          WHEN cardinality(parsed.parts) >= 3
            THEN NULLIF(array_to_string(parsed.parts[3:cardinality(parsed.parts)], ' '), '')
          ELSE NULL
        END
      FROM parsed
      WHERE u.id = parsed.id;
    `);

    await queryInterface.sequelize.query(`
      CREATE OR REPLACE FUNCTION sync_user_name_parts()
      RETURNS trigger AS $$
      DECLARE
        name_parts text[];
      BEGIN
        IF TG_OP = 'INSERT' OR NEW.name IS DISTINCT FROM OLD.name THEN
          name_parts := regexp_split_to_array(btrim(COALESCE(NEW.name, '')), E'\\s+');

          IF cardinality(name_parts) >= 2 THEN
            NEW."lastName" := NULLIF(name_parts[1], '');
            NEW."firstName" := NULLIF(name_parts[2], '');
            NEW."middleName" := CASE
              WHEN cardinality(name_parts) >= 3
                THEN NULLIF(array_to_string(name_parts[3:cardinality(name_parts)], ' '), '')
              ELSE NULL
            END;
          ELSIF cardinality(name_parts) = 1 THEN
            NEW."lastName" := NULL;
            NEW."firstName" := NULLIF(name_parts[1], '');
            NEW."middleName" := NULL;
          END IF;
        ELSIF
          NEW."lastName" IS DISTINCT FROM OLD."lastName"
          OR NEW."firstName" IS DISTINCT FROM OLD."firstName"
          OR NEW."middleName" IS DISTINCT FROM OLD."middleName"
        THEN
          NEW.name := concat_ws(
            ' ',
            NULLIF(btrim(NEW."lastName"), ''),
            NULLIF(btrim(NEW."firstName"), ''),
            NULLIF(btrim(NEW."middleName"), '')
          );
        END IF;

        RETURN NEW;
      END;
      $$ LANGUAGE plpgsql;
    `);

    await queryInterface.sequelize.query(`
      CREATE TRIGGER users_sync_name_parts
      BEFORE INSERT OR UPDATE OF name, "lastName", "firstName", "middleName"
      ON users
      FOR EACH ROW
      EXECUTE FUNCTION sync_user_name_parts();
    `);
  },

  async down(queryInterface) {
    await queryInterface.sequelize.query(
      'DROP TRIGGER IF EXISTS users_sync_name_parts ON users;'
    );
    await queryInterface.sequelize.query(
      'DROP FUNCTION IF EXISTS sync_user_name_parts();'
    );
    await queryInterface.removeColumn("users", "middleName");
    await queryInterface.removeColumn("users", "firstName");
    await queryInterface.removeColumn("users", "lastName");
  },
};
