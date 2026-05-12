-- db_update_goals.sql

ALTER TABLE goal
  MODIFY points_current INT(11) NOT NULL DEFAULT 0,
  MODIFY is_active INT(1) NOT NULL DEFAULT 0;

-- Empfohlen: starttime soll nicht bei späteren Updates überschrieben werden.
-- Falls deine Spalte aktuell "ON UPDATE CURRENT_TIMESTAMP" hat:
ALTER TABLE rounds
  MODIFY starttime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Optional, aber hilfreich für Performance und Sicherheit:
CREATE INDEX idx_goal_user_active ON goal (Id_users, is_active);
CREATE INDEX idx_rounds_user_starttime ON rounds (Id_users, starttime);
