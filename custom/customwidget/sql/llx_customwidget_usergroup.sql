-- Module customwidget - Table liaison widget <-> groupes
CREATE TABLE IF NOT EXISTS llx_customwidget_usergroup (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_customwidget INTEGER NOT NULL,
    fk_usergroup    INTEGER NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;
