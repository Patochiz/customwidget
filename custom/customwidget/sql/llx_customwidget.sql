-- Module customwidget - Table principale
-- Copyright (C) 2024 CustomWidget

CREATE TABLE IF NOT EXISTS llx_customwidget (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(128) NOT NULL,
    label           VARCHAR(255) NOT NULL,
    description     TEXT,
    widget_type     VARCHAR(20) NOT NULL DEFAULT 'number',
    sql_query       TEXT NOT NULL,

    -- Config Number (KPI)
    number_icon     VARCHAR(128) DEFAULT '',
    number_color    VARCHAR(7) DEFAULT '#0077b6',
    number_suffix   VARCHAR(20) DEFAULT '',
    number_sub1_sql TEXT,
    number_sub1_label VARCHAR(128) DEFAULT '',
    number_sub2_sql TEXT,
    number_sub2_label VARCHAR(128) DEFAULT '',
    number_url      VARCHAR(255) DEFAULT '',

    -- Config Table
    table_columns   TEXT,
    table_maxrows   INTEGER DEFAULT 10,

    -- Config Chart
    chart_type      VARCHAR(20) DEFAULT 'bar',
    chart_colors    TEXT,
    chart_height    INTEGER DEFAULT 300,
    chart_label_col INTEGER DEFAULT 0,
    chart_data_col  INTEGER DEFAULT 1,

    -- Config commune
    display_zone    VARCHAR(20) DEFAULT 'box',
    position        INTEGER DEFAULT 0,
    active          TINYINT DEFAULT 1,
    cache_duration  INTEGER DEFAULT 300,

    -- Métadonnées
    entity          INTEGER DEFAULT 1,
    fk_user_creat   INTEGER,
    fk_user_modif   INTEGER,
    date_creation   DATETIME,
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
